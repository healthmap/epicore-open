<?php

/**
 * Class UserSignUpResponse
 */
class UserSignUpResponse
{
    private $userConfirmed;
    private $userSub;

    public function getUserConfirmed(){
        return $this->userConfirmed;
    }

    public function setUserConfirmed(bool $status): UserSignInResponse
    {
        $this->userConfirmed = $status;
        return $this;
    }

    public function getUserSub(){
        return $this->userSub;
    }

    /**
     * @param string $key
     * @return UserSignInResponse
     */
    public function setUserSub(string $key): UserSignInResponse
    {
        $this->userSub = $key;
        return $this;
    }
}