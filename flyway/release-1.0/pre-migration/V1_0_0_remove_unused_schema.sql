-- Healthmap-database contains many schema(s) that is not required for Epicore
-- This marks an effort to clean up the schema to hold just the epicore database and none others
-- After mergin the required tables needed for epciore (hm.hmu & hm.ticket), the rest of the schema should be safely removed from the current copy of the database [nonprod-epicore-dev.cpjpph3j7f8a.us-east-1.rds.amazonaws.com]
-- ------------------------------------------------------
-- The following sql code must be run as a root user manually.
-- This script should ***NOT*** be included under the migrations/sql

DROP TABLE epicore.hmu; -- this exists only on dev. (not used. Seems like an attempt was made to migrate the hm databases)
DROP SCHEMA hm;
DROP SCHEMA ddd;
DROP SCHEMA epicore_test;
DROP SCHEMA epicore_v1;
DROP SCHEMA epicore_v3; -- this exists only on dev
DROP SCHEMA hmdb;
DROP SCHEMA hmdrupal;
DROP SCHEMA hmdrupal2;
DROP SCHEMA hmdrupal3;
-- DROP DATABASE innodb; --need to revisit. Unable to delete
DROP SCHEMA opensteward;
DROP SCHEMA openstewardship;
DROP SCHEMA predict;
DROP SCHEMA predict_test;
DROP SCHEMA promed;
DROP SCHEMA resistanceopen;
DROP SCHEMA ropenapi;
DROP SCHEMA ropen_test;
-- DROP SCHEMA tmp; --need to revisit. Unable to delete

