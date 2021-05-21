#!/usr/bin/env sh
set -e

aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/.$//' > .env    
rm -rf ssm_parameters.txt

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


cd /var/www/html
cat .env
cd scripts
jobName=$1

if [ "$jobName" = "getMembers" ]; then
     php ./downloadMembers.php
elif [ "$jobName" = "getRFIstats" ]; then
	 php ./downloadEventStats.php
elif [ "$jobName" = "getRFIMetrics" ]; then
	 python ./rfi_metrics.py
else
    echo "No Job found with name $jobName"
fi


