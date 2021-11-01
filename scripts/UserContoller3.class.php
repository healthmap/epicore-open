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

  public static function isUserValid()
  {
    $userCookie = $_COOKIE["epiUserInfo"];
    if (isset($userCookie)) {
      return true;
    }
    return false;
  }
}

