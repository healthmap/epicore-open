<?php

require_once (dirname(__FILE__) ."/CognitoService.php");
require_once (dirname(__FILE__) ."/IAuthService.php");
require_once(dirname(__FILE__) . "/../Model/UserCognitoType.php");
require_once(dirname(__FILE__) . "/../Exception/LoginException.php");
require_once(dirname(__FILE__) . "/../Exception/LoginPasswordAttemptsExceededException.php");
require_once(dirname(__FILE__) . "/../Exception/UserAccountExistException.php");
require_once(dirname(__FILE__) . "/../Exception/UserIsConfirmed.php");

class AuthService implements IAuthService
{
    /**
     * @var CognitoService
     */
    private $cognitoService;

    /**
     * AuthService constructor.
     */
    public function __construct(){
        $this->cognitoService = new CognitoService();
    }

    /**
     * @todo valid existing token
     * @param string $token
     */
    public function ValidToken(string $token){

    }

    /**
     * @param string $username
     * @param string $password
     * @return UserAuthResponse
     * @throws Exception
     */
    public function LoginUser(string $username, string $password): UserAuthResponse
    {
        try
        {
            return $this->cognitoService->login($username , $password);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();
            if($exceptionMessage['message'] === "Incorrect username or password.")
            {
                throw new \LoginException($exceptionMessage['message']);
            }
            if($exceptionMessage['message'] === "Password attempts exceeded")
            {
                throw new \LoginPasswordAttemptsExceededException($exceptionMessage['message']);
            }

            throw new \Exception('Login process error');
        }
    }

    /**
     * @throws Exception
     */
    public function SingUp(string $username, string $password, string $email)
    {
        try
        {
            $this->cognitoService->singUp($username, $password, $email);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();
            if($exceptionMessage['message'] === "User account already exists")
            {
                throw new \UserAccountExistException($exceptionMessage['message']);
            }

            throw new \Exception($exceptionMessage['message']);
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $code
     * @throws Exception
     */
    public function UpdatePassword(string $username , string $password , string $code)
    {
        try
        {
            $this->cognitoService->confirmPassword($username , $password , $code);
        }
        catch (Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param string $username
     */
    public function ForgotPassword(string $username)
    {
        try
        {
            $this->cognitoService->forgotPassword($username);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @throws UserIsConfirmed
     * @return bool
     * @throws Exception
     */
    public function ConfirmAccount(string $username , string $password , string $newPassword ): bool
    {
        try
        {
            $this->LoginUser($username , $password);
            return true;
        }
        catch (\NewPasswordException $exception)
        {
            $user = $this->cognitoService->adminGetUser($username);
            if(!empty($user))
            {
                if($user['UserStatus'] === "CONFIRMED")
                {
                    throw new \UserIsConfirmed('User is CONFIRMED');
                }
            }
            $this->cognitoService->adminSetUserPassword($username, $newPassword);
            return true;
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();

            throw new \Exception($exceptionMessage['message']);
        }
    }

}