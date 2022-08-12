#!/bin/sh
cd ~/otago/data/`date +%Y/%m/%d`
~/util/sh/replace.sh __`date +%Y%m%d`.xls "&hellip;" "..."
cd ~/otago/src/bi
php import.php -d $1 -r tiande -c mc -s mc
php import.php -d $1 -r tiande -c self -s 3n
php import.php -d $1 -r gaozhi -c mtpos -s mtpos
php import.php -d $1 -r gaozhi -c mini -s 3n
php import.php -d $1 -s myt
php daily.php -d $1
