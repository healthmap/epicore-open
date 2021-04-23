<?php

class UserController
{
  public static function getUserData()
  {
    $userCookie = $_COOKIE["epiUserInfo"];
    if (isset($userCookie)) {
      return json_decode($userCookie, true);
    }
  }
}

