<?php
require_once('Logic/request_base.php');
require_once('FrontEnd/errors.php');

abstract class BrowserRequest extends Request {
    public function endWithError(Exception $e) {
        end_with_HTML_error_ex($e);
    }
}

class AddItemRequest extends BrowserRequest {

    public function __construct() {
    }

    public function execute(Shop $data) {
        $type_name = filter_input(INPUT_POST, 'type_name');
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
    
        if (($errors = $this->check_params($type_name, $amount)) !== true) {
            $url_params = $this->encode_url_params($errors, $type_name, $amount);
            header("Location: index.php?${url_params}", true, 303);
            return;
        }
    
        try {
            $data->addItem($type_name, $amount);
        }
        catch(Exception $e) {
            end_with_HTML_error_ex($e);
        }

        header('Location: index.php', true, 303);
    }

    protected function check_params($type_name, $amount) {
        $errors = [];
        if ($this->check_param($errors, 'type_name', $type_name) && $type_name === '') {
            $errors['type_name'] = 'empty';
        }
    
        
        if ($this->check_param($errors, 'amount', $amount) && $amount <= 0) {
            $errors['amount'] = 'lezero';
        }
    
        return count($errors) !== 0 ? $errors : true;
    } 

    protected function append_to_params(string $params, string $name, string $value) {
        $name = urlencode($name);
        $value = urlencode($value);
        
        if ($params === '') {
            return "${name}=${value}";
        }
        else {
            return $params . "&${name}=${value}";
        }
    }
    
    protected function encode_url_params(array $errors, $type_name, $amount) {
        if (isset($errors['type_name'])) {
            $params = $this->append_to_params('', 'type_name_error', $errors['type_name']);
        }
        else {
            $params = $this->append_to_params('', 'type_name', $type_name);
        }
    
        if (isset($errors['amount'])) {
            $params = $this->append_to_params($params, 'amount_error', $errors['amount']);
        }
        else {
            $params = $this->append_to_params($params, 'amount', $amount);
        }
    
        return $params;
    }
}

class GetPageRequest extends BrowserRequest {


    public function __construct() {
    }

    public function execute(Shop $data) {
        $preset_values = [];
        $type_name = filter_input(INPUT_GET,'type_name');
        if ($type_name !== null) {
            $preset_values['type_name'] = urldecode($type_name);
        }

        $type_name_error = filter_input(INPUT_GET,'type_name_error');
        if ($type_name_error !== null) {
            $preset_values['type_name_error'] = urldecode($type_name_error);
        }

        if ($type_name !== null && $type_name_error !== null) {
            end_with_HTML_error(422, "Type name and type name error cannot be both set", "Malformed query", "Malformed query");
        }

        $amount = filter_input(INPUT_GET,'amount', FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1
            ]
        ]);
        if ($amount !== null && $amount !== false) {
            $preset_values['amount'] = urldecode($amount);
        }

        $amount_error = filter_input(INPUT_GET,'amount_error');
        if ($amount_error !== null) {
            $preset_values['amount_error'] = urldecode($amount_error);
        }

        if ($amount !== null && $amount_error !== null) {
            end_with_HTML_error(422, "Amount and amount error cannot be both set", "Malformed query", "Malformed query");
        }
        

        try {
            $page = new PageView($data, $preset_values);
        }
        catch(Exception $e) {
            end_with_HTML_error_ex($e);
        }
        //Should not throw exception
        $page->render();
    }
}