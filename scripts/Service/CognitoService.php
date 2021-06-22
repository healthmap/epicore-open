<?php

require_once(dirname(__FILE__) . "/../Model/UserSignUpResponse.php");
require_once(dirname(__FILE__) . "/../Model/UserAuthResponse.php");
require_once(dirname(__FILE__) . "/../Exception/NewPasswordException.php");
require_once(dirname(__FILE__) . "/../Exception/UserAccountNotExist.php");
require_once(dirname(__FILE__) . "/../Exception/UserAccountExistException.php");
require_once(dirname(__FILE__) . "/../Exception/CognitoException.php");
require_once(dirname(__FILE__) . "/../Model/CognitoErrors.php");
require_once (dirname(__FILE__) ."/../common/AWSCredentialsProvider.php");

if (file_exists("/usr/share/php/vendor/autoload.php")) {
    require_once '/usr/share/php/vendor/autoload.php';
}
//require_once '../vendor/autoload.php';

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
        // TODO get from .env aws_clinet_id
        $this->clientId = aws_client_id;

        // TODO get from .env aws_user_pool_id
        $this->userPoolId = aws_user_pool_id;

        // TODO get from .env aws_profile
        $profile = aws_profile;

        if(empty($this->clientId) || empty($this->userPoolId) || empty($profile))
        {
      //      print('AWS Congnito .env missing');
       //     die();
        }

        $AWSCredentialsProviderInstance = AWSCredentialsProvider::getInstance();


        // TODO init CognitoIdentityProviderClient
        $this->client = new CognitoIdentityProviderClient([
            'version' => 'latest',
          //  'profile' => $profile,
            'credentials' => $AWSCredentialsProviderInstance->fetchAWSCredentialsFromRole(),
            'region' => aws_region,
        ]);
    }


    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @return void
     * @throws \Exception
     */
    public function singUp(string $username , string $password , string $email , bool $dontSendEmail = false , bool $dontUpdatePassword) : void
    {
        try
        {
            $dataContext = [
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->userPoolId,
                'Username' => $username,
                'ForceAliasCreation' => true,
                'DesiredDeliveryMediums'=> ['EMAIL'],
                'TemporaryPassword' => $password,
                'UserAttributes' => [
                    [
                        'Name' => 'name',
                        'Value' => $username
                    ],
                    [
                        'Name' => 'email',
                        'Value' => $email
                    ],
                    [
                        'Name' => 'email_verified',
                        'Value' => 'true'
                    ],

                ]
            ];

            if($dontSendEmail)
            {
                $dataContext['MessageAction'] = 'SUPPRESS';
            }

            $this->client->AdminCreateUser($dataContext);
            // TODO case for user who have already account and we do not want to change their password
            if($dontSendEmail && !$dontUpdatePassword)
            {
                $this->adminSetUserPassword($username , $password);
            }
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $error = $exception->toArray();
            if($error['message'] == CognitoErrors::accountExists)
            {
                throw new \UserAccountExistException(error['message']);
            }
            error_log($exception->getMessage());
            throw  $exception;
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
            if (isset($result['Session']))
            {
                throw new \NewPasswordException('New password action');
            }
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

            throw new \Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException('Wrong AWS response');

        }
        catch ( \Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $error = $exception->toArray();

            if($error['message'] == CognitoErrors::accountExists)
            {
                throw new \UserAccountExistException($error['message']);
            }
            if($error['message'] == CognitoErrors::accountNotExists)
            {
                throw new \UserAccountNotExist($error['message']);
            }
            error_log($exception['message']);
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
                'ClientId' => $this->clientId,
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
     */
    public function resetPassword(string $code, string $password, string $username): void
    {
        try {

            $this->client->confirmForgotPassword([
                'ClientId' => $this->clientId,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'Username' => $username
            ]);

        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $e)
        {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $code
     * @return bool
     */
    public function confirmPassword(string $username , string $password , string $code): bool
    {
        try
        {
             $this->client->ConfirmForgotPassword([
                'ClientId' => $this->clientId,
                'Username' => $username,
                'ConfirmationCode' => $code,
                'Password' => $password

            ]);
            return true;
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            error_log($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $username
     */
    public function adminForgotPassword(string $username): void
    {
        try
        {
            $result = $this->client->AdminResetUserPassword([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId
            ]);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            error_log($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param string $username
     */
    public function forgotPassword(string $username)
    {
        try
        {
             $this->client->ForgotPassword([
                'ClientId' => $this->clientId,
                'Username' => $username
            ]);
        }
        catch (Exception $exception)
        {
            error_log($exception->getMessage());
            throw $exception;
        }
    }

    public function adminSetUserPassword(string $username , string $newPassword)
    {
        try
        {
            $this->client->AdminSetUserPassword([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId,
                'Password' => $newPassword,
                'Permanent' => true
            ]);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param string $username
     * @return array
     */
    public function adminGetUser(string $username) : array
    {
        try
        {
            $result = $this->client->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->userPoolId,
            ]);
            return $result->toArray();

        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            throw $exception;
        }

    }

    /**
     * @param string $token
     */
    public function getUser(string $token): void
    {
        try
        {
            $this->client->getUser([
                'AccessToken' => $token
            ]);
        }
        catch(\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            throw $exception;
        }

    }

    /**
     * @param string $username
     * @return bool
     * @throws CognitoException
     */
    public function revokeToken(string $username): void
    {
        try
        {
            $this->client->AdminUserGlobalSignOut([
               'UserPoolId' => $this->userPoolId,
                'Username' => $username
            ]);
        }
        catch(\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $message = $exception->toArray();
            throw new \CognitoException($message['message']);
        }

    }
}