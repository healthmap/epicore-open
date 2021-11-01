-- user table are the requesters

USE epicore;

ALTER TABLE epicore.user
ADD COLUMN roleId INT(11) NOT NULL;

update epicore.user 
set roleId = 1;

ALTER TABLE epicore.user
ADD CONSTRAINT fk_user_role_id
FOREIGN KEY (roleId) REFERENCES epicore.role(id);

commit;