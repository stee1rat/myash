var express = require('express');
var router = express.Router();

/* GET top activity listing. */

const runQuery = require('../helpers/run-query.js');

async function run(req, res) {
  const dbConfig = {
    user: req.query.user,
    password: req.query.password,
    connectString: `${req.query.host}:${req.query.port}/${req.query.service}`
  };

  const queryMod1 = req.query.byEvent ? 'event' : 'wait_class';
  const queryMod2 = req.query.byEvent ?
                      req.query.byEvent === 'CPU' ?
                        ` AND wait_class is null` :
                        ` AND wait_class = '${req.query.byEvent}'` : '';

  const historical = (req.query.historical === 'true');
  const date = req.query.date;
  const dbid = req.query.dbid;

  let startTime;
  let minSnapId;
  let maxSnapId;

  if (!historical) {
    const result = await runQuery(
        `SELECT (sysdate - 1/24 - mod(to_char(sysdate - 1/24, 'ss'),15)/86400 - date '1970-01-01')*86400000
           FROM dual`, {}, dbConfig);
    startTime = result[0][0];
  } else {
    const result = await runQuery(
        `SELECT (trunc(min(begin_interval_time) - mod(to_char(min(begin_interval_time), 'mi'),5)/24/60, 'mi') - date '1970-01-01')*86400000,
                 min(snap_id) min_snap_id, max(snap_id) max_snap_id
           FROM dba_hist_snapshot
          WHERE instance_number = 1
            AND trunc(begin_interval_time) = to_date('${date}','DD.MM.YYYY')
            AND dbid = ${dbid}`, {}, dbConfig);
    startTime = result[0][0];
    minSnapId = result[0][1];
    maxSnapId = result[0][2];
  }

  let ASHQuery;
  let CPUQuery;
  if (!historical) {
    ASHQuery = 
      `SELECT (cast(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second') as date) - date '1970-01-01')*86400000 sample_time,
              wait_class,
              sum(sessions)/max(rollup_samples)
         FROM (SELECT sample_time, nvl(${queryMod1},'CPU') wait_class, 
                      count(*) sessions,
                      count(distinct sample_time)
                      over (partition by (cast(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second') as date) - date '1970-01-01')*86400000) rollup_samples
                 FROM v$active_session_history
                WHERE (cast(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second') as date) - date '1970-01-01')*86400000 >= ${startTime} ${queryMod2}
                GROUP BY sample_time, nvl(${queryMod1},'CPU'))
        GROUP BY (cast(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second') as date) - date '1970-01-01')*86400000, wait_class
        ORDER BY 1, 2 desc`;

    CPUQuery = 
      `SELECT least(cpu_core_count_current, cpu_count_current) 
         FROM v$license`;
  } else {
    ASHQuery = 
      `SELECT (trunc(sample_time - mod(to_char(sample_time, 'mi'),5)/24/60, 'mi') - date '1970-01-01')*86400000 sample_time,
              wait_class,
              sum(sessions)/max(rollup_samples)
         FROM (SELECT sample_time, nvl(${queryMod1},'CPU') wait_class,
                      count(*) sessions,
                      count(distinct sample_time)
                      over (partition by (trunc(sample_time - mod(to_char(sample_time, 'mi'),5)/24/60, 'mi') - date '1970-01-01')*86400000) rollup_samples
                 FROM dba_hist_active_sess_history
                WHERE snap_id between ${minSnapId} and ${maxSnapId}
                  AND dbid = ${dbid}
                  AND instance_number = 1 ${queryMod2}
                GROUP BY sample_time, nvl(${queryMod1},'CPU'))
        GROUP BY (trunc(sample_time - mod(to_char(sample_time, 'mi'),5)/24/60, 'mi') - date '1970-01-01')*86400000, wait_class
        ORDER BY 1, 2 desc`;

    CPUQuery =
      `SELECT least(s.value, p.value)
         FROM dba_hist_parameter p, dba_hist_osstat s
        WHERE p.parameter_name = 'cpu_count'
          AND s.stat_name = 'NUM_CPU_CORES'
          AND s.snap_id = p.snap_id
          AND s.snap_id = ${maxSnapId}
          AND s.dbid = ${dbid}
          AND p.dbid = ${dbid}
          AND p.instance_number = 1
          AND s.instance_number = 1`;
    }
  
  const result = await Promise.all([
      runQuery(ASHQuery, {}, dbConfig),
      runQuery(CPUQuery, {}, dbConfig)
  ])
    
  const steps = historical ? 288 : 240;
  const step = historical ? 3e5 : 15e3;
  let history = {};
  for (i = startTime; i <= startTime + steps*step; i += step) {
    history[i] = {
      sessionsCount: 0
    };  
  } 
  
  let waits = {};
  for (let row of result[0]) {
    if (history[row[0]] == undefined) continue;
    history[row[0]][row[1]] = row[2];
    history[row[0]].sessionsCount += row[2];
    if (waits[row[1]] === undefined) {
      waits[row[1]] = {};
    }
  }
  
  let series = {};
  let sessionsMax = 0;
  for (let time in history) {
    if (history[time].sessionsCount > sessionsMax) {
      sessionsMax = history[time].sessionsCount;
    }
    for (let wait in waits) {
      if (series[wait] === undefined) {
        series[wait] = {};
        series[wait].data = [];
      }
      if (history[time][wait] !== undefined) {
         series[wait].data.push([Number(time), history[time][wait]]);
      } else {
         series[wait].data.push([Number(time), 0]);
      }
    }
  }

  let json = {
    sessionsMax: sessionsMax,
    maximumCPU: result[1],
    series: [],
  };

  for (let wait in waits) {
    let a = {};
    a.name = wait;
    switch (wait) {
      case 'User I/O':
        a["color"] = "blue";
        a["index"] = 99;
        break;
      case 'Commit':
        a["color"] = "Orange";
        a["index"] = 97;
        break;
      case 'System I/O':
        a["color"] = "#0890F0";
        a["index"] = 98;
        break;
      case 'Concurrency':
        a["color"] = "#800000";
        a["index"] = 1;
        break;
      case 'Application':
        a["color"] = "#FF0000";
        a["index"] = -1;
        break;
      case 'Administrative':
        a["color"] = "SlateGray";
        a["index"] = 1;
        break;
      case 'Other':
        a["color"] = "Pink";
        a["index"] = 1;
        break;
      case 'Network':
        a["color"] = "DarkGrey";
        a["index"] = -96;
        break;
      case 'Queueing':
        a["color"] = "LightGrey";
        a["index"] = -98;
        break;
      case 'Configuration':
        a["color"] = "#5c3317";
        a["index"] = 1;
        break;
      case 'CPU':
        a["color"] = "#00CC00";
        a["index"] = 100;
        break;
    }
    a.data = series[wait].data;
    json.series.push(a);
  }

  res.setHeader('Content-type', 'application/json');
  res.send(JSON.stringify(json));
}

router.get('/', function(req, res) {
  run(req, res);
});

module.exports = router;
