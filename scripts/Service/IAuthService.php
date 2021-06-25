<?php

/**
 * Interface IAuthService
 */
interface IAuthService
{
    public function ValidToken(string $token);
    public function RevokeToken(string $username);
    public function LoginUser(string $username, string $password);
    public function SingUp(string $username, string $password, bool $dontSendEmail = false , bool $dontUpdatePassword = false);
    public function UpdatePassword(string $username , string $password , string $code);
    public function ForgotPassword(string $username);
    public function ConfirmAccount(string $username , string $password , string $newPassword);
    public function DeleteUser(string $username);
}