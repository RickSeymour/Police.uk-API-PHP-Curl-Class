DROP TABLE IF EXISTS crimes;
CREATE TABLE `crimes` (
`dt`  date NULL DEFAULT NULL ,
`eastings`  int(10) UNSIGNED NULL DEFAULT NULL ,
`northings`  int(10) UNSIGNED NULL DEFAULT NULL ,
`id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`force_id`  enum('avon-and-somerset','bedfordshire','cambridgeshire','cheshire','city-of-london','cleveland','cumbria','derbyshire','devon-and-cornwall','dorset','durham','dyfed-powys','essex','gloucestershire','greater-manchester','gwent','hampshire','hertfordshire','humberside','kent','lancashire','leicestershire','lincolnshire','merseyside','metropolitan','norfolk','northamptonshire','northumbria','north-wales','north-yorkshire','nottinghamshire','south-wales','south-yorkshire','staffordshire','suffolk','surrey','sussex','thames-valley','warwickshire','west-mercia','west-midlands','west-yorkshire','wiltshire') NULL DEFAULT NULL ,
`crime_type`  enum('all-crime','burglary','anti-social-behaviour','robbery','vehicle-crime','violent-crime','other-crime') NULL DEFAULT NULL ,
`context`  text NULL DEFAULT NULL ,
`latitude`  float(6,3) NULL DEFAULT NULL ,
`longitude`  float(6,3) NULL DEFAULT NULL ,
`postcode`  varchar(8) NULL DEFAULT NULL ,
PRIMARY KEY (`id`),
INDEX `en` USING BTREE (`eastings`, `northings`) 
)
ENGINE=MyISAM
CHECKSUM=0
ROW_FORMAT=DYNAMIC
DELAY_KEY_WRITE=0
;




DROP TABLE IF EXISTS crime_type;
CREATE TABLE `crime_type` (
`crime_type_url`  varchar(255) NOT NULL DEFAULT '' ,
`crime_type_name`  varchar(255) NULL DEFAULT NULL ,
PRIMARY KEY (`crime_type_url`)
)
ENGINE=MyISAM
CHECKSUM=0
ROW_FORMAT=DYNAMIC
DELAY_KEY_WRITE=0
;




DROP TABLE IF EXISTS forces;
CREATE TABLE `forces` (
`force_id`  varchar(255) NOT NULL DEFAULT '' ,
`force_name`  varchar(255) NULL DEFAULT NULL ,
`force_url`  text NULL DEFAULT NULL ,
`force_telephone`  varchar(255) NULL DEFAULT NULL ,
`force_facebook`  varchar(255) NULL DEFAULT NULL ,
`force_youtube`  varchar(255) NULL DEFAULT NULL ,
`force_twitter`  varchar(255) NULL DEFAULT NULL ,
`force_myspace`  varchar(255) NULL DEFAULT NULL ,
`force_flickr`  varchar(255) NULL DEFAULT NULL ,
PRIMARY KEY (`force_id`)
)
ENGINE=MyISAM
CHECKSUM=0
ROW_FORMAT=DYNAMIC
DELAY_KEY_WRITE=0
;



DROP TABLE IF EXISTS neighbourhoods;
CREATE TABLE `neighbourhoods` (
`neighbourhood_id`  varchar(255) NOT NULL DEFAULT '' ,
`neighbourhood_name`  varchar(255) NOT NULL DEFAULT '' ,
`force_id`  enum('','avon-and-somerset','bedfordshire','cambridgeshire','cheshire','city-of-london','cleveland','cumbria','derbyshire','devon-and-cornwall','dorset','durham','dyfed-powys','essex','gloucestershire','greater-manchester','gwent','hampshire','hertfordshire','humberside','kent','lancashire','leicestershire','lincolnshire','merseyside','metropolitan','norfolk','northamptonshire','northumbria','north-wales','north-yorkshire','nottinghamshire','south-wales','south-yorkshire','staffordshire','suffolk','surrey','sussex','thames-valley','warwickshire','west-mercia','west-midlands','west-yorkshire','wiltshire') NOT NULL DEFAULT '' ,
PRIMARY KEY (`neighbourhood_id`, `neighbourhood_name`, `force_id`)
)
ENGINE=MyISAM
DEFAULT CHARACTER SET=latin1 COLLATE=latin1_general_ci
CHECKSUM=0
ROW_FORMAT=DYNAMIC
DELAY_KEY_WRITE=0
;

