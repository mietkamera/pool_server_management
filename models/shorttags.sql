SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE shorttags;
USE shorttags;

CREATE TABLE `valid_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipv6` boolean DEFAULT false,
  `private` boolean DEFAULT false,
  `reserved` boolean DEFAULT false,
  `ip` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

