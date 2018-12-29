<?php
    include("Logic/requests.php");
    include_once("Logic/data_model.php");

    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
        require("FrontEnd/main_page.php");
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
            header("HTTP/1.0 400 Bad Request");
            echo "Invalid post method";
        }
    }
    else if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
        header("HTTP/1.0 400 Bad Request");
        echo "Invalid post method";
    }
    else {
        //TODO: Unknown HTTP method
        header("HTTP/1.0 405 Method Not Allowed");
        echo "Unknown method sent";
    }

