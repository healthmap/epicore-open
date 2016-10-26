## Deployment

Epicore uses Deploybot (https://deploybot.com/) for deployment to both development and production environments.

### Development Environment

The development URL is at https://epicore.org/~dev

All of your commits to the epicore git repo are automatically deployed to the development environment, so you don't need to do anything to see your changes.  

The development environment is identical to the  production environment, except it uses a test database.  This allows testing of all functionality without affecting production.

Development deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/23779030056625/85433.svg)](http://deploybot.com)


### Production Environment

Only authorized users can deploy to the production environment using the Deploybot dashboard.

Production deployment status: [![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/02267418033975/87596.svg)](http://deploybot.com)



## Configuration

 
The following steps must be done after the epicore root directory is created from git clone or for the root directory on a web server.


### Copy data files and set permissions

```sh
sudo cp -R /var/www/html/dev.epicore.org/data/ epicore_root/.

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

### Create and copy config file to scripts/conf dir

```sh
cd epicore_root/scripts

mkdir conf

cp da.ini.php epicore_root/scripts/conf/.

*Contact Epicore admin for a copy of the da.ini.php file.

```
