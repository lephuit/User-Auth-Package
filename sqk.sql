 /* SQL for the App */

/*  Users Table */
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `forgot_hash` varchar(32),
  `ip_address` varchar(50),
  `group` int(3) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` int(10) NOT NULL DEFAULT '0',
  `updated_at` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `EMAIL` (`email`))
  ENGINE=INNODB
  DEFAULT CHARSET=utf8
  AUTO_INCREMENT=1;


/*  Users_meta Table */
CREATE TABLE `users_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `pic` varchar(150) NOT NULL,
  `name` varchar(100),
  `about` text,
  `location` varchar(100),
  `lang_from` varchar(100),
  `lang_to` varchar(100),
  `company` varchar(50),
  `website` varchar(50),
  `created_at` int(10) NOT NULL DEFAULT '0',
  `updated_at` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `USERID` (`user_id`))
  ENGINE=INNODB
  DEFAULT CHARSET=utf8
  AUTO_INCREMENT=1;