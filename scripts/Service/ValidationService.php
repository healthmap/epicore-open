<?php

require_once(dirname(__FILE__) . "/../Exception/PasswordValidationException.php");
require_once(dirname(__FILE__) . "/../Exception/EmailValidationException.php");

if (file_exists("/usr/share/php/vendor/autoload.php")) {
    require_once '/usr/share/php/vendor/autoload.php';
}

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

class ValidationService
{
    /**
     * @param User $user
     * @throws PasswordValidationException
     */
    public function password(User $user): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($user->getPassword(), [
            new Length(['min' => 6]),
            new NotBlank(),
            new PositiveOrZero()
        ]);

        if (0 !== count($violations))
        {
            throw new PasswordValidationException('Please check password.The password is not valid');
        }
    }

    /**
     * @param User $user
     * @throws EmailValidationException
     */
    public function email(User $user): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($user->getEmail(), [
            new Email(),
            new NotBlank(),
        ]);

        if (0 !== count($violations))
        {
            throw new EmailValidationException('Please check email.');
        }
    }

}