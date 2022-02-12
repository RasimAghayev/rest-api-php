-- MySQL
CREATE DATABASE rest_api_php CHARACTER SET utf8 COLLATE utf8_bin;
CREATE USER 'rest_api_php_user'@'%' IDENTIFIED BY 'RasiM123$321MisaR';
GRANT ALL ON rest_api_php.* TO 'rest_api_php_user'@'*';
-- GRANT ALL ON rest_api_php TO 'rest_api_php_user'@'%';
GRANT CREATE ON rest_api_php TO 'rest_api_php_user'@'%';
GRANT ALL PRIVILEGES ON rest_api_php.* TO 'rest_api_php_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;


-- MariaDB
CREATE DATABASE rest_api_php;
-- GRANT ALL PRIVILEGES ON rest_api_php.* TO rest_api_php_user@localhost IDENTIFIED BY 'RasiM123$321MisaR';
GRANT ALL PRIVILEGES ON rest_api_php.* TO rest_api_php_user@localhost IDENTIFIED BY 'RasiM123$321MisaR';


-- ------------------------------------------------------------------------
CREATE TABLE tasks(
    id INT NOT  NULL AUTO_INCREMENT,
    name VARCHAR (128) NOT NULL,
    priority INT DEFAULT NULL ,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id),
    INDEX (name)
);

-- ---------------
DESCRIBE tasks;

SHOW INDEXES FROM tasks;


-- --------------------


insert into tasks (name,priority,is_completed) values
('Test edilesi case',1,true ),
('Test edilesi case',2,false ),
('Test edilesi case',NULL,false );

-- --------------------
CREATE TABLE `users` (
                         `id` INT(11) NOT NULL AUTO_INCREMENT,
                         `name` VARCHAR(128) NOT NULL COLLATE 'utf8_bin',
                         `username` VARCHAR(128) NOT NULL COLLATE 'utf8_bin',
                         `password_hash` VARCHAR(255) NOT NULL COLLATE 'utf8_bin',
                         `api_key` VARCHAR(32) NOT NULL COLLATE 'utf8_bin',
                         PRIMARY KEY (`id`) USING BTREE,
                         UNIQUE INDEX `username` (`username`) USING BTREE,
                         UNIQUE INDEX `api_key` (`api_key`) USING BTREE
)
    COLLATE='utf8_bin'
ENGINE=InnoDB
;

alter table tasks
    add user_id int not null;

create index user_id
    on tasks (user_id);

ALTER TABLE `tasks`
    ADD COLUMN `user_id` INT(11) NOT NULL AFTER `user_id`,
    ADD INDEX `user_id` (`user_id`);

UPDATE rest_api_php.tasks SET user_id=1;
ALTER TABLE rest_api_php.tasks ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE ;


-- --------------------------------
CREATE TABLE refresh_token(
    token_hash varchar(64) NOT NULL ,
    expires_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (token_hash),
    INDEX (expires_at)
);
















--------------------------------POSTGRSQL



CREATE TABLE "tasks" (
                         "id" BIGSERIAL NOT NULL,
                         "name" VARCHAR(255) NOT NULL,
                         "priority" INTEGER NULL,
                         "is_completed" BOOLEAN NOT NULL,
                         "user_id" INTEGER NOT NULL,
                         PRIMARY KEY ("id")
)
;
CREATE INDEX "name" ON "tasks" USING HASH ("name");


