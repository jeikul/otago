#!/bin/sh
if [ -z $1 ]
then
	cd ~/otago/data/`date +%Y/%m/%d`
	~/util/sh/replace.sh __`date +%Y%m%d`.xls "&hellip;" "..."
	cd ~/otago/src/bi
	php import.php -r tiande -c mc -s mc
	php import.php -r tiande -c beike -s 3n
	php import.php -r tiande -c self -s 3n
	php import.php -r tiande -c yf -s 3n
	php import.php -r gaozhi -c mtpos -s mtpos
	php import.php -r gaozhi -c mini -s 3n
	php import.php -r gaozhi -c yf -s 3n
	php import.php -r gaozhi -c youfan -s youfan
	php import.php -s myt
	php daily.php 
else
  arg=$1
	cd ~/otago/data/${arg:0:4}/${arg:5:2}/${arg:8:2}
	~/util/sh/replace.sh __${arg:0:4}${arg:5:2}${arg:8:2}.xls "&hellip;" "..."
	cd ~/otago/src/bi
	php import.php -d $1 -r tiande -c mc -s mc
	php import.php -d $1 -r tiande -c beike -s 3n
	php import.php -d $1 -r tiande -c self -s 3n
	php import.php -d $1 -r tiande -c yf -s 3n
	php import.php -d $1 -r gaozhi -c mtpos -s mtpos
	php import.php -d $1 -r gaozhi -c mini -s 3n
	php import.php -d $1 -r gaozhi -c yf -s 3n
	php import.php -d $1 -r gaozhi -c youfan -s youfan
	php import.php -d $1 -s myt
	php daily.php -d $1
fi
