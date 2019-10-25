#!/bin/bash
#
# Curiosidade
# Gade foi, de acordo com o Livro de Gênesis, o sétimo filho de Jacó, 
# primeiro dele com Zilpa, e o fundador da tribo israelita de Gade. (https://pt.wikipedia.org/wiki/Gade)
# 
# No contexto deste script, a abreviação de gade é: Gerencia Ambiente Docker Expresso


WORKSPACE=$(pwd)
WORKFLOW_HOME="WORKFLOW_HOME"

NEW=false
UP=false
DOWN=false
CLEAN=false
OLD=false

declare -A versoes

#Versao do PHP e a porta mapeada do Container - Server FrontEnd com a Maquina Hospedeira
versoes["5.3"]="8081:80"
versoes["5.6"]="80:80"	
versoes["7.0"]="8080:80"
versoes["7.3"]="8082:80"


QTDEI=`docker images -q | wc -l`
QTDEC=`docker ps -aq | wc -l`


function servicesFrontEnd {

	for versao in ${!versoes[*]}; do
		
		if [ "$1" = "start" ]; then
		
			docker start frontend-php${versao}.expresso 
		fi

		docker exec -it frontend-php${versao}.expresso service rsyslog restart
		docker exec -it frontend-php${versao}.expresso service apache2 restart
		docker exec -it frontend-php${versao}.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
		docker exec -it frontend-php${versao}.expresso ./tmp/check_service.sh
	done
}


function verificaParametro {

	#As últimas opções de cada ítem vieram do Street Fighter
	possiveisNEW='new novo criar shoryuken'
	possiveisUP='up start inicie iniciar comece levante hadouken'
	possiveisDOWN='down stop pare parar baixe abaixe tigeruppercut'
	possiveisCLEAN='clean clear limpa limpe limpar zere zerar zutsuki'
	possiveisOLD='old antigo correia original yogafire'

	for possivelNEW in $possiveisNEW; do
		
		if [ "x${possivelNEW}" = "x${1}" ]; then
					
			NEW=true
		fi
	done

	for possivelUP in $possiveisUP; do
		
		if [ "x${possivelUP}" = "x${1}" ]; then
					
			UP=true	
		fi
	done


	for possivelDOWN in $possiveisDOWN; do
		
		if [ "x${possivelDOWN}" = "x${1}" ]; then
					
			DOWN=true
		fi
	done

	for possivelCLEAN in $possiveisCLEAN; do
		
		if [ "x${possivelCLEAN}" = "x${1}" ]; then
		
			CLEAN=true			
		fi
	done

	for possivelOLD in $possiveisOLD; do
		
		if [ "x${possivelOLD}" = "x${1}" ]; then
		
			OLD=true			
		fi
	done

 
	if [ "$NEW" = false -a "$UP" = false -a "$DOWN" = false -a "$CLEAN" = false -a "$OLD" = false ]; then
	
		echo -e "\nScript para Gerenciar o Ambiente Docker Expresso\n"
		echo -e "Opcoes validas para:\n"
		echo -e "- CRIAR NOVO Ambiente: $possiveisNEW \n"
		echo -e "- SUBIR o Ambiente: $possiveisUP \n"
		echo -e "- PARAR o Ambiente: $possiveisDOWN \n"
		echo -e "- LIMPAR o Ambiente: $possiveisCLEAN \n"

		exit 0
	fi
}


function msgSemContainer {

	echo -e "Nenhum container encontrado... Foi mal...\n"

	exit 0
}


function bkpLdap {

	docker exec -it ldap.expresso service slapd stop
	docker exec -it ldap.expresso slapcat -l /tmp/data.ldif	
	docker cp ldap.expresso:/tmp/data.ldif $WORKSPACE/../../../DB-DESENV/ldap/
	docker exec -it ldap.expresso service slapd start
}


function restoreLdap {

	docker exec -it ldap.expresso service slapd stop
	docker exec -it ldap.expresso rm -rf /var/lib/ldap
	docker exec -it ldap.expresso mkdir /var/lib/ldap
	docker cp $WORKSPACE/../../../DB-DESENV/ldap/data.ldif ldap.expresso:/tmp/
	docker exec -it ldap.expresso slapadd -f /etc/ldap/slapd.conf -l /tmp/data.ldif	
	docker exec -it ldap.expresso chown -R openldap:openldap /var/lib/ldap
	docker exec -it ldap.expresso service slapd start
}


function bkpPostgres {

	db=$1

	docker exec -it database.expresso su -c "pg_dump -Fp -C -d ${db} | gzip > /tmp/${db}.gz" postgres
	docker cp database.expresso:/tmp/${db}.gz $WORKSPACE/../../../DB-DESENV/postgres/
}


function restorePostgres {
	
	db=$1

	docker exec -it database.expresso su -c "echo \"DROP DATABASE ${db};\" | psql" postgres
	docker cp $WORKSPACE/../../../DB-DESENV/postgres/${db}.gz database.expresso:/tmp/${db}.gz
	docker exec -it database.expresso su -c "cat /tmp/${db}.gz | gunzip | psql" postgres
	docker exec -it database.expresso rm -f /tmp/${db}.gz
}


function up {

	docker start ldap.expresso
	docker exec -it ldap.expresso service rsyslog restart
	docker exec -it ldap.expresso service slapd restart

	docker start mailboxes.expresso 	
	docker exec -it mailboxes.expresso service rsyslog restart
	docker exec -it mailboxes.expresso service saslauthd restart
	docker exec -it mailboxes.expresso service cyrus-imapd restart
	docker exec -it mailboxes.expresso service postfix restart

	docker start database.expresso
	docker exec -it database.expresso service rsyslog restart
	docker exec -it database.expresso service postgresql restart
	docker exec -it database.expresso service postgresql stop
	docker exec -it database.expresso service postgresql start

	docker start memcache.expresso

	servicesFrontEnd start
	
	echo -e "\nAmbiente Docker Expresso Up!\n"
}


function down {

	bkpLdap

	bkpPostgres expresso
	bkpPostgres workflow
	
	docker stop $(docker ps -aq)
	echo -e "\nAmbiente Docker Expresso Down! Va para casa dormir um pouco...\n"
}


function clean {

	if [ $QTDEI -gt 0 ]; then

		if [ $QTDEC -gt 0 ]; then

			docker rm -f $(docker ps -aq)
		else 
			
			echo -e "Nenhum container encontrado... Mas existem imagens a serem removidas...\n"
		fi
	
		docker rmi $(docker images -q)
	
		echo -e "\nAmbiente Docker Expresso Limpo! Acabou a brincadeira...\n"
	else
		echo -e "Nenhuma imagem encontrada... Foi mal...\n"
	fi
}


function old {

	# A rotina abaixo, cria o ambiente de desenvolvimento Expresso utilizando Containers Docker
	# Essa iniciativa fomentou a ideia para a criacao do GADE (Gerencia de Ambiente Docker Expresso)
	# Sempre que houver alguma duvida, consulte a rotina original abaixo
	
	WORKSPACE=$(pwd)

	docker pull expressolivre/database
	docker pull expressolivre/mailboxes
	docker pull expressolivre/ldap
	docker pull expressolivre/frontend-des:php5.3
	docker pull expressolivre/frontend-des:php5.6
	docker pull expressolivre/frontend-des:php7.0
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

	# Server Frontend - PHP 5.3
	docker run -itd --name frontend-php5.3.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p 8081:80 expressolivre/frontend-des:php5.3
	docker exec -it frontend-php5.3.expresso service rsyslog restart
	docker exec -it frontend-php5.3.expresso service apache2 restart
	docker exec -it frontend-php5.3.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
	docker exec -it frontend-php5.3.expresso ./tmp/check_service.sh

	# Server Frontend - PHP 5.6
	docker run -itd --name frontend-php5.6.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p 80:80 expressolivre/frontend-des:php5.6
	docker exec -it frontend-php5.6.expresso service rsyslog restart
	docker exec -it frontend-php5.6.expresso service apache2 restart
	docker exec -it frontend-php5.6.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
	docker exec -it frontend-php5.6.expresso ./tmp/check_service.sh

	# Server Frontend - PHP 7.0
	docker run -itd --name frontend-php7.0.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p 8080:80 expressolivre/frontend-des:php7.0
	docker exec -it frontend-php7.0.expresso service rsyslog restart
	docker exec -it frontend-php7.0.expresso service apache2 restart
	docker exec -it frontend-php7.0.expresso cp /tmp/header.inc.php /var/www/expresso/header.inc.php
	docker exec -it frontend-php7.0.expresso ./tmp/check_service.sh
}


function new {

	clean

	docker pull expressolivre/database
	docker pull expressolivre/mailboxes
	docker pull expressolivre/ldap
	
	for versao in ${!versoes[*]}; do
		
		docker pull expressolivre/frontend-des:php${versao}
	done
	
	docker pull memcached

	novaBaseLdap=true

	# Server Ldap
	if [ -f $WORKSPACE/../../../DB-DESENV/ldap/data.ldif ]; then

		echo -e "Encontrei uma base de Backup do Ldap, vou restaurar...\n" 	
	
		docker run -itd --name ldap.expresso -v /etc/localtime:/etc/localtime -p 389:389 -p 636:636 expressolivre/ldap

		restoreLdap

		novaBaseLdap=false
	else
		
		mkdir -p $WORKSPACE/../../../DB-DESENV/ldap
		
		docker run -itd --name ldap.expresso -v /etc/localtime:/etc/localtime -p 389:389 -p 636:636 expressolivre/ldap
		
		bkpLdap
	fi
 
	docker exec -it ldap.expresso service rsyslog restart
	docker exec -it ldap.expresso service slapd restart


	# Server MailBoxes
	docker run -itd --name mailboxes.expresso -v /etc/localtime:/etc/localtime --link ldap.expresso -h mailboxes.expresso -p 25:25 -p 143:143 -p 4190:4190 expressolivre/mailboxes
	docker exec -it mailboxes.expresso service rsyslog restart
	docker exec -it mailboxes.expresso service saslauthd restart
	docker exec -it mailboxes.expresso service cyrus-imapd restart
	docker exec -it mailboxes.expresso service postfix restart

	if $novaBaseLdap; then
		
		docker exec -it mailboxes.expresso perl /tmp/cyrus.pl senha
	fi


	# Server Database Expresso
	if [ -f $WORKSPACE/../../../DB-DESENV/postgres/expresso.gz ]; then

		echo -e "Encontrei uma base de Backup do Expresso, vou restaurar...\n"
		
		docker run -itd --name database.expresso -v /etc/localtime:/etc/localtime -p 5432:5432 expressolivre/database
		docker exec -it database.expresso service postgresql restart
		
		restorePostgres expresso
	else
		
		mkdir -p $WORKSPACE/../../../DB-DESENV/postgres
		docker run -itd --name database.expresso -v /etc/localtime:/etc/localtime -p 5432:5432 expressolivre/database
		docker exec -it database.expresso service postgresql restart
		
		bkpPostgres	expresso
	fi

	# Server Database Workflow
	if [ -f $WORKSPACE/../../../DB-DESENV/postgres/workflow.gz ]; then
		
		echo -e "Encontrei uma base de Backup do Workflow, vou restaurar...\n"

		restorePostgres workflow
	else

		bkpPostgres	workflow
	fi

	docker exec -it database.expresso service rsyslog restart
	docker exec -it database.expresso service postgresql stop
	docker exec -it database.expresso service postgresql start


	# Server Memcached
	docker run --name memcache.expresso -v /etc/localtime:/etc/localtime -d memcached memcached -m 64

	
	# Servers Frontend - PHP x.x

	mkdir -p $WORKSPACE/../../../$WORKFLOW_HOME
	chmod -R 777 $WORKSPACE/../../../$WORKFLOW_HOME

	for versao in ${!versoes[*]}; do
	
		docker run -itd --name frontend-php${versao}.expresso -v /etc/localtime:/etc/localtime -v $WORKSPACE/../../:/var/www/expresso -v $WORKSPACE/../../../$WORKFLOW_HOME:/home/expressolivre/workflow --link database.expresso --link mailboxes.expresso --link ldap.expresso --link memcache.expresso -p ${versoes[$versao]} expressolivre/frontend-des:php${versao}
	done


	# Servicos FrontEnd
	servicesFrontEnd
}


verificaParametro $1


if $NEW; then

	new
fi

if $UP; then

	if [ $QTDEC -gt 0 ]; then
	
		up
	else
		
		msgSemContainer 
	fi
fi


if $DOWN; then
	
	if [ $QTDEC -gt 0 ]; then
	
		down
	else
		
		msgSemContainer
	fi
fi


if $CLEAN; then
	
	clean
fi


if $OLD; then
	
	old
fi
