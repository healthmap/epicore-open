# Epicore



### Clone Epicore repository

### set permissions on data files

```sh
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

```

### Set app version and mode

Edit epicoreConfig.js

