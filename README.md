# Epicore

## Development Environment

> Repo Url:https://github.com/healthmap/epicore-ng.git
> Requires PHP 7.3.11 or greater
> MySQL 

## Development package managers
 - npm (for dev only used for php-server and some python modules)
 - composer (php modules - check composer.json)

## Using vlucas/phpdotenv
 - configure your .env as per .env.example (see 1P for hooks) 

## Note:
if pbkdf2.php is unavailable download from:
https://defuse.ca/php-pbkdf2.htm
defuse.cadefuse.ca
PBKDF2 Password Hashing for PHP
Standards compliant PBKDF2 implementation for PHP.


## Local Development without docker
    You'll need the following software installed to get started.

    - [Node.js](http://nodejs.org): Use the installer for your OS.
    - [Git](http://git-scm.com/downloads): Use the installer for your OS.
    - Windows users can also try [Git for Windows](http://git-for-windows.github.io/).

    ## Getting Started

    Clone this repository.

    Open Terminal
    > git clone https://github.com/healthmap/epicore.git
    > cd epicore
    > npm install (for npm dependencies)
    > composer install 

    # Install Composer if (composer not available)
    sudo curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

    # AWS cli - please refer to the software development handbook for recommendations on installation and configuration

## Update ,env file using the .env.example file

## To run locally
> Open terminal
cd ~epicore
npm start

 Browser points to: http://127.0.0.1:8000/#/

> Get files from developer if running as ini.php:
~/epicore/scripts/conf/da.ini.php

## Local Development as docker using ECR

    1. Edit docker-compose.yml to use Dockerfile

    2. Configure AWS on your local environment to the role:EPICORE-NONPROD having access to the healthmap account.
    - Assigning a API Scretekey to AWS using AWS config on local machine
    - Create folder under root 
    - /.aws
    - /.aws/config
    - /.aws/credentials
    Please add config details and credentials for AWS here with the Epicore-NonProd role
    vi ~/.aws/config
    [EPICORE-NONPROD]
    role_arn = 
    region = us-east-1
    source_profile = default

    3. Install Docker (download from https://docs.docker.com/get-docker/, homebrew formulae)

    *** change vendor./autoload.php path *** 
    docker entrypoint would install all composer under /usr/share/php/ If this is incorrect change path accordingly in
    File: AWSMail.class.php
    File: db.function.php
    File: const.inc.php

    cd ~/epicore
    export AWS_PROFILE=EPICORE-NONPROD
    docker-compose build
    docker-compose up -d

## Local Development without ECR/AWS account setup(For contractors and other orgs that do not require AWS account setup)

    1. Edit docker-compose.yml to use Dockerfile_local

    2. Edit entrypoint.sh
        Comment below lines, as local development will not access parameter file from AWS. Will be using .env directly
        #aws ssm get-parameters-by-path --path "/" > ssm_parameters.txt                                                              
        #cat ssm_parameters.txt | jq -r '.[] |  map("\(.Name)=\(.Value|tostring)")'  |  sed 's/"//g' | sed 's/.$//' > .env    
        #rm -rf ssm_parameters.txt

    3. Build/Run docker
        cd ~/epicore
        docker-compose build
        docker-compose up -d

## Flyway migrations
Database migrations are handled via npm package node-flywaydb
Flyway config: ~/epicore/flyway/conf/flyway.js
Modify .env file to make sure migrations are run on the right platform

### Adding new mirations
All new migrations must follow the norms as follows

~/flyway/release-X.X
~/flyway/release-X.X/migrations
Files in this folder will conform to the following nature
versioned migration (V), an undo migration (U), or a repeatable migration (R)
Vrelease-number_version-number_version_number__description.sql
Note:'__' is the default separator.
Examples: V1_0_1__alter_table_hm_ticket.sql
V1_0_1__create_table_role.sql

~/flyway/release-X.X/pre-migrations (these are run manually as this will be one time)

### Running migrations
The migrations can be tested on a local database of choice before committing the changes. Migration files are automatically picked up by Jenkins and migration scripts are executed as part of the migrations
**Note**: Always start with a flyway-baseline as the first command before migrations
To run locally: please see package.json
> npm run flyway-info
> npm run flyway-baseline
> npm run flyway-migrate

## Cypress Tests
Cypress is included as a dev dependency and is part of our package.json
To run cypress tests:
> Edit the cypress.json file accordingly before running any test
> Make sure to run a local instance of epicore
> On another terminal start cypress
> npx cypress open

This will bring open the test suite. You can run each file under ~/epicore/cypress/tests/e2e/integration individually or all.

Add additional tests to ~epicore/cypress/tests/e2e/integration


##########################################################
### OLD CONFIGURATION SETUP FOR DEV/PROD EPICORE-V1-V2
##########################################################

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

### Epicore-V3 EKS Cron jobs

### Email Reminders Cron job
    Email reminders are sent for auto-close and warning notifications for RFIs.

### Member and RFI stats Cron job

    1. Member and RFI stats are saved weekly in a CSV file. See scripts:
    epicore/crontab/getMembers.sh
    epicore/crontab/getRFIstats.sh

    2. Stats for the member and RFI dashboards are generated weekly with two python scripts:
    epicore/scripts/rfi_metrics.py
    epicore/scripts/responder_metrics2.py

    These scripts use data from the csv files in #1.

#### How To add new Cronjob-EKS
    
    Files: create-cron-jobs.sh, jobs.sh, jobs.txt
    1. Add new job to jobs.txt
        Format: cron-job-name, job-name-to-select,schedule
        Example: get-members-weekly,getMembers,30 1 * * 1
    2. Add entry to jobs.sh
       if [ "$jobName" = "getMembers" ]; then
        php ./downloadMembers.php
    3. To deploy: 
        > export AWS_PROFILE=EPICORE-NONPROD
        > cd /epicore/deploy
        > ./create-cron-jobs.sh (creates only the yml files) - check if all correct
        > ./create-cron-jobs.sh deploy
        This creates a /deploy/cron-jobs .yml file for each job listed in the jobs.txt which are used to create the jobs on the EKS.
        Note: Do not checkin the .yml files.
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

## Updating ECR image for docker please refer to handbook aws-ecr page

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


