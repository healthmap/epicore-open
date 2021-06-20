<?php

if (file_exists("/usr/share/php/vendor/autoload.php")) {

}
require_once '../vendor/autoload.php';

/**
 * Class CognitoException
 */
class CognitoException extends \Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException
{

}