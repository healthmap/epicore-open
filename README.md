## Deployment

Epicore uses Deploybot for deployment to development and production environments.

### Development Environment

```sh
All commits to the epicore git repo are automatically deployed to the development environment.

The development URL is at https://epicore.org/~dev

[![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/23779030056625/85433.svg)](http://deploybot.com)

```

### Production Environment

```sh
Deployment to the production environment is manual and must be done by authorized users from the Deploybot dashboard.

[![Deployment status from DeployBot](https://boston-childrens-hosptial.deploybot.com/badge/02267418033975/87596.svg)](http://deploybot.com)

```


## Configuration and additional setup

```sh
epicore_root is the epicore root directory.
 
The following steps must be done after the epicore root directory is created from git clone or for the root directory on a web server.

```

### copy data files and set permissions

```sh
sudo cp -R /var/www/html/prod.epicore.org/data/ epcicore_root/.

cd epicore_root/data

sudo chown www-data:sudo *.csv
```

### create temp directory in emailtemplates

```sh
cd epicore_root

cd emailtemplates

sudo mkdir temp

cd temp

sudo mkdir response

sudo mkdir rfi

sudo chown www-data:sudo *
```

### create and copy config file to scripts/conf dir

```sh
cd epicore_root/scripts

mkdir conf

cp da.ini.php epicore_root/scripts/conf/.

```
