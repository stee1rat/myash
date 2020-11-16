var express = require('express');
var oracledb = require('oracledb');
var router = express.Router();

/* GET database version. */
router.get('/', function(req, res, next) {
  let params = {
    user: req.query.user,
    password: req.query.password,
    connectString: `${req.query.host}:${req.query.port}/${req.query.service}`
  };

  oracledb.getConnection(params, function(err, connection) {
    res.setHeader('Content-type', 'application/json');
    if (err) {
      res.status(500).send(JSON.stringify({status:500, message: err.message}));
      return;
    }

    connection.execute(
      `SELECT instance_name, 
              host_name, 
              version, 
              least(cpu_core_count_current, cpu_count_current) 
         FROM v$instance, 
              v$license`,
      function(err, result) {
        if (err) {
          res.status(500).send(JSON.stringify({
            status: 500, 
            message: err.message
          }));
        } else {
          res.send(JSON.stringify(result.rows));
        }
        connection.release(function(err) {
          if (err) {
            console.error(err.message);
          }
      });
    });
  });
});

module.exports = router;
