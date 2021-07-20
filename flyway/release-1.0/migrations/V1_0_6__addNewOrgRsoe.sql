-- current super-users are userids='94,95,99,122,135'
-- updating these users with admin roles

USE epicore;

INSERT INTO epicore.organization (organization_id, name) VALUES ('8', 'RSOE');

commit;