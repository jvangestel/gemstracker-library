
CREATE TABLE if not exists gems__token_attempts (
        gta_id_attempt bigint unsigned not null auto_increment,
        gta_id_token varchar(9) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' not null,
        gta_ip_address int(11) unsigned not null,
        gta_datetime timestamp not null,

        PRIMARY KEY (gta_id_attempt)
    )
    ENGINE=InnoDB
	AUTO_INCREMENT = 10000
    CHARACTER SET 'utf8' COLLATE 'utf8_general_ci';

