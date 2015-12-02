#------------------------------------------------------------ 1.0 ---------------------------------------
CREATE TABLE `category` ( `id` INT NOT NULL AUTO_INCREMENT ,  `name` VARCHAR(255) NOT NULL ,  `link` VARCHAR(255) NOT NULL ,  `parent_id` INT NULL ,  `position` INT NOT NULL DEFAULT '1' ,    PRIMARY KEY  (`id`)) ENGINE = InnoDB;
ALTER TABLE `category` ADD INDEX(`parent_id`);
ALTER TABLE `category` ADD FOREIGN KEY (`parent_id`) REFERENCES `category`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
