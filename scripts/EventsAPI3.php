<?php
require_once 'EventsController3.class.php';
use EventsController as controller;

try {
  $output = controller::resolveRequest();
} catch (Error $error) {
  $output = controller::getErrorMessage($error);
}

echo json_encode($output);
