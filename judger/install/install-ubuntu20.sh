#!/bin/bash
set -e -x
#before install check DB setting in 
#	judge.conf 
#	hustoj-read-only/web/include/db_info.inc.php
#	and down here
#and run this with root

#CENTOS/REDHAT/FEDORA WEBBASE=/var/www/html APACHEUSER=apache 
WEBBASE=/var/www/
APACHEUSER=www-data
DBUSER=root
DBPASS=root


#try install tools, modify the mysql deb's link if necessary
wget -P ../../../ https://dev.mysql.com/get/mysql-apt-config_0.8.16-1_all.deb
apt install ../../../mysql-apt-config_0.8.16-1_all.deb
#deps="make flex g++ clang libmysql++-dev php7.0 apache2 mysql-server libapache2-mod-php7.0 php7.0-mysql php7.0-mbstring php7.0-gd php7.0-cli php-xml mono-mcs subversion libexplain-dev"
deps="make flex g++ libmysql++-dev php7.4 apache2 mysql-server libapache2-mod-php7.4 php7.4-mysql php7.4-mbstring php7.4-gd php7.4-cli php-xml mono-mcs subversion libexplain-dev"
apt update
apt -y install $deps
#apt purge -y --auto-remove $buildDeps
apt clean

/etc/init.d/mysql start

#set up database
#mysql -uroot -proot < db.sql

#create user and homedir
/usr/sbin/useradd -m -u 1536 judge

#compile and install the core
cd ../core/
bash ./make.sh
cd ../../..

#install web and db
rm -R $WEBBASE
cp -R HZNUOJ $WEBBASE
chgrp -R $APACHEUSER $WEBBASE/web/OJ/
chmod 750 $WEBBASE/web/OJ/

#create upload dir
mkdir -p $WEBBASE/web/OJ/upload
chmod 770 $WEBBASE/web/OJ/upload
chgrp -R $APACHEUSER $WEBBASE/web/OJ/upload

#create work dir set default conf
mkdir -p /home/judge
mkdir -p /home/judge/etc

#make data for problem 1000
mkdir -p /home/judge/data/1000
pushd /home/judge/data/1000
    echo "1 2" > sample0.in
    echo "3" > sample0.out
    echo "6 10" > test0.in
    echo "16" > test0.out
    echo "6 9" > test1.in
    echo "15" > test1.out
    echo "0 0" > test2.in
    echo "0" > test2.out
popd

mkdir -p /home/judge/log
mkdir -p /home/judge/run0
mkdir -p /home/judge/run1
mkdir -p /home/judge/run2
mkdir -p /home/judge/run3
cd HZNUOJ/judger/install
cp java0.policy  judge.conf /home/judge/etc
chown -R judge /home/judge
chgrp -R $APACHEUSER /home/judge/data
chgrp -R root /home/judge/etc /home/judge/run?
chmod 771 -R /home/judge /home/judge/data /home/judge/etc /home/judge/run?
chmod 570 /home/judge/etc/judge.conf

#boot up judged
cp /usr/bin/judged /etc/init.d/judged
chmod +x  /etc/init.d/judged
ln -s /etc/init.d/judged /etc/rc3.d/S93judged
ln -s /etc/init.d/judged /etc/rc2.d/S93judged

judged
# change apache server root to /var/www/web
sed -i -e 's/\/var\/www\/html/\/var\/www\/web/g' /etc/apache2/sites-available/000-default.conf
/etc/init.d/apache2 restart

echo "Install HZNUOJ successfuly!"
echo "Please modify your database account for HZNUOJ in:"
echo "/var/www/web/OJ/include/static.php"
echo "/home/judge/etc/judge.conf"
echo ""
echo "Enjoy ;)"
