Notes

Git clone
example. git clone https://github.com/healthmap/epicore.git destination_dir

Additional steps

1. copy data files and set permissions

sudo cp -R /var/www/html/epicore.org/data/ destination_dir/

cd destination_dir/data

sudo chown www-data:sudo *.csv

2. set permissions for temp directory

cd destination_dir

sudo mkdir temp

cd temp

sudo mkdir response

sudo mkdir rfi

sudo chown www-data:sudo *

3. point to live database, if desired

sudo vi epicore.org/scripts/da.ini.php

database = epicore

4. change EPICORE_URL to make email work with new_url, if desired

example:

sudo vi scripts/const.inc.php

define('EPICORE_URL', 'http://epicore.org/new_url');
