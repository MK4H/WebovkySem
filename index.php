<?php
require_once("FrontEnd/errors.php");
require_once("Logic/db_access.php");
require_once("Logic/data_model.php");
require_once("Logic/browser_requests.php");
require_once("Logic/async_requests.php");
require_once("FrontEnd/views.php");

try {
    $handlers = [
        'GET' => [
            'default' => new GetPageRequest()
        ],
        'POST' => [
            'add_item' => new AddItemRequest(),
            'change_amount' => new ChangeAmountRequest(),
            'change_position' => new ChangePositionRequest(),
            'delete_item' => new DeleteItemRequest()
        ]  
    ];
  
    $method = strtoupper($_SERVER['REQUEST_METHOD']);

    if (!isset($handlers[$method])) {
        end_with_HTML_error(405, "Unsupported method ${method}");
    }
    
    $method_handlers = $handlers[$method];
    $action = isset($_GET['action']) ? $_GET['action'] : 'default';
    
    if (!isset($method_handlers[$action])) {
        end_with_HTML_error(400, "Invalid request to ${method}"); 
    }

    try {
        $data = new ShopData(new PDODB());  
        $method_handlers[$action]->execute($data);  
    }
    catch(DBException $e) {
        $method_handlers[$action]->endWithError($e);
    }
    
}
catch(Exception $e) {
    $message = $e->getMessage();
    error_log("Uncaught exception: ${message}\n");
    end_with_HTML_error(500, $e->getMessage(), "Uncaught error", "Uncaught error");
} 
   

