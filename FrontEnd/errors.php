<?php

class HTMLError {
    private $code;
    private $title;
    private $header;
    private $message;

    public static function FromException(Exception $e) {
        return new HTMLError($e->getCode(), $e->getMessage());
    }

    public function __construct(int $code, string $message, string $header = "Error", string $title = "Error") {
        $this->title = htmlspecialchars($title);
        $this->header = htmlspecialchars($header);
        $this->message = htmlspecialchars($message);
    }

    public function print_inline() {
        require("FrontEnd/Templates/error_inline.php");
    }
    
    public function print_full_and_end() {
        http_response_code($this->code);
        require("FrontEnd/Templates/error_full.php");
        exit;
    }
}

class TextError {

    private $code;
    private $message;

    public static function FromException(Exception $e) {
        return new TextError($e->getCode(), $e->getMessage());
    }

    public function __construct(int $code, string $message) {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_message_raw() {
        return $this->message;
    }

    public function get_message_html() {
        return htmlspecialchars($message);
    }

    public function print_and_end() {
        http_response_code($this->code);
        header('Content-Type: text/plain');
        echo $this->get_message_raw();
        exit;
    }
}
    
function end_with_HTML_error(int $code, string $message, string $header = "Error", string $title = "Error") {
    $error = new HTMLError($code, $message, $header, $title);
    $error->print_full_and_end();
}

function end_with_HTML_error_ex(Exception $e) {
    $error = HTMLError::FromException($e);
    $error->print_full_and_end();
}

function end_with_TEXT_error(int $code, string $message) {
    $error = new TextError($code, $message);
    $error->print_and_end();
}

function end_with_TEXT_error_ex(Exception $e) {
    $error = TextError::FromException($e);
    $error->print_and_end();
}