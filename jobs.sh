#!/usr/bin/env sh
set -e

aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/,$//' | sed 's/$//'  | sed '/^[[:space:]]*$/d' | sed '/^[[:space:]]*$/d' | sed "s/^[ \t]*//" | sed '1d; $d' > .env

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


cd /var/www/html
cat .env

cd scripts
jobName=$1

if [ "$jobName" = "getMembers" ]; then
     php ./downloadMembers.php
elif [ "$jobName" = "getRFIstats" ]; then
	 php ./downloadEventStats.php
elif [ "$jobName" = "getRFIMetrics" ]; then
	 python3 ./rfi_metrics.py
elif [ "$jobName" = "getResponderMetrics" ]; then
	 python3 ./responder_metrics2.py     
elif [ "$jobName" = "autoCloseInactiveEvents" ]; then
	 php closeInactiveEvents.php
elif [ "$jobName" = "closeInactiveEvents" ]; then
	 php closeInactiveEvents.php
elif [ "$jobName" = "closeActiveEvents" ]; then
	 php closeActiveEvents.php
elif [ "$jobName" = "closeWarning" ]; then
	 php closeWarning.php



else

    echo "No Job found with name $jobName"
fi


