'use strict';
const { URL } = require('url');
const fs = require('fs');
const fetch = require('node-fetch');
const CRYPTOKEY = '';

const INPUT_DATA_FILE = '/path/to/datafile/_temp.json';
const PROCESSED_DATA_FILE = '/path/to/datafile/_processed.sql';
const UNPROCESSED_DATA_FILE = '/path/to/datafile/_unProcessed.sql';
const RESULTS_FILE = '/path/to/datafile/output.txt';
let data = require(INPUT_DATA_FILE);

/*********************************************************************
* Generate sql statements for records that have missing lat lon 
* Provide data and path for: CRYPTOKEY, INPUT_DATA_FILE, PROCESSED_DATA_FILE, UNPROCESSED_DATA_FILE, RESULTS_FILE accordingly
* Input: json file (INPUT_DATA_FILE) 
  Extract data as a json from the database using the following sql
  select maillist_id, email, city, state, country
  from epicore.maillist
  where maillist_id IN (
  SELECT maillist_id
  FROM epicore.fetp
  where lat=0.000000
  and lon=0.000000
  order by fetp_id desc)
* How it works:
  Using maps.googleapi we fetch lat lon for every record in the input file using city, state, and country
  Once lat/lon received create a 'update' statement accordingly and write to file.
* Output:
  3 Files genrated:
  PROCESSED_DATA_FILE (sql statements generated)
  UNPROCESSED_DATA_FILE (records in error)
  RESULTS_FILE (output log)
* How to run
* cd repo/epicore/js/app/dataFixScripts
* > node ./updateLatLngForFetps.js
***********************************************************************/

async function run() {

  var promiseArr = [];
  var invalidData = [];
  var outputText = [];
  let dataSize = data.length;
  console.log('TOTAL NUM RECORDS:', dataSize);
  outputText.push('TOTAL NUM RECORDS:' + dataSize);

  for (let j = 0; j < dataSize; j++) {
    let rowObj = data[j];
    let objRowLength = Object.keys(rowObj).length;
    if (j % 10 == 0)
      console.log('Completed processing ' + j + ' records');

    if (objRowLength && objRowLength > 0) {
      let maillist_id = rowObj.maillist_id;
      let email = rowObj.email;
      let city = rowObj.city;
      let state = rowObj.state;
      let country = rowObj.country;
      if (city != null && state != null && country != null) {
        var promise = await fetchLatLong(maillist_id, email, city, state, country);
        promiseArr.push(promise);
      } else {
        invalidData.push('Invalid data for maillist_id:' + maillist_id + ' address:' + city + ',' + state + ',' + country + ' email:' + email);
      }
    } //close if

  } //close for

  Promise.all([promiseArr])
    .then(function (results) {
      // console.log('FINAL:' + JSON.stringify(results[0]));
      try {
        const resultData = results[0];
        let updateRecs = [];
        let errorRecs = [];
        resultData.map((tuple) => {
          if (tuple && tuple.length > 0) {
            if (tuple.startsWith('update')) {
              updateRecs.push(tuple + '\n');
            } else {
              errorRecs.push(tuple + '\n');
            }
          }
        });
        outputText.push('TOTAL NUM RECORDS PROCESSED:' + updateRecs.length);
        outputText.push('TOTAL NUM RECORDS UNPROCESSED:' + errorRecs.length);
        outputText.push('TOTAL NUM RECORDS INVALID:' + invalidData.length);

        const resultsArr = [...outputText, '\n\n', ...invalidData];
        fs.writeFileSync(PROCESSED_DATA_FILE, updateRecs.join('\n'));
        fs.writeFileSync(UNPROCESSED_DATA_FILE, errorRecs.join('\n'));
        fs.writeFileSync(RESULTS_FILE, resultsArr.join('\n'));

        console.log('RESULTS:', outputText);
        console.log('Job completed.');

      } catch (error) {
        console.error('Unable to write to file:', err);
      }
    });

} //close run function


async function fetchLatLong(maillist_id, email, city, state, country) {

  return new Promise((resolve, reject) => {

    let url = 'https://maps.googleapis.com/maps/api/geocode/json?address==' + city + ',' + state + ',' + country + '&key=' + CRYPTOKEY;

    //Escape spl chars
    var regexForEscChars = /,/g;
    city.replace(regexForEscChars, '\,');
    state.replace(regexForEscChars, '\,');
    country.replace(regexForEscChars, '\,');

    fetch(new URL(url), {
      method: 'post',
      headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
      .then(res => res.json())
      .then(json => {

        let resp = {
          'maillist_id': maillist_id,
          'email': email,
          'city': city,
          'state': state,
          'country': country,
          'lat': (json && json.results[0] && json.results[0].geometry) ? json.results[0].geometry.location.lat : 0.000000,
          'long': (json && json.results[0] && json.results[0].geometry) ? json.results[0].geometry.location.lng : 0.000000
        };

        let sqlStatement = '';
        if (json && json.results[0] && json.results[0].geometry)
          sqlStatement = 'update epicore.fetp set lat=\'' + resp.lat + '\' , lon=\'' + resp.long + '\' where maillist_id=' + maillist_id + ' and email=\'' + email + '\';';
        else
          sqlStatement = 'Unable to fetch for maillist_id:' + maillist_id + ' address:' + city + ',' + state + ',' + country + ' status:' + json.status + ' url:' + url;

        resolve(sqlStatement);
      })
      .catch(err => {
        //console.log('Error for maillist_id:' + maillist_id + '--->' + err);
        let sqlStatement = 'Error:Unable to fetch for maillist_id:' + maillist_id + ' address:' + city + ',' + state + ',' + country + 'url:' + url + '\n Error:' + err;
        resolve(true);

      });
  });
}

run().catch(console.log);
