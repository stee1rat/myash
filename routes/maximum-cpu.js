const express = require('express');
const oracledb = require('oracledb');
const runQuery = require('../helpers/run-query.js');
const router = express.Router();

/* GET get snapshots listing. */

async function run(req, res) {
  let dbConfig = {
    user: req.query.user,
    password: req.query.password,
    connectString: `${req.query.host}:${req.query.port}/${req.query.service}`
  };
  
  let result = await runQuery(
    `SELECT i.dbid, 
            db_name, 
            to_char(trunc(begin_interval_time,'DD'), 'DD.MM.YYYY') day, 
            decode(i.dbid, d.dbid, 1, 0) this
       FROM dba_hist_snapshot s,
            dba_hist_database_instance i,
            v$database d
      WHERE s.dbid = i.dbid
      GROUP BY trunc(begin_interval_time,'DD'),
            i.dbid, db_name, d.dbid
      ORDER BY 4 desc, 
            i.dbid, 
            trunc(begin_interval_time,'DD') DESC`,
    {}, dbConfig);
  
  let output = {};
  for (let i = 0; i < result.length; i++) {
    let row = result[i];
    if (output[row[0]] === undefined) {
      output[row[0]] = {};
      output[row[0]].name = row[1];
      output[row[0]].isCurrentDatabase = row[3] === 1 ? true : false;
      output[row[0]].dates = [];
    }
    output[row[0]].dates.push(row[2]);
  }

  res.setHeader('Content-type', 'application/json');
  res.send(output);
}

router.get('/', function(req, res) {
  run(req, res);
});

module.exports = router;
