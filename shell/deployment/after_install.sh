#!/bin/bash
#######################################################################
# Application preparation
#######################################################################
PROJECT_DIR="/var/www/magento/"

(
    cd /var/www/magento ;
    mkdir -p feeds;
    mkdir -p media/feeds;
   
    # Copy in the production local configuration
    rm appspec.yml ;
    
    #copy nginx config
    mkdir -p /etc/nginx/sites-enabled
    mv shell/deployment/nginx/nginx.conf /etc/nginx/;
    
    rm -rf magazine_bk;
    rm -rf nbproject; 
    rm -rf db;

    #mv app/etc/local.xml.production app/etc/local.xml;
    #mv app/etc/fpc.xml.production app/etc/fpc.xml;
    
    mkdir -p /var/www/magento/var;
    mkdir -p /var/www/magento/var/ew;
    mkdir -p /var/www/magento/var/log;
    mkdir -p /var/www/magento/var/log/extendware;   
    chmod -R 777 /var/www/magento/var;
   
    chown apache:apache /var/www/magento/var -R;
    chmod -R 777 /var/www/magento/var/log;

    mkdir -p /var/www/magento/progos;
    chmod -R 777 /var/www/magento/progos;
 
    chmod -R 777 /var/www/magento/feeds;
 
    if [ ! -d /var/www/magento/skin ]; then
      ln -s /var/www/magento/skin /var/www/magento/media;
    fi

    if [ ! -d /var/www/magento/js ]; then
      ln -s /var/www/magento/js /var/www/magento/media;
    fi

    export SEARCH='Magento'

    export REGION=$(curl -s 169.254.169.254/latest/meta-data/placement/availability-zone/ | sed -e 's/^\(.*\)\(.\)$/\1/')
    export INSTANCE_ID=$(curl -s 169.254.169.254/latest/meta-data/instance-id)
    export FILTER="Name=resource-id,Values=${INSTANCE_ID}"
    export QUERY='Tags[?Key==`Name`]|[0].Value'

    #Checks to see if instance this is runs from has a Name tag that matches what's been supplied in SEARCH
    aws ec2 describe-tags --region ${REGION} --filters ${FILTER} --query ${QUERY} |grep -qs ${SEARCH}

    #If successful grep $? will equal 0
    if [ $? -eq '0' ]
    then
      mv shell/deployment/nginx/site-available/rsdev.elabelz.com.conf /etc/nginx/sites-enabled/;
      mv app/etc/local.xml.stage app/etc/local.xml;
      mv app/etc/fpc.xml.stage app/etc/fpc.xml;
      #rm -rf shell/deployment; 
    fi

    export SEARCH='Cron'
    #Checks to see if instance this is runs from has a Name tag that matches what's been supplied in SEARCH
    aws ec2 describe-tags --region ${REGION} --filters ${FILTER} --query ${QUERY} |grep -qs ${SEARCH}
    if [ $? -eq '0' ]
    then
       mv app/etc/local.xml.cron app/etc/local.xml;
       #rm -rf shell/deployment;
    fi


    export SEARCH='Job'
    #Checks to see if instance this is runs from has a Name tag that matches what's been supplied in SEARCH
    aws ec2 describe-tags --region ${REGION} --filters ${FILTER} --query ${QUERY} |grep -qs ${SEARCH}
    if [ $? -eq '0' ]
    then
       mv app/etc/local.xml.job app/etc/local.xml;
       #rm -rf shell/deployment;
    fi


    export SEARCH='Magento-Web' 
    #Checks to see if instance this is runs from has a Name tag that matches what's been supplied in SEARCH
    aws ec2 describe-tags --region ${REGION} --filters ${FILTER} --query ${QUERY} |grep -qs ${SEARCH}
    if [ $? -eq '0' ]
    then
       mv shell/deployment/nginx/www.conf.web /etc/php-fpm-5.5.d/www.conf;
       mv shell/deployment/nginx/nginx.conf.web /etc/nginx/nginx.conf;
       mv shell/deployment/nginx/site-available/elabelz.com.conf /etc/nginx/sites-enabled/;
       mv shell/deployment/nginx/site-available/www.elabelz.com.conf /etc/nginx/sites-enabled/;
       mv app/etc/local.xml.production app/etc/local.xml;
       mv app/etc/fpc.xml.production app/etc/fpc.xml;
       #rm -rf shell/deployment;
    fi
	


    export SEARCH='Magento-Admin'
    #Checks to see if instance this is runs from has a Name tag that matches what's been supplied in SEARCH
    aws ec2 describe-tags --region ${REGION} --filters ${FILTER} --query ${QUERY} |grep -qs ${SEARCH}
    if [ $? -eq '0' ]
    then
       mv shell/deployment/nginx/www.conf.admin /etc/php-fpm-5.5.d/www.conf;
       mv shell/deployment/nginx/nginx.conf.admin /etc/nginx/nginx.conf;
       mv shell/deployment/nginx/site-available/admin.elabelz.com.conf /etc/nginx/sites-enabled/;
       mv app/etc/local.xml.production app/etc/local.xml;
       mv app/etc/fpc.xml.production app/etc/fpc.xml;
       #rm -rf shell/deployment;
    fi

    
    

    chmod 644 /var/www/magento/app/etc/*.xml;

    service nginx restart;
    service php-fpm restart;
)
