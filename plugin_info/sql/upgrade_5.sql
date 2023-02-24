CREATE TABLE IF NOT EXISTS `devolo_cpl_rates` (
	`time` datetime NOT NULL,
	`mac_address_from` varchar(20) NOT NULL,
	`mac_address_to` varchar(20) NOT NULL,
	`tx_rate` smallint UNSIGNED NOT NULL,
	`rx_rate` smallint UNSIGNED NOT NULL
);
