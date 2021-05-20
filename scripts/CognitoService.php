<?php

if (file_exists("/usr/share/php/vendor/autoload.php")) {

}
require_once '../vendor/autoload.php';

use Aws\CognitoIdentity\CognitoIdentityClient;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Credentials\CredentialProvider;
use Aws\Ses\SesClient;
use Aws\CognitoIdentity;


class CognitoService
{
    private $client;
    private $clientId;
    private $userPoolId;

    public function __construct()
    {
        $provider = CredentialProvider::defaultProvider();
        $this->client = new CognitoIdentityProviderClient([
            'version' => 'latest',
            'profile' => 'default',
            'region' => 'us-east-2',
            'credentials' => $provider
        ]);

        $this->clientId= '26qu1mhe12dso7o8jjiuthla6v';
        $this->userPoolId = 'us-east-2_1DeSl642y';
    }
    public function singUp(string $username , string $password , string $email)
    {
        $this->client->signUp([
            'ClientId' => $this->clientId,
            //      'SecretHash' => '7785fp7u39011i8pifej81hh5ato5vcs44a4jhppp15ifl3616g    ',
            'Username' => $username,
            'Password' => $this->encryptPassword($password),
            'UserAttributes' => [
                [
                    'Name' => 'name',
                    'Value' => $username
                ],
                [
                    'Name' => 'email',
                    'Value' => $email
                ]
            ]
        ]);
    }
    public function login(string $username , string $password)
    {
        //$sing = $this->client->get();
        //var_dump($sing);die();

        $result = $this->client->InitiateAuth([
            'AuthFlow' => 'USER_PASSWORD_AUTH',
            'ClientId' => $this->clientId,
            'UserPoolId' => $this->userPoolId,
            'AuthParameters' => [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ],
        ]);

    }
    public function sendPasswordResetMail(string $username) : string
    {
        try {
            $this->client->forgotPassword([
                'ClientId' => $this->client_id,
                'Username' => $username
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return '';
    }

    public function resetPassword(string $code, string $password, string $username) : string
    {
        try {
            $this->client->confirmForgotPassword([
                'ClientId' => $this->client_id,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'Username' => $username
            ]);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return '';
    }

    protected function encryptPassword(string $password)
    {
        return md5($password);
    }


}