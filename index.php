<?php
require_once("FrontEnd/errors.php");
require_once("Logic/data_model.php");
require_once("Logic/browser_requests.php");
require_once("Logic/async_requests.php");
require_once("FrontEnd/views.php");

try {
    $data = new ShopData();

    $handlers = [
        'GET' => [
            'default' => new GetPageRequest($data)
        ],
        'POST' => [
            'add_item' => new AddItemRequest($data),
            'change_amount' => new ChangeAmountRequest($data),
            'change_position' => new ChangePositionRequest($data),
            'delete_item' => new DeleteItemRequest($data)
        ]  
    ];
  
    $method = strtoupper($_SERVER['REQUEST_METHOD']);

    if (!isset($handlers[$method])) {
        end_with_HTML_error(405, "Unsupported method ${method}");
    }
    
    $method_handlers = $handlers[$method];
    if (!isset($_GET['action'])) {
        if (isset($method_handlers['default'])) {
            $method_handlers['default']->execute();
            exit;
        }
        else {
            end_with_HTML_error(400, "Invalid request to ${method}"); 
        }
    }
    
    $action = $_GET['action'];

    if (!isset($method_handlers[$action])) {
        end_with_HTML_error(400, "Invalid request to ${method}"); 
    }

    $method_handlers[$action]->execute();
}
catch(Exception $e) {
    $message = $e->getMessage();
    error_log("Uncaught exception: ${message}\n");
    end_with_HTML_error(500, $e->getMessage(), "Uncaught error", "Uncaught error");
} 
   

