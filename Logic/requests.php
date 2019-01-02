<?php
include_once("Logic/data_model.php");
include_once("FrontEnd/functions.php");



function add_item(ShopData $data) {
    $type_name = filter_input(INPUT_POST, 'type_name');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => 1,
        ]
    ]);

    if (check_params($type_name, $amount) === false) {
        return;
    }

    //TODO: React to invalid params
    try {
        $data->addItem($type_name, $amount);
    }
    catch(DBException $e) {
        //TODO: Error screen
        out_text_error(500, $e->getMessage());
    }
    catch(DataException $e) {
        //TODO: Error screen
        out_text_error(500, $e->getMessage());
    }
    catch(ArgumentException $e) {
        //TODO: Error screen
        out_text_error(500, $e->getMessage());
    }

    header('Location: index.php', true, 303);
}

function change_amount(ShopData $data) {

    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $new_amnt = filter_input(INPUT_POST, 'new_amount', FILTER_VALIDATE_INT);

    //TODO: React to invalid params
    try {
        $data->setItemAmount($item_id, $new_amnt);
        $item = $data->getItem($item_id);
    }
    catch(DBException $e) {
        out_text_error(500, $e->getMessage());
    }
    catch(ArgumentException $e) {
        //Should have been validated, internal server error, probably hide the message
        out_text_error(500, $e->getMessage());
    }

    out_table_row($item['id'], $item['position'], $item['name'], $item['amount']);
}

function change_position(ShopData $data) {
  
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);
    $end_pos = filter_input(INPUT_POST,'end_pos', FILTER_VALIDATE_INT);

    //TODO: Check values

    try {
        $data->changePosition($item_id, $end_pos);
    }
    catch(DBException $e) {
        out_text_error(500, $e->getMessage());
    }
    catch(ArgumentException $e) {
        //Should have been validated, internal server error, probably hide the message
        out_text_error(500, $e->getMessage());
    }
    catch(DataException $e) {
        out_text_error(500, $e->getMessage());
    }
    
    out_table();
}

function delete_item(ShopData $data) {
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);

    //TODO: Check values
    
    try {
        $data->removeItem($item_id);
    }
    catch(DBException $e) {
        out_text_error(500, $e->getMessage());
    }
    catch(ArgumentException $e) {
        //Should have been validated, internal server error, probably hide the message
        out_text_error(500, $e->getMessage());
    }
    catch(DataException $e) {
        out_text_error(500, $e->getMessage());
    }

    out_table();
}
