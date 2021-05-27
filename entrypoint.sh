#!/usr/bin/env sh
set -e

aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/.$//' > .env    
rm -rf ssm_parameters.txt

pip3 install -r requirements.txt


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
nginx -g "daemon off;"