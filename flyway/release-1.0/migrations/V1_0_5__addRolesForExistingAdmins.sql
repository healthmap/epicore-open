-- current super-users are userids='94,95,99,122,135'
-- updating these users with admin roles

USE epicore;

update epicore.user 
set roleId = 3
where user_id in (94, 95, 99, 122, 135);

commit;