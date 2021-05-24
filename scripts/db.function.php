<?php
/**
 * db.function.php
*/

function getDB($which = '')
{


    static $db;
    if(!is_object($db)) {

        require_once(dirname(__FILE__).'/DB/DB.php');
        require_once '/usr/share/php/vendor/autoload.php'; 
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '../../');
        $dotenv->load();
        $epicore_db = $_ENV['epicore_db'];
        $dsn = array(
            'phptype'  => $_ENV['phptype'] ,
            'username' => $_ENV['username'],
            'password' => $_ENV['password'],
            'hostspec' => $_ENV['hostspec'],
            'database' =>  $_ENV['database'] 
        );
        // echo '>>>>>>:';
        // echo $dsn;
        // echo '>>>>>>:';

        $which = $which ? $which : $epicore_db ;
        $db =& DB::connect($dsn);
        if ($which == $epicore_db)
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

