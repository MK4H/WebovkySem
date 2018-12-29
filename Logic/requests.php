<?php
include_once("Logic/data_model.php");
include_once("FrontEnd/functions.php");

function add_item(ShopData $data) {
    $type_name = filter_input(INPUT_POST,'type_name');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

    //TODO: React to invalid params

    if ($data->addItem($type_name, $amount) === false) {
        header("HTTP/1.0 500 Internal Server Error");
        echo "DB Error";
        return;
    }

    header('Location: index.php', true, 303);
}

function change_amount(ShopData $data) {

    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $new_amnt = filter_input(INPUT_POST, 'new_amount', FILTER_VALIDATE_INT);

    //TODO: React to invalid params

    if ($data->setItemAmount($item_id, $new_amnt) === false) {
        header("HTTP/1.0 500 Internal Server Error");
        echo "DB Error set amount";
        return;
    }

    if (($item = $data->getItem($item_id)) === false) {
        header("HTTP/1.0 500 Internal Server Error");
        echo "DB Error get item";
        return;
    }

    out_table_row($item['id'], $item['position'], $item['name'], $item['amount']);
}

function change_position(ShopData $data) {
  
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);
    $end_pos = filter_input(INPUT_POST,'end_pos', FILTER_VALIDATE_INT);

    //TODO: Check values

    if ($data->changePosition($item_id, $end_pos) === false) {
        header("HTTP/1.0 400 Bad Request");
        echo "Invalid arguments";
    }
    
    out_table();
}

function delete_item(ShopData $data) {
    $item_id = filter_input(INPUT_POST,'item_id', FILTER_VALIDATE_INT);

    //TODO: Check values

    if ($data->removeItem($item_id) === false) {
        header("HTTP/1.0 500 Internal Server Error");
        echo "DB Error";
    }

    out_table();
}
