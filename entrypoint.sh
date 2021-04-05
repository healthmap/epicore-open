#!/usr/bin/env sh
set -e

# run composer to set up dependencies if not already there...
if ! [ -e vendor/autoload.php ]; then
    
    echo >&2 "installing dependencies with Composer"
    cd /usr/share/php
    composer install
else
    echo >&2 "vendor dependencies already in place, updating."
    cd /usr/share/php
    composer update
fi


# start PHP
php-fpm -D

# start the nginx in the foreground
nginx -g 'daemon off;'