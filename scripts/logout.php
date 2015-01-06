<?
exit;
// expire the cookie if there is one
if(isset($_COOKIE['Epicore'])) {
    $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
    foreach($_COOKIE['Epicore'] as $cookiekey => $cookieval) {
        setcookie("Epicore[$cookiekey]", '', time()-1, '/', $domain, false);
    }
    unset($_COOKIE['Epicore']);
}
unset($_COOKIE);

session_start();
foreach($_SESSION as $key => $val) {
    unset($_SESSION[$key]);
}
session_destroy();
header("Location: ../");
?>
