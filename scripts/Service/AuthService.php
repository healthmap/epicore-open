<?php

require_once (dirname(__FILE__) ."/CognitoService.php");

class AuthService
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
    public function validToken(string $token){
        if(!empty($token)){

        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return UserAuthResponse
     */
    public function loginUser(string $username, string $password): UserAuthResponse
    {
        try
        {
            $this->cognitoService->login($username , $password);
        }
        catch (Exception $exception)
        {
            print_r($exception->getCode());
            print_r('mam cie');die();
        }
    }

    /**
     * @throws Exception
     */
    public function singUp(string $username, string $password, string $email)
    {
        try
        {
            $this->cognitoService->singUp($username, $password, $email);
        }
        catch (Exception $exception)
        {

        }
    }

}