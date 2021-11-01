<?php

/**
 * Class CognitoErrors
 */
class CognitoErrors
{
    public const accountExists = 'User account already exists.';
    public const accountNotExists = 'User does not exist.';
    public const incorectUserNameOrPassword = 'Incorrect username or password.';
    public const passwordAttemptsExceeded = 'Password attempts exceeded';
    public const noEmailProvided = 'No email provided but desired delivery medium was Email';
    public const confirmed = 'CONFIRMED';
    public const invalidCode = 'Invalid code provided, please request a code again.';
    public const cantResetPassword = 'User password cannot be reset in the current state.';
}