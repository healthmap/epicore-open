<?php
/**
 * db.function.php
 * Sue Aman 2014-01-31
 */

require_once('const.inc.php');

if(ENVIRONMENT == 'Local'){
    require_once(dirname(__FILE__).'/DB/DB.php');
} else {
    require_once('DB.php');
}

function getDB($which = '')
{
    static $db;
    if(!is_object($db)) {
        $opts = parse_ini_file(dirname(__FILE__) . '/conf/da.ini.php', true);
        $which = $which ? $which : 'epicore_db';
        $dsn = $opts[$which];
        $db =& DB::connect($dsn);
        if ($which == 'epicore_db')
            $db->connection->set_charset("utf8");
        if (PEAR::isError($db)) {
            //print_r($db);
            die('Cant connect to database as normal user');
        } else {
            $db->setFetchMode(DB_FETCHMODE_ASSOC);
            $db->autoCommit(false);
        }
    }
    return $db;
}

?>
