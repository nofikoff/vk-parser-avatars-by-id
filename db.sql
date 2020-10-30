

CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;




LOAD DATA INFILE '/small.csv' INTO TABLE images (`id`)