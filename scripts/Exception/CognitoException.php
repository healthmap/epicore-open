<?php

if (file_exists("/usr/share/php/vendor/autoload.php")) {

}
require_once '../vendor/autoload.php';

use Aws\Credentials\CredentialProvider;
use Aws\Exception;

/**
 * Class CognitoException
 */
class CognitoException extends \Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException
{

}