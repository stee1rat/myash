const oracledb = require('oracledb');

module.exports = (sql, binds, dbConfig) => new Promise(async function(resolve, reject) {
  let connection;
  try {
    connection = await oracledb.getConnection(dbConfig);
    let result = await connection.execute(sql, binds);
    resolve(result.rows);
  } catch(err) {
    reject(err);
  } finally {
    if (connection) {
      try {
        await connection.release();
    } catch (e) {
        console.log(e);
      }
    }
  }
});
