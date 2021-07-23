'use strict';
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
const INPUT_SQL_FILE = '/path/to/datafile/_processed.sql';
const OUTPUT_RESULTS_FILE = '/path/to/datafile/_sqlUpdatesResults.sql';

/*********************************************************************
* Script to update DB with missing lat/long
* Input - > File with update sql statements
* Must have executed node ./js/app/updateLatLngForFetps.js script file as it generates the ~/'_processed.sql' file
* Update the db connection string with appropriate parameters
* requires file named '_processed.sql'
* How to run
* cd repo/epicore/js/app/dataFixScripts
* > node .runBulkStatements.js
***********************************************************************/


async function run() {

  //Creds
  //Environment
  var host = '';
  var port = 3306;
  var user = '';
  var password = '';
  var database = '';

  let sqlStatementArr = [];


  let sqlStrArr = await fs.readFileSync(INPUT_SQL_FILE).toString('utf8').split('\n');

  const conn = await getDBConnection(host, port, user, password, database);

  const sqlResults = await executeRows(sqlStrArr, conn);


  Promise.all([sqlStrArr, conn, sqlResults])
    .then(function (results) {
      try {
        console.log('FINAL:' + JSON.stringify(results[2]));
        fs.writeFileSync(OUTPUT_RESULTS_FILE, JSON.stringify(results[2]));
      } catch (err) {
        console.log(err);
        throw new Error('Error writing output to file');
      }
    });

} //end of run



async function executeRows(sqlArr, connection) {
  try {

    let results = [];
    let i;

    for (i = 0; i < sqlArr.length; i++) {
      var sql = sqlArr[i];
      if (i % 10 == 0) // count will be double of records, as this includes empty line. Can improve next time.
        console.log('Completed updating lat/lon for ' + i + ' records');
      if (sql && sql.length > 0) {
        const sqlPromise = await connection.execute(sql);
        results.push({ SQL: sql, results: sqlPromise });
      }
    }
    return Promise.all(results);

  } catch (err) {
    console.log(err);
    throw new Error('Error executing sql updates', err);
  }

}

async function getDBConnection(host, port, user, password, database) {
  const connection = await mysql.createConnection({
    host: host,
    port: port,
    user: user,
    password: password,
    database: database
  });
  console.log('connected');
  return connection;
}

run().catch(console.log);