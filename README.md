# Epicore

## Development Environment

The development evenironment is typically set up in the user's sandbox on the server in /home/username/public_html.


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
These files are in the staging and production directories on the server.


### Install library for Mobile Push Notifications (optional)

```sh
cd epicore_root
composer require sly/notification-pusher
```

*see https://github.com/Ph3nol/NotificationPusher

## Deployment

Epicore uses Deploybot (https://deploybot.com/) for deployment to both staging and production servers.

### Staging

The staging URL is at https://epicore.org/~dev

All of your commits to the epicore git repo are automatically deployed to staging, so you don't need to do anything to see your changes.  

The staging deployment is identical to production , except it uses a test database.  This allows testing of all functionality without affecting production.

Staging deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/23779030056625/85433.svg)](http://deploybot.com)


### Production

Only authorized users can deploy to production using the Deploybot dashboard.

Production deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/02267418033975/87596.svg)](http://deploybot.com)



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
