-- Table user (Requesters)
-- Add new column 'active' status with default active=1.
-- Adding new column to have the flexibility of decativating requesters

USE epicore;

ALTER TABLE epicore.user
ADD active TINYINT(1) DEFAULT 1 NOT NULL;

commit;