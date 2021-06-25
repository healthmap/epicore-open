<?php

require_once (dirname(__FILE__) ."/CognitoService.php");
require_once (dirname(__FILE__) ."/ValidationService.php");
require_once (dirname(__FILE__) ."/IAuthService.php");
require_once(dirname(__FILE__) . "/../Model/UserCognitoType.php");
require_once(dirname(__FILE__) . "/../Model/User.php");
require_once(dirname(__FILE__) . "/../Exception/LoginException.php");
require_once(dirname(__FILE__) . "/../Exception/LoginPasswordAttemptsExceededException.php");
require_once(dirname(__FILE__) . "/../Exception/UserAccountExistException.php");
require_once(dirname(__FILE__) . "/../Exception/PasswordValidationException.php");
require_once(dirname(__FILE__) . "/../Exception/UserIsConfirmed.php");
require_once(dirname(__FILE__) . "/../Exception/UserAccountNotExist.php");
require_once(dirname(__FILE__) . "/../Exception/InvalidCodeException.php");
require_once(dirname(__FILE__) . "/../Exception/CognitoException.php");

class AuthService implements IAuthService
{
    /**
     * @var CognitoService
     */
    private $cognitoService;

    /**
     * @var ValidationService
     */
    private $validationService;

    /**
     * AuthService constructor.
     */
    public function __construct()
    {
        $this->cognitoService = new CognitoService();
        $this->validationService = new ValidationService();
    }

    /**
     * @param string $token
     * @return bool
     * @throws CognitoException
     */
    public function ValidToken(string $token): bool
    {
        try{
            $this->cognitoService->getUser($token);
            return true;
        }
        catch (\CognitoException  $exception)
        {
            throw $exception;

        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return UserAuthResponse
     * @throws LoginException
     * @throws LoginPasswordAttemptsExceededException
     * @throws UserAccountNotExist
     * @throws PasswordValidationException
     * @throws Exception
     */
    public function LoginUser(string $username, string $password): UserAuthResponse
    {
        try
        {
            $user = new User();
            $user->setPassword($password);
            // TODO valid
            $this->validationService->password($user);

            return $this->cognitoService->login($username , $password);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();
            if($exceptionMessage['message'] === CognitoErrors::incorectUserNameOrPassword)
            {
                throw new \LoginException($exceptionMessage['message']);
            }
            if($exceptionMessage['message'] === CognitoErrors::passwordAttemptsExceeded)
            {
                throw new \LoginPasswordAttemptsExceededException($exceptionMessage['message']);
            }
            if($exceptionMessage['message'] === CognitoErrors::accountNotExists)
            {
                throw new \UserAccountNotExist($exceptionMessage['message']);
            }
            throw new Exception($exceptionMessage['message']);
        }
        catch (PasswordValidationException $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool $dontSendEmail
     * @param bool $dontUpdatePassword
     * @throws NoEmailProvidedException
     * @throws PasswordValidationException
     * @throws UserAccountExistException
     */
    public function SingUp(string $username, string $password = '' , bool $dontSendEmail = false , bool $dontUpdatePassword = false)
    {
        try
        {
            if(!$dontSendEmail)
            {
                $password = $this->generatePassword();
            }

            // TODO valid password
            $user = new User();
            $user->setPassword($password);

            $this->validationService->password($user);

            //TODO send user to AWS Cognito
            $this->cognitoService->singUp($username, $password , $username , $dontSendEmail , $dontUpdatePassword);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();

            if($exceptionMessage['message'] === "User account already exists")
            {
                throw new \UserAccountExistException($exceptionMessage['message']);
            }
            if($exceptionMessage['message'] === CognitoErrors::noEmailProvided)
            {
                throw new \NoEmailProvidedException($exceptionMessage['message']);
            }
        }
        catch (\PasswordValidationException $exception)
        {
            throw $exception;
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
            $user = new User();
            $user->setPassword($password);

            // TODO validate password
            $this->validationService->password($user);

            // TODO confirm password in AWS Cognito
            $this->cognitoService->confirmPassword($username , $password , $code);
        }
        catch (Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException | PasswordValidationException $exception)
        {
            $message = $exception->toArray();
            if($message['message'] === CognitoErrors::invalidCode)
            {
                throw new InvalidCodeException($message['message']);
            }
            throw new Exception($message['message']);
        }
    }

    /**
     * @param string $username
     * @throws UserAccountNotExist
     * @throws Exception
     */
    public function ForgotPassword(string $username)
    {
        try
        {
            $this->cognitoService->forgotPassword($username);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $code = $exception->toArray();
            if($code['message']=== "Username/client id combination not found.")
            {
                throw new UserAccountNotExist($code['message']);
            }

            throw new Exception($code['message']);
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $newPassword
     * @throws PasswordValidationException
     * @throws UserIsConfirmed
     * @throws Exception
     */
    public function ConfirmAccount(string $username , string $password , string $newPassword ): void
    {
        try
        {
            $user = new User();
            $user->setPassword($password);

            // TODO valid password
            $this->validationService->password($user);

            // TODO login user by AWS Cognito
            $this->LoginUser($username , $password);
        }
        catch (\NewPasswordException $exception)
        {
            $user = $this->cognitoService->adminGetUser($username);
            if(!empty($user))
            {
                if($user['UserStatus'] === CognitoErrors::confirmed)
                {
                    throw new \UserIsConfirmed('User is CONFIRMED');
                }
            }
            $this->cognitoService->adminSetUserPassword($username, $newPassword);
        }
        catch (\Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException $exception)
        {
            $exceptionMessage = $exception->toArray();

            throw new \Exception($exceptionMessage['message']);
        }
    }

    /**
     * @param string $password
     * @return string
     */
    private function generatePassword(string $password = ''): string
    {
        if(empty($password))
        {
            $password = 'Erz2' .md5(date('Y-m-d hh:ss'));
            return substr($password , 0, 6 );
        }
        return $password;
    }

    /**
     * @param string $username
     * @return bool
     * @throws CognitoException
     */
    public function RevokeToken(string $username): bool
    {
        try{
            $this->cognitoService->revokeToken($username);
            return true;
        }
        catch (\CognitoException $exception)
        {
           throw $exception;
        }
    }

    /**
     * @param string $username
     * @return bool
     * @throws CognitoException
     */
    public function DeleteUser(string $username): bool
    {
        try{
            $this->cognitoService->deleteUser($username);
            return true;
        }
        catch (\CognitoException $exception)
        {
            throw $exception;
        }
    }
}