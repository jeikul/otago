#!/bin/sh
cd ~/otago/data/`date +%Y/%m/%d`
~/util/sh/replace.sh __`date +%Y%m%d`.xls "&hellip;" "..."
cd ~/otago/src/bi
php import.php -r tiande -c mc -s mc
php import.php -r tiande -c beike -s 3n
php import.php -r tiande -c self -s 3n
php import.php -r gaozhi -c mtpos -s mtpos
php import.php -r gaozhi -c mini -s 3n
php import.php -r gaozhi -c yf -s 3n
php import.php -s myt
php daily.php 
