-- SQL to create geofences table
CREATE TABLE IF NOT EXISTS `geofences` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `office_id` INT DEFAULT NULL,
  `center_lat` DOUBLE NOT NULL,
  `center_lng` DOUBLE NOT NULL,
  `radius1` INT NOT NULL DEFAULT 50,
  `radius2` INT NOT NULL DEFAULT 100,
  `radius3` INT NOT NULL DEFAULT 150,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_geofence_name` (`name`)
);
