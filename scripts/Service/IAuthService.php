<?php

/**
 * Interface IAuthService
 */
interface IAuthService
{
    public function ValidToken(string $token);
    public function LoginUser(string $username, string $password);
    public function SingUp(string $username, string $password, string $email);
    public function UpdatePassword(string $username , string $password , string $code);
    public function ForgotPassword(string $username);
    public function ConfirmAccount(string $username , string $password , string $newPassword);
}