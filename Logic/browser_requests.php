<?php
require_once(__DIR__ . 'request_base.php');

class AddItemRequest extends Request {
    private $data;

    public function __construct(ShopData $shop_data) {
        $this->data = $shop_data;
    }

    public function execute() {
        $type_name = filter_input(INPUT_POST, 'type_name');
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
    
        if (($errors = check_params_add($type_name, $amount)) !== true) {
            $url_params = $this->encode_url_params($errors, $type_name, $amount);
            header("Location: index.php?${url_params}", true, 303);
            return;
        }
    
        try {
            $this->data->addItem($type_name, $amount);
        }
        catch(DBException $e) {
            //TODO: Error screen
            out_text_error($e->getCode(), $e->getMessage());
        }
        catch(DataException $e) {
            //TODO: Error screen
            out_text_error($e->getCode(), $e->getMessage());
        }
        catch(ArgumentException $e) {
            //TODO: Error screen
            out_text_error($e->getCode(), $e->getMessage());
        }
    
        header('Location: index.php', true, 303);
    }

    protected function check_params($type_name, $amount) {
        $errors = [];
        if (check_param($errors, 'type_name', $type_name) && $type_name === '') {
            $errors['type_name'] = 'empty';
        }
    
        
        if (check_param($errors, 'amount', $amount) && $amount <= 0) {
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
            $params = append_to_params('', 'type_name_error', $errors['type_name']);
        }
        else {
            $params = append_to_params('', 'type_name', $type_name);
        }
    
        if (isset($errors['amount'])) {
            $params = append_to_params($params, 'amount_error', $errors['amount']);
        }
        else {
            $params = append_to_params($params, 'amount', $amount);
        }
    
        return $params;
    }
}

class GetPageRequest extends Request {
    private $data;

    public function __construct(ShopData $shop_data) {
        $this->data = $shop_data;
    }

    public function execute() {
        //TODO: React if the query params are wrong
        $preset_values = [];
        $type_name = filter_input(INPUT_GET,'type_name');
        if ($type_name !== null) {
            $preset_values['type_name'] = urldecode($type_name);
        }

        $type_name_error = filter_input(INPUT_GET,'type_name_error');
        if ($type_name_error !== null) {
            $preset_values['type_name_error'] = urldecode($type_name_error);
        }

        $amount = filter_input(INPUT_GET,'amount', FILTER_VALIDATE_INT);
        if ($amount !== null && $amount !== false) {
            $preset_values['type_name_error'] = urldecode($type_name_error);
        }

        $amount_error = filter_input(INPUT_GET,'amount_error');
        if ($amount_error !== null) {
            $preset_values['amount_error'] = urldecode($amount_error);
        }

        //TODO: Catch exceptions
        $page = new PageView($this->data, $preset_values);
        $page->render();
    }
}