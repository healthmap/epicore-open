<?php

class UserAuthResponse {

    private $accessToken;
    private $expiresIn;
    private $tokenType;
    private $refreshToken;
    private $tokenId;
    private $error;
    private $changepPassword;

    /**
     * @return string
     */
    public function getAccessToken(): string{
        return $this->accessToken;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setAccessToken($token):UserAuthResponse {
        $this->accessToken = $token;
        return $this;
    }

    /**
     * @param $expiresIn
     * @return $this
     */
    public function setExpiresIn($expiresIn): UserAuthResponse {
        $this->expiresIn = $expiresIn;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int {
        return $this->expiresIn;
    }

    /**
     * @return string
     */
    public function getTokenType(): string{
        return $this->tokenType;
    }

    /**
     * @param $tokenType
     * @return UserAuthResponse
     */
    public function setTokenType($tokenType): UserAuthResponse
    {
        $this->tokenType = $tokenType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken(){
        return $this->refreshToken;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setRefreshToken($token): UserAuthResponse
    {
        $this->refreshToken = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenId(){
        return $this->tokenId;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setTokenId($token): UserAuthResponse
    {
        $this->tokenId = $token;
        return $this;
    }

    public function setError($msg): UserAuthResponse
    {
        $this->error = $msg;
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function getChangePassword(): bool
    {
        return $this->changepPassword;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function setChangePassword(bool $status): self
    {
        $this->changepPassword = $status;
        return $this;
    }
}
