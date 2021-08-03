-- Epicore V3 changes for external users to use user_id instead of hm_hmu_id (PROMED users)
-- Future Epicore should remove hm_hmu table as this entails HM users as well
-- Table user will be the pure Epicore requester users.

USE epicore;

ALTER TABLE epicore.hm_ticket
ADD user_id int(11) DEFAULT NULL;

commit;