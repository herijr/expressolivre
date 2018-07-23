#!/bin/bash
WORKSPACE=$(pwd)

docker pull expressolivre/database
docker pull expressolivre/mailboxes
docker pull expressolivre/ldap
docker pull expressolivre/frontend-des
docker pull expressolivre/frontend-des-php7
docker pull memcached

# Server Ldap
docker run -itd --name ldap.expresso -v /etc/localtime:/etc/localtime -p 389:389 -p 636:636 expressolivre/ldap
docker exec -it ldap.expresso service rsyslog restart
docker exec -it ldap.expresso service slapd restart

# Server MailBoxes
docker run -itd --name mailboxes.expresso -v /etc/localtime:/etc/localtime --link ldap.expresso -h mailboxes.expresso -p 25:25 -p 143:143 -p 4190:4190 expressolivre/mailboxes
docker exec -it mailboxes.expresso service rsyslog restart
docker exec -it mailboxes.expresso service saslauthd restart
docker exec -it mailboxes.expresso service cyrus-imapd restart
docker exec -it mailboxes.expresso service postfix restart
docker exec -it mailboxes.expresso perl /tmp/cyrus.pl senha

# Server Database
docker run -itd --name database.expresso -v /etc/localtime:/etc/localtime -p 5432:5432 expressolivre/database
docker exec -it database.expresso service rsyslog restart
docker exec -it database.expresso service postgresql restart
docker exec -it database.expresso service postgresql stop
docker exec -it database.expresso service postgresql start

# Server Memcached
docker run --name memcache.expresso -v /etc/localtime:/etc/localtime -d memcached memcached -m 64

# Server Frontend - PHP 5.6
docker run -itd --name frontend-80.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p 80:80 expressolivre/frontend-des
docker exec -it frontend-80.expresso service rsyslog restart
docker exec -it frontend-80.expresso service apache2 restart
docker exec -it frontend-80.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
docker exec -it frontend-80.expresso ./tmp/check_service.sh

# Server Frontend - PHP 7.0
docker run -itd --name frontend-8080.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p 8080:80 expressolivre/frontend-des-php7
docker exec -it frontend-8080.expresso service rsyslog restart
docker exec -it frontend-8080.expresso service apache2 restart
docker exec -it frontend-8080.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
docker exec -it frontend-8080.expresso ./tmp/check_service.sh
