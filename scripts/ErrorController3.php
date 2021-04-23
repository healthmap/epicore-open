<?php

class ErrorController
{

  public static function getErrorMessage($error)
    {
        $action = null;
        if (isset($_REQUEST['action'])) {
            $action = $_REQUEST['action'];
        }

        $error_response = Array(
            'error' => true
        );

        $error_messages = self::getErrorMessages();

        if (isset($action) && isset($error_messages[$action])) {
            $error_response["error_message"] = $error_messages[$action];
        } else {
            $error_response["error_message"] = $error_messages['default'];
        }
        $error_response["error_details"] = 'Error code: ' . $error->getCode() . ', <i>"'. $error->getMessage() . '"</i>' . ' in file <i>' . $error->getFile() .' </i>, line ' . $error->getLine() . '.';
        return $error_response;
    }

    private static function getErrorMessages()
    {
        $path = __DIR__ . '/../constants/error-messages.json';

        if (!file_exists($path)) {
            return null;
        }

        $data = file_get_contents($path);

        if (isset($data)) {
            return json_decode($data, true);
        }
    }

}