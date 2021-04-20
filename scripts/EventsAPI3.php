<?php
require_once 'EventsController3.class.php';
require_once 'ErrorController3.php';

use EventsController as controller;
use ErrorController as errorController;

try {
  $output = controller::resolveRequest();
} catch (Error $error) {
  $output = errorController::getErrorMessage($error);
}

echo json_encode($output);
