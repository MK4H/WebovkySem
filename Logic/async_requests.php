<?php
require_once('Logic/request_base.php');
require_once('FrontEnd/errors.php');

class ChangeAmountRequest extends Request {
    private $data;

    public function __construct(ShopData $shop_data) {
        $this->data = $shop_data;
    }
    
    public function execute() {
        $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $new_amnt = filter_input(INPUT_POST, 'new_amount', FILTER_VALIDATE_INT);
    
        if (($errors = $this->check_params($item_id, $new_amnt)) !== true) {     
            end_with_TEXT_error(422, get_text($errors));
        }
    
        try {
            $this->data->setItemAmount($item_id, $new_amnt);
            $item = $this->data->getItem($item_id);
        }
        catch(Exception $e) {
            end_with_TEXT_error_ex($e);
        }
    
        $table_row = new TableRowView($item['id'], $item['name'], $item['amount'], $item['position']);
        $table_row->render();
    }

    protected function check_params($item_id, $new_amnt) {
        $errors = [];
        $this->check_param($errors, 'item_id', $item_id);
    
        if ($this->check_param($errors, 'new_amnt', $new_amnt) && $new_amnt < 0) {
            $errors['new_amnt'] = 'ltzero';
        }
    
        return count($errors) !== 0 ? $errors : true;
    }
}

class ChangePositionRequest extends Request {
    private $data;

    public function __construct(ShopData $shop_data) {
        $this->data = $shop_data;
    }
    
    public function execute() {
        $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);
        $end_pos = filter_input(INPUT_POST,'end_pos', FILTER_VALIDATE_INT);
    
        if (($errors = $this->check_params($item_id, $end_pos)) !== true) {    
            end_with_TEXT_error(422, get_text($errors));
        }
    
    
        try {
            $this->data->changePosition($item_id, $end_pos);
        }
        catch(Exception $e) {
            end_with_TEXT_error_ex($e);
        }

        
        $table = new TableView($this->data);
        $table->render();
    }

    protected function check_params($item_id, $end_pos) {
        $errors = [];
        $this->check_param($errors, 'item_id', $item_id);
    
        if ($this->check_param($errors, 'end_pos', $end_pos) && $end_pos < 0) {
            $errors['end_pos'] = 'out_of_bounds';
        }
    
        return count($errors) !== 0 ? $errors : true;
    }
}

class DeleteItemRequest extends Request {
    private $data;

    public function __construct(ShopData $shop_data) {
        $this->data = $shop_data;
    }
    
    public function execute() {
        $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);

        if (($errors = $this->check_params($item_id)) !== true) {   
            end_with_TEXT_error(422, get_text($errors));
        }
    
        try {
            $this->data->removeItem($item_id);
        }
        catch(Exception $e) {
            end_with_TEXT_error_ex($e);
        }
    
        $table = new TableView($this->data);
        $table->render();
    }

    protected function check_params($item_id) {
        $errors = [];
        $this->check_param($errors, 'item_id', $item_id);
    
        return count($errors) !== 0 ? $errors : true;
    }
}