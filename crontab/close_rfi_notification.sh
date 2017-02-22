#!/bin/sh
cd /var/www/html/prod.epicore.org/scripts/
php closeNotification.php 2>&1 >> /var/www/html/prod.epicore.org/crontab/close_rfi.log