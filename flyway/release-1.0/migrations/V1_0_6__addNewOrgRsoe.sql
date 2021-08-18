-- current super-users are userids='94,95,99,122,135'
-- updating these users with admin roles

USE epicore;
-- already exists in PROD and others. Need to run Flyway smoothly so comment below line
-- INSERT INTO epicore.organization (`organization_id`, `name`) VALUES ('8', 'RSOE');

commit;