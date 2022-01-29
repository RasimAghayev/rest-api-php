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


--------------------------------------------------------------------------
CREATE TABLE tasks(
    id INT NOT  NULL AUTO_INCREMENT,
    name VARCHAR (128) NOT NULL,
    priority INT DEFAULT NULL ,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id),
    INDEX (name)
);

-----------------
DESCRIBE tasks;

SHOW INDEXES FROM tasks;


----------------------


insert into tasks (name,priority,is_completed) values
('Test edilesi case',1,true ),
('Test edilesi case',2,false ),
('Test edilesi case',NULL,false );

----------------------
CREATE TABLE users(
                      id INT NOT  NULL AUTO_INCREMENT,
                      name VARCHAR (128) NOT NULL,
                      username VARCHAR (128) NOT NULL,
                      password_hash VARCHAR (255) NOT NULL,
                      api_key VARCHAR (128) NOT NULL,
                      PRIMARY KEY (id),
                      UNIQUE (username),
                      UNIQUE (api_key)
);
