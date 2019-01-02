<?php
require_once("Logic/exceptions.php");
require_once("Logic/data_model.php");
require_once("FrontEnd/functions.php");

function check_param(array &$errors, string $name, $value) {
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

function check_params_add($type_name, $amount) {
    $errors = [];
    if (check_param($errors, 'type_name', $type_name) && $type_name === '') {
        $errors['type_name'] = 'empty';
    }

    
    if (check_param($errors, 'amount', $amount) && $amount <= 0) {
        $errors['amount'] = 'lezero';
    }

    return count($errors) !== 0 ? $errors : true;
}

function check_params_change_amount($item_id, $new_amnt) {
    $errors = [];
    check_param($errors, 'item_id', $item_id);

    if (check_param($errors, 'new_amnt', $new_amnt) && $new_amnt < 0) {
        $errors['new_amnt'] = 'ltzero';
    }

    return count($errors) !== 0 ? $errors : true;
}

function check_params_change_position($item_id, $end_pos) {
    $errors = [];
    check_param($errors, 'item_id', $item_id);

    if (check_param($errors, 'end_pos', $end_pos) && $end_pos < 0) {
        $errors['end_pos'] = 'out_of_bounds';
    }

    return count($errors) !== 0 ? $errors : true;
}

function check_params_delete_item($item_id) {
    $errors = [];
    check_param($errors, 'item_id', $item_id);

    return count($errors) !== 0 ? $errors : true;
}

function append_to_params(string $params, string $name, string $value) {
    $name = urlencode($name);
    $value = urlencode($value);
    
    if ($params === '') {
        return "${name}=${value}";
    }
    else {
        return $params . "&${name}=${value}";
    }
}

function encode_url_params(array $errors, $type_name, $amount) {
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

function get_text(array $errors) {
    $message = "";
    foreach ($errors as $var => $error) {
        $message = $message . "${var}:${error}\n";
    }
    return $message;
}

function add_item(ShopData $data) {
    $type_name = filter_input(INPUT_POST, 'type_name');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

    if (($errors = check_params_add($type_name, $amount)) !== true) {
        $url_params = encode_url_params($errors, $type_name, $amount);
        header("Location: index.php?${url_params}", true, 303);
        return;
    }

    try {
        $data->addItem($type_name, $amount);
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

function change_amount(ShopData $data) {

    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $new_amnt = filter_input(INPUT_POST, 'new_amount', FILTER_VALIDATE_INT);

    if (($errors = check_params_change_amount($item_id, $new_amnt)) !== true) {     
        out_text_error(422, get_text($errors));
    }

    try {
        $data->setItemAmount($item_id, $new_amnt);
        $item = $data->getItem($item_id);
    }
    catch(DBException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }
    catch(ArgumentException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }

    out_table_row($item['id'], $item['position'], $item['name'], $item['amount']);
}

function change_position(ShopData $data) {
  
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);
    $end_pos = filter_input(INPUT_POST,'end_pos', FILTER_VALIDATE_INT);

    if (($errors = check_params_change_position($item_id, $end_pos)) !== true) {    
        out_text_error(422, get_text($errors));
    }


    try {
        $data->changePosition($item_id, $end_pos);
    }
    catch(DBException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }
    catch(ArgumentException $e) {
        //Should have been validated, internal server error, probably hide the message
        out_text_error($e->getCode(), $e->getMessage());
    }
    catch(DataException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }
    
    out_table();
}

function delete_item(ShopData $data) {
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);

    //TODO: Check values
    if (($errors = check_params_delete_item($item_id)) !== true) {   
        out_text_error(422, get_text($errors));
    }

    try {
        $data->removeItem($item_id);
    }
    catch(DBException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }
    catch(ArgumentException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }
    catch(DataException $e) {
        out_text_error($e->getCode(), $e->getMessage());
    }

    out_table();
}

function get_page() {
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

    out_whole_page($preset_values);
}
