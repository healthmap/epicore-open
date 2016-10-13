
## Additional Setup

clone_dir is the git cloned directory.

### copy data files and set permissions

sudo cp -R /var/www/html/epicore.org/data/ clone_dir/

cd clone_dir/data

sudo chown www-data:sudo *.csv

### create temp directory in emailtemplates

cd clone_dir

cd emailtemplates

sudo mkdir temp

cd temp

sudo mkdir response

sudo mkdir rfi

sudo chown www-data:sudo *

### create and copy config file to scripts/conf dir

cp da.ini.php scripts/conf/da.ini.php
