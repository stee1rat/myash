const express = require('express');
const runQuery = require('../helpers/run-query.js');
const moment = require('moment');
const router = express.Router();

/* GET top sessions listing. */

async function run(req, res) {
  let dbConfig = {
    user: req.query.user,
    password: req.query.password,
    connectString: `${req.query.host}:${req.query.port}/${req.query.service}`
  };
  
  let minDate = Number(req.query.minDate);
  let maxDate = Number(req.query.maxDate);

  minDate = moment(minDate).utc().format("DD.MM.YYYY HH:mm:ss");
  maxDate = moment(maxDate).utc().format("DD.MM.YYYY HH:mm:ss");

  const historical = (req.query.historical === 'true');
  const date = req.query.date;
  const dbid = req.query.dbid;

  let minSnapId;
  let maxSnapId;

  if (historical) {
    const result = await runQuery(
        `SELECT min(snap_id) min_snap_id, max(snap_id) max_snap_id
           FROM dba_hist_snapshot
          WHERE instance_number = 1
            AND trunc(begin_interval_time) = to_date(:MDATE,'DD.MM.YYYY')
            AND dbid = :DBID`,
        {DBID: dbid, MDATE: date},
        dbConfig);
    minSnapId = result[0][0];
    maxSnapId = result[0][1];
  }

  const queryMod1 = req.query.byEvent ? 'event' : 'wait_class';
  const queryMod2 = req.query.byEvent ?
                      req.query.byEvent === 'CPU' ?
                          ` AND wait_class is null` :
                          ` AND wait_class = '${req.query.byEvent}'` : '';

  let sql;
  let binds;

  if (!historical) {
    sql = 
        `SELECT h.*, u.username
           FROM (SELECT session_id, program, wait_class, user_id, n, total,
                        total_by_sid/total*100 percent_total,
                        round(n/total*100,2) percent,
                        dense_rank() over (order by total_by_sid desc, session_id desc) as rank
                  FROM (SELECT session_id || ',' || session_serial# session_id,
                               program, nvl(${queryMod1},'CPU') wait_class, user_id, count(*) n,
                               sum(count(*)) over () total,
                               sum(count(*)) over (partition by session_id  || ',' ||  session_serial#) total_by_sid
                          FROM v$active_session_history
                         WHERE sample_time > to_date(:MINDATE, 'DD.MM.YYYY HH24:MI:SS')
                           AND sample_time < to_date(:MAXDATE, 'DD.MM.YYYY HH24:MI:SS') ${queryMod2}
                         GROUP BY session_id || ',' || session_serial#, program, nvl(${queryMod1},'CPU'), user_id
                         ORDER BY 7 desc, 1)) h,
                 dba_users u
           WHERE h.user_id = u.user_id and rank <= 10 
           ORDER BY rank, wait_class desc`,

    binds = { 
      MINDATE: minDate, 
      MAXDATE: maxDate 
    }; 
  } else {
    sql = 
        `SELECT h.*, u.username  
           FROM (SELECT session_id, program, wait_class, user_id, n, total,
                        total_by_sid/total*100 percent_total,
                        round(n/total*100,2) percent,
                        dense_rank() over (order by total_by_sid desc, session_id desc) as rank
                  FROM (SELECT session_id || ',' || session_serial# session_id,
                               program, nvl(${queryMod1},'CPU') wait_class, user_id, count(*) n,
                               sum(count(*)) over () total,
                               sum(count(*)) over (partition by session_id  || ',' ||  session_serial#) total_by_sid
                          FROM dba_hist_active_sess_history
                         WHERE sample_time >= to_date(:MINDATE, 'DD.MM.YYYY HH24:MI:SS')
                           AND sample_time <= to_date(:MAXDATE, 'DD.MM.YYYY HH24:MI:SS')
                           AND snap_id >= :MINSNAPID
                           AND snap_id <= :MAXSNAPID
                           AND instance_number = 1
                           AND dbid = :DBID ${queryMod2}
                         GROUP BY session_id || ',' || session_serial#, program, nvl(${queryMod1},'CPU'), user_id
                         ORDER BY 7 desc, 1)) h,
                 dba_users u
           WHERE u.user_id (+)= h.user_id and rank <= 10
           ORDER BY rank, wait_class desc`;

    binds = {
      MINDATE: minDate,
      MAXDATE: maxDate,
      MINSNAPID: minSnapId,
      MAXSNAPID: maxSnapId,
      DBID: dbid
    };
  }

  let result = await runQuery(sql, binds, dbConfig);
  
  console.log(minDate, maxDate, maxSnapId, minSnapId, dbid);
  
  let json = {};

  for (row of result) {
    const rank = row[8]
    if (!json[rank]) json[rank] = {};
    
    json[rank].session_id = row[0];
    json[rank].program = row[1];
    json[rank].user_id = row[3];
    json[rank].total = row[5];
    json[rank].percent_total = row[6];
    json[rank].username = row[9];

    if (!json[rank].waits) json[rank].waits = [];
    json[rank].waits.push({
      name: row[2],
      n: row[4],
      percent: row[7]
    });
  }

  let output = {};
  output.top = json;
  output.timestamp = Date.now();
  
  res.setHeader('Content-type', 'application/json');
  res.send(output);
}

router.get('/', function(req, res) {
  run(req, res);
});

module.exports = router;
