<?php
require_once("Logic/exceptions.php");
require_once("Logic/data_model.php");
require_once("FrontEnd/functions.php");



abstract class Request {
    
    public abstract function execute();

    protected function check_param(array &$errors, string $name, $value) {
        if ($value === null) {
            $errors[$name] = 'missing';
            return false;
        }
        else if ($value === false) {
            $errors[$name] = 'invalid';
            return false;
        }
        return true;
    }

    protected function get_text(array $errors) {
        $message = "";
        foreach ($errors as $var => $error) {
            $message = $message . "${var}:${error}\n";
        }
        return $message;
    }
}



