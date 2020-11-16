const express = require('express');
const moment = require('moment');
const runQuery = require('../helpers/run-query.js');
const router = express.Router();

/* GET top sql listing. */

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

  let result; 
  let queryStats = {};

  if (!historical) {
    result =  await runQuery(
        `SELECT h.sql_id, sql_opcode, wait_class, n, total,
                total_by_sql_id/total*100 percent_total,
                percent, rank,
                dbms_lob.substr(sql_text,1000,1) sql_text, SUM(executions) executions,
                ROUND(SUM(s.elapsed_time)/DECODE(SUM(s.executions),0,1,SUM(s.executions))/1e6, 2) avg_time
           FROM (SELECT sql_id, sql_opcode, wait_class, n, total, total_by_sql_id,
                        round(n/total*100,2) percent,
                        dense_rank() over (order by total_by_sql_id desc, sql_id desc) as rank
                   FROM (SELECT sql_id, sql_opcode, NVL(${queryMod1},'CPU') as wait_class, count(*) n,
                                sum(count(*)) over () total,
                                sum(count(*)) over (partition by sql_id,sql_opcode) total_by_sql_id
                           FROM v$active_session_history
                          WHERE sample_time > to_date(:MINDATE, 'DD.MM.YYYY HH24:MI:SS')
                            AND sample_time < to_date(:MAXDATE, 'DD.MM.YYYY HH24:MI:SS')
                            AND sql_id is not null ${queryMod2}
                          GROUP BY sql_id, sql_opcode, NVL(${queryMod1},'CPU')
                          ORDER BY 6 DESC)
                 GROUP BY sql_id, sql_opcode,  wait_class, n, total, total_by_sql_id) h,
                 v$sqlstats s
          WHERE rank <= 10
            AND s.sql_id (+) = h.sql_id
          GROUP BY h.sql_id, sql_opcode, wait_class, n, total, total_by_sql_id, percent, rank,  dbms_lob.substr(sql_text,1000,1)  
          ORDER BY rank, wait_class desc`, 
          {MINDATE: minDate, MAXDATE: maxDate}, 
          dbConfig);
  } else {
    result = await runQuery(
        `SELECT h.sql_id, sql_opcode, wait_class, n, total,
                total_by_sql_id/total*100 percent_total,
                percent, rank
           FROM (SELECT sql_id, sql_opcode, wait_class, n, total, total_by_sql_id,
                        round(n/total*100,2) percent,
                        dense_rank() over (order by total_by_sql_id desc, sql_id desc) as rank
                   FROM (SELECT sql_id, sql_opcode, NVL(${queryMod1},'CPU') as wait_class, count(*) n,
                                sum(count(*)) over () total,
                                sum(count(*)) over (partition by sql_id,sql_opcode) total_by_sql_id
                           FROM dba_hist_active_sess_history
                          WHERE snap_id between :MIN_SNAP_ID and :MAX_SNAP_ID
                            AND sample_time > to_date(:MINDATE, 'DD.MM.YYYY HH24:MI:SS')
                            AND sample_time < to_date(:MAXDATE, 'DD.MM.YYYY HH24:MI:SS')
                            AND sql_id is not null 
                            and dbid = :DBID
                            and instance_number = 1 ${queryMod2}
                          GROUP BY sql_id, sql_opcode, NVL(${queryMod1},'CPU')
                          ORDER BY 6 DESC)
                 GROUP BY sql_id, sql_opcode,  wait_class, n, total, total_by_sql_id) h
          WHERE rank <= 10
          GROUP BY h.sql_id, sql_opcode, wait_class, n, total, total_by_sql_id, percent, rank
          ORDER BY rank, wait_class desc`, 
          { 
            MINDATE: minDate, 
            MAXDATE: maxDate, 
            MIN_SNAP_ID: minSnapId, 
            MAX_SNAP_ID: maxSnapId, 
            DBID: dbid
          }, 
          dbConfig);

    let queries = [...new Set(result.map(item => item[0]))].join("','");
    queries = "'" + queries + "'";

    stats = await runQuery(
        `SELECT s.sql_id,
                SUM(executions_delta) executions,
                ROUND(SUM(s.elapsed_time_delta)/DECODE(SUM(s.executions_delta),0,1,SUM(s.executions_delta))/1e6, 2) avg_time,
                dbms_lob.substr(sql_text,1000,1) sql_text
           FROM dba_hist_sqlstat s,
                dba_hist_sqltext t
          WHERE s.sql_id IN (${queries})
            AND snap_id >= ${minSnapId}
            AND snap_id <= ${maxSnapId}
            AND s.dbid = ${dbid}
            AND s.instance_number = 1
            AND s.sql_id = t.sql_id 
          GROUP BY s.sql_id, dbms_lob.substr(sql_text,1000,1)`,
        {}, dbConfig);
    for (let i = 0; i < stats.length; i++) {
      queryStats[stats[i][0]] = {};
      queryStats[stats[i][0]].executions = stats[i][1];
      queryStats[stats[i][0]].avgTime = stats[i][2];
      queryStats[stats[i][0]].sqlText = stats[i][3];
    }
  }
  
  let json = {};
  
  for (row of result) {
    const rank = row[7]
    if (!json[rank]) json[rank] = {};
    
    json[rank].sql_id = row[0];
    json[rank].sql_opcode = row[1];
    json[rank].total = row[4];
    json[rank].percent_total = row[5];

    if (historical) {
      json[rank].sql_text = queryStats[row[0]] ? queryStats[row[0]].sqlText : null;
      json[rank].executions = queryStats[row[0]] ? queryStats[row[0]].executions : null;
      json[rank].avg_time = queryStats[row[0]] ? queryStats[row[0]].avgTime : null;
    } else {
      json[rank].sql_text = row[8];
      json[rank].executions = row[9];
      json[rank].avg_time = row[10];
    }

    if (!json[rank].waits) json[rank].waits = [];
    json[rank].waits.push({
      name: row[2],
      n: row[3],
      percent: row[6]
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
