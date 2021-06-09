USE epicore;

CREATE TABLE epicore.role
(
    id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    create_date datetime DEFAULT CURRENT_TIMESTAMP
);

commit;