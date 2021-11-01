-- fetp users are the responders

USE epicore;

ALTER TABLE epicore.fetp
ADD COLUMN roleId INT(11) NOT NULL;

update epicore.fetp 
set roleId = 2;

ALTER TABLE epicore.fetp
ADD CONSTRAINT fk_role_id
FOREIGN KEY (roleId) REFERENCES epicore.role(id);

commit;