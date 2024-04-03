@echo off
set MYSQL_USER=root
set MYSQL_PASSWORD=''
set MYSQL_HOST=localhost
set MYSQL_DATABASE=sicode2

pause

@echo on

mysql -u root -p -h localhost -e "SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE files; TRUNCATE TABLE files_form; TRUNCATE TABLE comments; TRUNCATE TABLE comment_reclaim; TRUNCATE TABLE comment_viability; TRUNCATE TABLE file_production; TRUNCATE TABLE forms; TRUNCATE TABLE reclaims; TRUNCATE TABLE reclaim_viability; TRUNCATE TABLE viabilities; SET FOREIGN_KEY_CHECKS=1" sicode2

@echo off
p