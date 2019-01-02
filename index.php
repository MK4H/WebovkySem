<?php
    require_once("Logic/requests.php");
    require_once("Logic/data_model.php");
    require_once("FrontEnd/functions.php");

    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
        get_page();
    }
	else if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && isset($_POST['action'])) {
        //Dispatch to the correct handler
        $action = $_POST['action'];
        $data = new ShopData();
        
        if ($action == "add_item") {
            add_item($data);
        }
        else if ($action == "change_amount") {
            change_amount($data);
        }
        else if ($action == "change_position") {
            change_position($data);
        }
        else if ($action == "delete_item") {
            delete_item($data);
        }
        else {
            //TODO: Return proper html
            out_text_error(400, "Invalid POST method");    
        }
    }
    else if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
        out_text_error(400, "Invalid POST method");        
        //TODO: Return proper html
    }
    else {
        out_text_error(405, "Unknown method");
        //TODO: Return proper html
    }

