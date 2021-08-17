#!/usr/bin/env sh
set -e

aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/,$//' | sed '/^[[:space:]]*$/d' | sed '/^[[:space:]]*$/d' | sed "s/^[ \t]*//" > .env
rm -rf ssm_parameters.txt


EPICORE_USER_POOL_ID=$(aws ssm get-parameter --name epicore_user_pool_id | jq -r '.[].Value')
EPICORE_APP_CLIENT_ID=$(aws ssm get-parameter --name epicore_app_client_id | jq -r '.[].Value')
 
epicore_app_client_id_secret=$(aws cognito-idp describe-user-pool-client --user-pool-id  $EPICORE_USER_POOL_ID  --client-id $EPICORE_APP_CLIENT_ID --region us-east-1 | jq -r '.[].ClientSecret')

echo "epicore_app_client_id_secret=$epicore_app_client_id_secret" >> .env

echo "epicore_aws_region=$AWS_REGION" >> .env



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
