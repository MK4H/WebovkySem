<?php

require_once("Logic/exceptions.php");

interface DB {
    /**
     * Starts transaction, all method calls after this and before commit/rollback will be
     * carried out inside this transaction
     *
     * @return void
     * @throws DBException Thrown when the underlying DB could not start transaction
     */
    public function beginTransaction();

    public function commit();

    public function rollback();

    /**
     * Gets item type names containing $forText
     *
     * @param string $forText Strinig to filter out any names that do not contain it
     * @return array Item type names containing $forText
     * @throws DBException Thrown when execution of statement fails inside the DB
     */
    public function getSuggestions(string $forText) : array;

    /**
     * Gets list of items in the shopping cart
     *
     * @return array Returns array of all items in the shopping list
     * @throws DBException Thrown when there was an error inside the DB
     */
    public function getShoppingList() : array;

    /**
     * Gets all data about the item with id $id from the DB and returns it in an array
     * Validates all data retrieved from DB.
     *
     * @param integer $id
     * @return array ["name" => string, "id" => int, "amount" => int, "position" => int];
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when item with the given $id was not present in the DB
     * @throws DataException Thrown when the data retrieved from the DB was not valid
     */
    public function getItem(int $id) : array;

    /**
     * Gets the amount of the item with id $id
     *
     * @param integer $id ID of the item
     * @return integer Amount of the item with id $id
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the item with the given id does not exist
     * @throws DataException Thrown when the retrieved data is not valid
     */
    public function getItemAmount(int $id) : int;

    /**
     * Sets the amount of the item with id $id to $new_amount
     *
     * @param integer $id Item to change the value of
     * @param integer $new_amount New value of the amount of the item with id $id
     * @return integer Number of rows affected (0-1)
     * @throws DBException Thrown when there was an error in the DB
     */
    public function setItemAmount(int $id, int $newAmount) : int;

    /**
     * Adds an item type with name $name, if item type with this name already exists, does nothing
     *
     * @param string $name Name of the added item type
     * @return integer Probably number of rows added (May not work with some databases)
     * @throws DBException Thrown when there was an error in the DB
     */
    public function tryAddItemType(string $typeName) : int;

    /**
     * Gets the ID of the item type with the name $name
     *
     * @param string $name Name of the item type
     * @return integer ID of the item type with the name $name
     * @throws PDOException
     * @throws ArgumentException Thrown when item type with this name does not exist
     * @throws DBException Thrown when there was an error in the DB
     * @throws DataException Thrown when the data retrieved from the DB were not valid
     */
    public function getItemTypeID(string $typeName) : int;

    /**
     * Adds item to shopping list, with $type_id type, $amount amount and to position $position
     * If $type_id is already present in the list, adds $amount to it's amount
     *
     * @param integer $type_id ID of the type of the item
     * @param integer $amount Initial amount of the item or the amount to add if already present
     * @param integer $position Position at which to insert the item, ignored on update
     * @return void
     * @throws DBException Thrown when there was an error in the DB
     */
    public function addOrUpdateToList(int $typeID, int $amount, int $position) : void;

    /**
     * Tries to remove an item with ID $id, returns number of items actually removed
     *
     * @param integer $id ID of the item to remove
     * @return integer Number of items removed (0-1)
     * @throws DBException Thrown when there was an error in the DB
     */
    public function removeItemFromList(int $id) : int;

     /**
     * Gets number of item types present in shopping cart
     *
     * @return integer Number of item types present in shopping cart
     * @throws DBException Thrown when there was an error in the DB
     */
    public function getItemTypeInListCount() : int;

     /**
     * Gets the position of item with ID $id
     *
     * @param integer $id ID of the item to get the position of
     * @return integer The position of the item with id $id
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when item with given $id does not exist
     * @throws DataException Thrown when the data retrieved from DB are not valid
     */
    public function getItemPosition(int $id) :int;

    /**
     * Changes position of the item $id to $new_pos
     *
     * @param integer $id ID of the item to change
     * @param integer $new_pos New position of the item
     * @return integer Number of rows changed (0 or 1)
     * @throws DBException Thrown when there was an error in the DB
     */
    public function setItemPosition(int $id, int $newPos) : int;

    /**
     * Changes position of items from position $lower (including) to position $upper (excluding) by $change
     *
     * @param integer $lower start of the range of position to change
     * @param integer $upper one beyond the end of the positions to change
     * @param integer $change the amount to add to the positions
     * @return integer Number of rows changed
     * @throws DBException Thrown when there was an error in the DB
     */
    public function changePositionsFromTo(int $lower, int $upper, int $change) : int;

    /**
     * Changes position of items from position $lower to the end by $change
     *
     * @param integer $lower Inclusive lower bound of changed positions
     * @param integer $change Value to add to the positions
     * @return integer Number of changed rows
     * @throws DBException Thrown when there was an error in the DB
     */
    public function changePositionsFrom(int $lower, int $change) : int;

    /**
     * Changes position of items from the beggining to the position $upper by $change
     *
     * @param integer $upper Exclusive upper bound of the positions changed
     * @param integer $change Value to add to the positions
     * @return integer Number of changed rows
     * @throws DBException Thrown when there was an error in the DB
     */
    public function changePositionsUpTo(int $upper, int $change) : int;
}

class PDODB implements DB {
    private $conn;

    private $sugg_stmt;
    private $list_stmt;
    private $get_item_stmt;
    private $get_item_amnt_stmt;
    private $set_item_amnt_stmt;
    private $try_add_item_type_stmt;
    private $get_item_type_id_stmt;
    private $add_or_update_list_stmt;
    private $rmv_item_from_list_stmt;
    private $get_count_items_stmt;
    private $get_pos_stmt;
    private $set_pos_stmt;
    private $change_pos_fromto_stmt;
    private $change_pos_from_stmt;
    private $change_pos_upto_stmt;

    public function __construct() {
        require("Logic/sql_queries.php");
     
        try {
            $this->conn = new PDO($conn_string, $user, $passwd);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);        
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("Could not connect to database", 500, $e);
        }

        
        try {
            //TODO: Create lazy statement class that prepares the statement on first use
            // really dont need all the statements prepared every connection
            $this->sugg_stmt = $this->conn->prepare($sugg_query);
            $this->list_stmt = $this->conn->prepare($list_query);
            $this->get_item_stmt = $this->conn->prepare($get_item_query);
            $this->get_item_amnt_stmt = $this->conn->prepare($get_item_amnt_query);
            $this->set_item_amnt_stmt = $this->conn->prepare($set_item_amnt_query);
            $this->try_add_item_type_stmt = $this->conn->prepare($try_add_item_type_query);
            $this->get_item_type_id_stmt = $this->conn->prepare($get_item_type_id_query);
            $this->add_or_update_list_stmt = $this->conn->prepare($add_or_update_to_list_query);
            $this->rmv_item_from_list_stmt = $this->conn->prepare($rmv_item_from_list_query);
            $this->get_count_items_stmt = $this->conn->prepare($get_item_count_query);
            $this->get_pos_stmt = $this->conn->prepare($get_pos_query);
            $this->set_pos_stmt = $this->conn->prepare($set_pos_query);
            $this->change_pos_fromto_stmt = $this->conn->prepare($change_pos_fromto_query);
            $this->change_pos_from_stmt = $this->conn->prepare($change_pos_from_query);
            $this->change_pos_upto_stmt = $this->conn->prepare($change_pos_upto_query);
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("Prepared statement creation failed", 500, $e);
        }
    }

    public function beginTransaction() {
        try {
            $this->conn->beginTransaction();
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Could not begin transaction", 500, $e);
        }
    }

    public function commit() {
        try {
            return $this->conn->commit();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            //TODO: What return code ?
            throw new DBException("DB: Commit failed", 400, $e);
        }
    }

    public function rollback() {
        try{
            return $this->conn->rollback();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Rollback failed", 500, $e);
        }
    }

    public function getSuggestions(string $forText) : array {
        try {
            $this->sugg_stmt->bindValue(":name", "%$forText%", PDO::PARAM_STR);         
            $this->sugg_stmt->execute();
            //TODO: Validate
            return $this->sugg_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Internal error", 500, $e);
        }
    }

    public function getShoppingList() : array {
        try {
            $this->list_stmt->execute();
    
            $val = $this->list_stmt->fetchAll();
            //TODO: Return just a cursor
            //TODO: possibly preprocess
            return $val;
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Internal error", 500, $e);
        }
    }

    public function getItem(int $id) : array {
        try {
            $this->get_item_stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
            $this->get_item_stmt->execute();
    
            if (!($val = $this->get_item_stmt->fetch())) {
                throw new ArgumentException("Item with this id does not exist", 422);
            }

            $filters = [
                "name" => FILTER_DEFAULT,
                "id" => [
                    "filter" => FILTER_VALIDATE_INT,
                    "flags" => FILTER_REQUIRE_SCALAR
                ],
                "amount" => [
                    "filter" => FILTER_VALIDATE_INT,
                    "flags" => FILTER_REQUIRE_SCALAR,
                    "options" => [
                        "min_range" => 1
                    ]
                ],
                "position" => [
                    "filter" => FILTER_VALIDATE_INT,
                    "flags" => FILTER_REQUIRE_SCALAR,
                    "options" => [
                        "min_range" => 1
                    ]
                ]
            ];
            
            if (($val = filter_var_array($val, $filters)) === false) {
                $this->logDataError("getItem");
                throw new DataException("Invalid data retrieved from the DB", 500);
            }
    
            return $val;
        }
        catch (PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Internal error", 500, $e);
        }
    }

    public function getItemAmount(int $id) : int {
        try {
            $this->get_item_amnt_stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $this->get_item_amnt_stmt->execute();
    
            if (!($amount = $this->get_item_amnt_stmt->fetch())) {
                throw new ArgumentException("Item with the given id does not exist", 422);
            }
    
            //TODO: Flags to filter_var
            if (($amount = filter_var($amount, FILTER_VALIDATE_INT)) === null) {
                $this->logDataError("getItemAmount");
                throw new DataException("Invalid data retrived from DB", 500);
            }
    
            return $amount;
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Get item amount failed", 500, $e);
        }

    }

    public function setItemAmount(int $id, int $newAmount) : int {
        try {
            $this->set_item_amnt_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->set_item_amnt_stmt->bindValue(':amount', $newAmount, PDO::PARAM_INT);
      
            $this->set_item_amnt_stmt->execute();
    
            return $this->set_item_amnt_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Set item amount failed", 500, $e);
        }
    }

    public function tryAddItemType(string $typeName) : int {
        try {
            $this->try_add_item_type_stmt->bindValue(':name', $typeName, PDO::PARAM_STR);    
            $this->try_add_item_type_stmt->execute();
    
            return $this->try_add_item_type_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: try add item type failed", 500, $e);
        }
    }

    public function getItemTypeID(string $typeName) : int {
        try {
            $this->get_item_type_id_stmt->bindValue(':name', $typeName, PDO::PARAM_STR);
    
            $this->get_item_type_id_stmt->execute();
    
            if (!($db_res = $this->get_item_type_id_stmt->fetch())) {
                throw new ArgumentException("Item type with this name does not exist", 422);
            }
    
            if (!isset($db_res['id'])) {
                throw new DBException("DB: Invalid DB schema, missing id", 500);
            }
    
            //TODO: Add flags to validation
            if (($value = filter_var($db_res['id'], FILTER_VALIDATE_INT)) === null) {
                $this->logDataError("ID was not int or not present");
                throw new DataException("Invalid data stored in the database", 500);
            }
            return $value;
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: get item type id failed", 500, $e);
        }
    }

    public function addOrUpdateToList(int $typeID, int $amount, int $position) : void {
        try {
            $this->add_or_update_list_stmt->bindValue(':type_id', $typeID, PDO::PARAM_INT);
            $this->add_or_update_list_stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
            $this->add_or_update_list_stmt->bindValue(':position', $position, PDO::PARAM_INT);
            
            $this->add_or_update_list_stmt->execute();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Add or update to list failed", 500, $e);
        }
    }

    public function removeItemFromList(int $id) : int {
        try {
            $this->rmv_item_from_list_stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $this->rmv_item_from_list_stmt->execute();
    
            return $this->rmv_item_from_list_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Remove item from list failed", 500, $e);
        }
    }


    public function getItemTypeInListCount() : int {
        try {
            //No params

            $this->get_count_items_stmt->execute();

            if (!($db_res = $this->get_count_items_stmt->fetch())) {
                throw new DBException("DB failed to count the items", 500);
            }

            //This is not user data, dont need to validate
            return $db_res[0];
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Get item type in list count failed", 500, $e);
        }

    }

    public function getItemPosition(int $id) : int {
        try {
            $this->get_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
            $this->get_pos_stmt->execute();
    
            if (!($db_res = $this->get_pos_stmt->fetch())) {
                throw new ArgumentException("Item with given ID does not exist", 422);
            }
            $position = $db_res['position'];
    
            //TODO: Add flags
            $val = filter_var($position, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 0
                ]
            ]);
            if ($val === null || $val === false) {
                $this->logDataError("Position was not present or was not valid");
                throw new DataException("Invalid data stored in DB", 500);
            }
            return $val;
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Get item position failed", 500, $e);
        }
        
    }

    public function setItemPosition(int $id, int $newPos) : int {
        try {
            $this->set_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->set_pos_stmt->bindValue(':new_position', $newPos, PDO::PARAM_INT);
    
            $this->set_pos_stmt->execute();
            
            return $this->set_pos_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Set item position failed", 500, $e);
        }
    }

    public function changePositionsFromTo(int $lower, int $upper, int $change) : int {
        if ($lower >= $upper) {
            return 0;
        }

        try {
            $this->change_pos_fromto_stmt->bindValue(':lower', $lower, PDO::PARAM_INT);
            $this->change_pos_fromto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT);
            $this->change_pos_fromto_stmt->bindValue(':amount', $change, PDO::PARAM_INT);
    
            $this->change_pos_fromto_stmt->execute();
    
            return $this->change_pos_fromto_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Change position from to failed", 500, $e);
        }
    }

    public function changePositionsFrom(int $lower, int $change) : int {
        try {
            $this->change_pos_from_stmt->bindValue(':lower', $lower, PDO::PARAM_INT);
            $this->change_pos_from_stmt->bindValue(':amount', $change, PDO::PARAM_INT);
            
            $this->change_pos_from_stmt->execute();
    
            return $this->change_pos_from_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Change position from failed", 500, $e);
        }
    }

    public function changePositionsUpTo(int $upper, int $change) : int {

        try {
            $this->change_pos_upto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT);
            $this->change_pos_upto_stmt->bindValue(':amount', $change, PDO::PARAM_INT);
            
            $this->change_pos_upto_stmt->execute();
    
            return $this->change_pos_upto_stmt->rowCount();
        }
        catch(PDOException $e) {
            $this->logDBError(__FUNCTION__, $e);
            throw new DBException("DB: Change position up to failed", 500, $e);
        }
    }

    /**
     * Logs database error
     *
     * @param PDOException $e Exception describing the error
     * @return void
     */
    private function logDBError(string $func, PDOException $e) {
        $message = $e->getMessage();
        error_log("DB error in func ${func}: ${message}");
    }

    private function logDataError(string $func, string $message) {
        error_log("DB data error in func ${func}: ${message}");
    }
}