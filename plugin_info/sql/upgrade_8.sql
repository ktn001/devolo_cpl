CREATE TABLE IF NOT EXISTS `devolo_connection` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`serial` varchar(17) NOT NULL,
	`mac` varchar(17) NOT NULL,
	`ip` varchar(60),
	`network` varchar(20) NOT NULL,
	`connect_time` datetime NOT NULL,
	`disconnect_time` datetime,
	PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `devolo_macinfo` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`mac` VARCHAR(17) NOT NULL UNIQUE,
	`vendor`VARCHAR(50),
	`name` VARCHAR(30) UNIQUE,
	PRIMARY KEY (`id`)
);
