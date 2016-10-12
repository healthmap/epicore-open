
## Additional Setup

destination_dir is the git cloned directory.

### copy data files and set permissions

sudo cp -R /var/www/html/epicore.org/data/ destination_dir/

cd destination_dir/data

sudo chown www-data:sudo *.csv

### set permissions for temp directory

cd destination_dir

sudo mkdir temp

cd temp

sudo mkdir response

sudo mkdir rfi

sudo chown www-data:sudo *

### copy config file to scripts/conf dir

scripts/conf/da.ini.php

