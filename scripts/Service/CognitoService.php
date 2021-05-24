<?php

require_once(dirname(__FILE__) . "/../Model/UserSignUpResponse.php");

if (file_exists("/usr/share/php/vendor/autoload.php")) {

}
require_once '../vendor/autoload.php';

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Credentials\CredentialProvider;
use Aws\Exception;


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

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @return UserSignUpResponse
     * @throws \Exception
     */
    public function singUp(string $username , string $password , string $email)
    {
        try {
            $result = $this->client->signUp([
                'ClientId' => $this->clientId,
                'Username' => $username,
                'Password' => $password,
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
            if (!is_null($result)) {
                $result = $result->toArray();

                $userSignUpResponse = new UserSignUpResponse();
                $userSignUpResponse->setUserConfirmed($result['UserSignUpResponse']);
                $userSignUpResponse->setUserSub($result['UserSub']);

                return $userSignUpResponse;
            }

            throw new \Exception('Wrong AWS response');

        }catch (Exception\AwsException $ex){
            throw new $ex;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return UserAuthResponse
     * @throws \Exception
     */
    public function login(string $username , string $password): UserAuthResponse
    {
        try {
            $result = $this->client->InitiateAuth([
                'AuthFlow' => 'USER_PASSWORD_AUTH',
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->userPoolId,
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                ],
            ]);

            if(!is_null($result)) {
                $result = $result->toArray();

                $userAuthResponse = new UserAuthResponse();

                $userAuthResponse->setAccessToken($result['AuthenticationResult']['AccessToken']);
                $userAuthResponse->setRefreshToken($result['AuthenticationResult']['RefreshToken']);
                $userAuthResponse->setExpiresIn($result['AuthenticationResult']['ExpiresIn']);
                $userAuthResponse->setTokenType($result['AuthenticationResult']['TokenType']);
                $userAuthResponse->setTokenId($result['AuthenticationResult']['IdToken']);

                return $userAuthResponse;
            }

            throw new \Exception('Wrong AWS response');

        } catch ( \Exception $exception){
            throw $exception;
        }
    }

    /**
     * @param string $username
     * @return string
     */
    public function sendPasswordResetMail(string $username) : string
    {
        try {
            $this->client->forgotPassword([
                'ClientId' => $this->client_id,
                'Username' => $username
            ]);

            return true;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $code
     * @param string $password
     * @param string $username
     * @return string
     */
    public function resetPassword(string $code, string $password, string $username) : string
    {
        try {

            $this->client->confirmForgotPassword([
                'ClientId' => $this->client_id,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'Username' => $username
            ]);

            return true;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }




}