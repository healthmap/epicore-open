# Epicore

## Development Environment

> Repo Url:https://github.com/healthmap/epicore
> Requires PHP5.5.x
> MySQL

> Get files from developer:
~/epicore/scripts/pbkdf2.php
~/epicore/scripts/conf/da.ini.php

## Note:
if pbkdf2.php is unavailable download from:
https://defuse.ca/php-pbkdf2.htm
defuse.cadefuse.ca
PBKDF2 Password Hashing for PHP
Standards compliant PBKDF2 implementation for PHP.

## Local Development without email client running
In order to run a local set up, we will need to **comment** a few lines of code in the following files:
File: AWSMail.class.php
Line#: 4 require_once '/usr/share/php/vendor/autoload.php';

File: EvenInfo.class.php
Line#: 813 file_put_contents("../$file_preview", $emailtext);

> Open terminal
cd ~epicore
npm install
npm start

## Local Development with email client running
Follow set up below on how email client is setup on the server and follow the same on local


### Install library for Mobile Push Notifications (optional)

```sh
cd epicore_root
composer require sly/notification-pusher
```

*see https://github.com/Ph3nol/NotificationPusher

## Deployment

Epicore uses Deploybot (https://deploybot.com/) for deployment to both staging and production servers. For access please contact your PM.

### Staging (aka DEV)

The staging URL is at https://dev.epicore.org/#/

All of your commits to the epicore git repo are automatically deployed to staging, so you don't need to do anything to see your changes.  

The staging deployment is identical to production , except it uses a test/dev database.  This allows testing of all functionality without affecting 

production.

Staging deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/23779030056625/85433.svg)](http://deploybot.com)


### Production

Only authorized users can deploy to production using the Deploybot dashboard.

Production deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/02267418033975/87596.svg)](http://deploybot.com)



## Approval Portal

Only superusers can access the approval portal (with username and pw). Supersers are added in the da.ini.php config file.


## Cron Jobs

All cron jobs run in root.  There are two sets of cron jobs described below.

### Email Reminders

Email reminders are sent for auto-close and warning notifications for RFIs.


### Member and RFI stats

1. Member and RFI stats are saved weekly in a CSV file. See scripts:
epicore/crontab/getMembers.sh
epicore/crontab/getRFIstats.sh

2. Stats for the member and RFI dashboards are generated weekly with two python scripts:
epicore/scripts/rfi_metrics.py
epicore/scripts/responder_metrics2.py

These scripts use data from the csv files in #1.



### DB Schema changes as of 10/9/2020

The old codebase for epicore used 2 schemas - hm and epicore (see da.ini.php old files). 

Going forward we have now consolidated da.ini.php file to hold only one schema access - epicore. hm schema was used to access table 'hmu' to fetch logged in 

user or insert new users only. Going forward epicore will have access to schema hm for (SELECT. INSERT ONLY QUERIES).


### Responder Login
To get a responder account, please “register” for an account on Epicore (www.epicore.org) / (www.dev.epicore.org) in respective environments

### Requester Login
To get a requester account, please “register” for an account on HealthMap (www.healthmap.org). Since dev healthmap no longer exists, in order to create dev-requester logins:
Step1: Please use script ~/scripts/createRequester.php to create users.
params:
@auth
@callback
@email
@name
@title
@username
@ppassword
@default_location
@default_locname
@createdate
@superuser

SampleURL:
http://127.0.0.1:8000/scripts/createRequester.php?auth=true&callback=angular.callbacks._0&email=lakshmi.yajurvedi@chboston.org&name=Lakshmi Yajurvedi&title=Epicore User&username=lyajurvedi&password=HealthMap0002&default_location=44.276332228 ,-105.503164654&default_locname=Gillete, WY 82717, USA&createdate=2021-02-16&superuser=true

Step2: Using the above users email address, add this user as a Requester into Epicore, using the member portal
Login to epicore as a superuser
Requester Portal
Add Requester

## Upgrading AWS SES Client (Mail client)
Epicore as of 2021-03-17 and before was using AWS SesClient version V1 for PHP
Steps for Upgrade:
Login to DEV EC2:
> cd /usr/share/php$ composer --version
> composer require aws/aws-sdk-ph
> cd /usr/share/php/vendor (should have aws folder and other things installed here)
> add aws config to root folder
    create dir .aws/
    create file .aws/config
    create file .aws/credentials
* Add appropriate sercrete keys in the above files

Now open file AWSMail.class.php and edit line #4 for the path to the vendor folder (this is where the autoload.php is)

To test locally use: testMail.php

<!-- 
OLD EPICORE VERSION-1-2 Reference docs

The development evenironment is set up in the user's sandbox on the server in /home/username/public_html.

The user must be added to the sandboxes in NGINX:
/etc/nginx/sites-available/default

Be sure to restart nginx.

### Clone Epicore repository in user's sandbox

```sh
cd /home/username/public_html
git clone https://github.com/healthmap/epicore.git

```


**Important**: You can view your changes at https://epicore.org/~username/epicore/ after completing the following steps.


### Copy data files and set permissions

```sh
sudo cp -R /var/www/html/dev.epicore.org/data/ /home/username/public_html/epicore/.

cd epicore_root/data

sudo chown www-data:sudo *.csv
```

### Create temp directory in emailtemplates

```sh
cd epicore_root

cd emailtemplates

sudo mkdir temp

cd temp

sudo mkdir response

sudo mkdir rfi

sudo chown www-data:sudo *
```

### Create and copy config files to scripts/conf dir

```sh
cd epicore_root/scripts

mkdir conf

cp da.ini.php epicore_root/scripts/conf/.
cp push-epicore.pem epicore_root/scripts/conf/.

```
*Contact Epicore admin for a copy of the config files.
These files are in the staging and production directories on the server. -->


