<?php
require_once "EventsController3.class.php";
require_once "ErrorController3.php";
require_once  "UserContoller3.class.php";

use UserController as userController;
use EventsController as controller;
use ErrorController as errorController;

if (!userController::isUserValid()) {
  echo json_encode(false);
  return false;
}

try {
  $output = controller::resolveRequest();
} catch (Error $error) {
  $output = errorController::getErrorMessage($error);
}

echo json_encode($output);
