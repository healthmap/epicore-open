require('dotenv').config();

//requires root username and password
// This can be a function or an object literal.
module.exports = function () {
  const hostspec = process.env.hostspec;
  const username = process.env.flywayusername;
  const password = process.env.flywaypassword;
  const dbName = process.env.database;
  let flywayURL = 'jdbc:mysql://' + hostspec;
  console.log('Connecting to:------>', hostspec, username, password, dbName);
  return {
    flywayArgs: {
      url: flywayURL,
      schemas: dbName,
      locations: 'filesystem:flyway/release-*/migrations',
      user: username,
      password: password,
      sqlMigrationSuffixes: '.sql',
      baselineVersion: '1.0.0',
      baselineOnMigrate: true,
    }
  };
};
