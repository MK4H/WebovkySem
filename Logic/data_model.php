<?php

class ShopData {

    //TODO: Make lastError method, that returns last error that happened

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

        include("Logic/sql_queries.php");

        try {
            $this->conn = new PDO($conn_string, $user, $passwd);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);        
        }
        catch (PDOException $e) {
            //TODO:call script to display error message
            echo "Database connection error: " . $e->getMessage();
            //TODO: throw another exception to signal failure but hide the password and other data
            die(); 
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
            //TODO: Call script to display error message
            echo "Database stored procedures creation error";
            //TODO: throw another exception to signal failure
            die();
        }
    }

    /**
     * Gets item type names containing $forText
     *
     * @param string $forText Strinig to filter out any names that do not contain it
     * @return array Item type names containing $forText
     * @throws DBException Thrown when execution of statement fails inside the DB
     * @throws ArgumentException Thrown when arguments do not meet preconditions
     */
    public function getSuggestions(string $forText) : array {
        try {
            if (!is_string($forText)) {              
                throw new ArgumentException("Expected string argument");
            }
    
            $this->sugg_stmt->bindValue(":name", "%$forText%", PDO::PARAM_STR);         
            $this->sugg_stmt->execute();
            //TODO: Validate
            return $this->sugg_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        catch (PDOException $e) {
            throw new DBException("DB: Internal error", 0, $e);
        }
    }

    /**
     * Gets list of items in the shopping cart
     *
     * @return void
     */
    public function getShoppingList() : array {
        try {
            $this->list_stmt->execute();
    
            $val = $this->list_stmt->fetchAll();
            //TODO: Return just a cursor
            //TODO: possibly preprocess
            return $val;
        }
        catch (PDOException $e) {
            throw new DBException("DB: Internal error", 0, $e);
        }
    }

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
    public function getItem(int $id) : array {
        try {
            $this->get_item_stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
            $this->get_item_stmt->execute();
    
            if (!($val = $this->get_item_stmt->fetch())) {
                throw new ArgumentException("Item with this id does not exist");
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
            
            if (($val = filter_var_array($var, $filters)) === false) {
                throw new DataException("Invalid data retrieved from the DB");
            }
    
            return $val;
        }
        catch (PDOException $e) {
            throw new DBException("DB: Internal error", 0, $e);
        }
    }

    /**
     * Adds item of type $type_name to the end of the list, with initail amount $amount
     * Adds item type $type_name to item types if it was not present already
     * If an item of this type was already in the list, adds the $amount to it's current amount
     * 
     * Is atomic, on error changes nothing and results in exception.
     * 
     * @param string $type_name Name of the type of the item to add to the list
     * @param integer $amount Initial amount of the item
     * @return boolean True if the operation was sucessful
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the $amount is <= 0
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function addItem(string $type_name, int $amount) : boolean {
        if ($amount <= 0) {
            throw new ArgumentException("Amount should be greater than 0");
        }

        try {
            $this->conn->beginTransaction();
        }
        catch (PDOException $e) {
            throw new DBException("DB: Could not begin transaction", 0, $e);
        }
       
        try {
            $this->stTryAddItemType($type_name);  
            $type_id = $this->stGetItemTypeID($type_name);
            $item_cnt = $this->stGetCountItems();   

            $this->stAddOrUpdateToList($type_id, $amount, $item_cnt);  
            return $this->conn->commit();
        }
        catch (PDOException $e) {
            $this->conn->rollback();
            throw new DBException("DB: Internal error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Removes item from the list and shifts all the items after it
     * to their new position.
     * Is atomic, on error changes nothing and results in exception.
     *
     * @param integer $id ID of the item to remove
     * @return boolean True when the operation was sucessful
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the item with the id $id does not eist
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function removeItem(int $id) : boolean {
        try {
            $this->conn->beginTransaction();
        }
        catch (PDOException $e) {
            throw new DBException("DB: Could not begin transaction", 0, $e);
        }

        try {  
            $item_pos = $this->stGetItemPosition($id);    
            $this->stRemoveItemFromList($id);
            $this->stChangePositionFrom($item_pos, -1);
           
            return $this->conn->commit();
        }
        catch (PDOException $e) {
            $this->conn->rollback();
            throw new DBException("DB: Internal error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Changes amount of the item with id $id to $new_amount.
     * If $new_amount is less than zero, removes the item.
     * Is atomic, on error changes nothing and results in exception.
     *
     * @param integer $id ID of the item to change
     * @param integer $new_amount New amount of the item with id $id
     * @return boolean True if the operation succeded
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the item with the $id does not exist
     */
    public function setItemAmount(int $id, int $new_amount) : boolean {
       

        try {
            $this->conn->beginTransaction();
        }
        catch (PDOException $e) {
            throw new DBException("DB: Could not begin transaction", 0, $e);
        }

        try {
            if ($new_amount <= 0) {
                if ($this->stRemoveItemFromList($id) < 1) {
                    throw new ArgumentException("Item with the given id does not exist");
                }          
            }
            else {
                if ($this->stSetItemAmount($id, $new_amount) < 1) {
                    throw new ArgumentException("Item with the given id does not exist");
                }  
            }
            return $this->conn->commit();
        }
        catch (PDOException $e) {
            $this->conn->rollback();
            throw new DBException("DB: Internal error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Moves item with the id $id from it's current position to the new position $new_pos.
     * Shifts all the items in between the old and new positions.
     * Is atomic, on error changes nothing and results in exception.
     *
     * @param integer $id ID of the item to move
     * @param integer $new_pos New position to move the item to
     * @return boolean True if the operation was succesful
     * @throws DBException Thrown when there was an error during the execution in the DB
     * @throws ArgumentException Thrown when item with $id does not exist or $new_pos is out of bounds
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function changePosition(int $id, int $new_pos) : boolean {
       
        try {
            $this->conn->beginTransaction();
        }
        catch (PDOException $e) {
            throw new DBException("DB: Could not begin transaction", 0, $e);
        }

        try {
            $c_pos = $this->stGetItemPosition($id);
            
            if ($c_pos < $new_pos) {
                $lower = $c_pos + 1;
                $upper = $new_pos + 1;
                //shift towards the empty hole at c_pos
                $change = -1;
            }
            else if ($c_pos > $new_pos) {
                $lower = $new_pos;
                $upper = $c_pos;
                //make a hole at new_pos
                $change = 1;
            }
            else {
                //Leaving the same position
                return $this->conn->commit();
            }
            if ($this->stChangePositionsFromTo($lower, $upper, $change) !== $upper - $lower) {
                throw new ArgumentException("New position is out of bounds");
            }

            $this->stSetItemPosition($id, $new_pos);
    
            return $this->conn->commit();
        }
        catch(PDOException $e) {
            $this->conn->rollback();
            throw new DBException("DB: Internal error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }      
    }   

    /**
     * Gets the amount of the item with id $id
     *
     * @param integer $id ID of the item
     * @return integer Amount of the item with id $id
     * @throws PDOException
     * @throws ArgumentException Thrown when the item with the given id does not exist
     * @throws DataException Thrown when the retrieved data is not valid
     */
    private function stGetItemAmount(int $id) : int {
        $this->get_item_amnt_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $this->get_item_amnt_stmt->execute();

        if (!($amount = $this->get_item_amnt_stmt->fetch())) {
            throw new ArgumentException("Item with the given id does not exist");
        }

        //TODO: Flags to filter_var
        if (($amount = filter_var($amount, FILTER_VALIDATE_INT)) === null) {
            throw new DataException("Invalid data retrived from DB");
        }

        return $amount;
    }
    
    /**
     * Sets the amount of the item with id $id to $new_amount
     *
     * @param integer $id Item to change the value of
     * @param integer $new_amount New value of the amount of the item with id $id
     * @return integer Number of rows affected (0-1)
     * @throws PDOException
     */
    private function stSetItemAmount(int $id, int $new_amount) : int {
        $this->set_item_amnt_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->set_item_amnt_stmt->bindValue(':amount', $new_amount, PDO::PARAM_INT);
  
        $this->set_item_amnt_stmt->execute();

        return $this->set_item_amnt_stmt->rowCount();
    }

    /**
     * Adds an item type with name $name, if item type with this name already exists, does nothing
     *
     * @param string $name Name of the added item type
     * @return integer Probably number of rows added (May not work with some databases)
     * @throws PDOException
     */
    private function stTryAddItemType(string $name) : int {
        $this->try_add_item_type_stmt->bindValue(':name', $name, PDO::PARAM_STR);    
        $this->try_add_item_type_stmt->execute();

        return $this->try_add_item_type_stmt->rowCount();
    }

    /**
     * Gets the ID of the item type with the name $name
     *
     * @param string $name Name of the item type
     * @return integer ID of the item type with the name $name
     * @throws PDOException
     * @throws ArgumentException Thrown when item type with this name does not exist
     * @throws DBException
     * @throws DataException Thrown when the data retrieved from the DB were not valid
     */
    private function stGetItemTypeID(string $name) : int {
        $this->get_item_type_id_stmt->bindValue(':name', $name, PDO::PARAM_STR);
    
        $this->get_item_type_id_stmt->execute();

        if (!($db_res = $this->get_item_type_id_stmt->fetch())) {
            throw new ArgumentException("Item type with this name does not exist");
        }

        if (!isset($db_res['id'])) {
            throw new DBException("DB: Internal error");
        }

        //TODO: Add flags to validation
        if (($value = filter_var($db_res['id'], FILTER_VALIDATE_INT)) === null) {
            throw new DataException("Invalid data stored in the database");
        }
        return $value;
    }

    /**
     * Adds item to shopping list, with $type_id type, $amount amount and to position $position
     * If $type_id is already present in the list, adds $amount to it's amount
     *
     * @param integer $type_id ID of the type of the item
     * @param integer $amount Initial amount of the item or the amount to add if already present
     * @param integer $position Position at which to insert the item, ignored on update
     * @return void
     * @throws PDOException
     */
    private function stAddOrUpdateToList(int $type_id, int $amount, int $position) : boolean {
        $this->add_or_update_list_stmt->bindValue(':type_id', $type_id, PDO::PARAM_INT);
        $this->add_or_update_list_stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $this->add_or_update_list_stmt->bindValue(':position', $position, PDO::PARAM_INT);
        
        $this->add_or_update_list_stmt->execute();
    }

    /**
     * Tries to remove an item with ID $id, returns number of items actually removed
     *
     * @param integer $id ID of the item to remove
     * @return integer Number of items removed (0-1)
     * @throws PDOException
     */
    private function stRemoveItemFromList(int $id) : int {
        $this->rmv_item_from_list_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $this->rmv_item_from_list_stmt->execute();

        return $this->rmv_item_from_list_stmt->rowCount();
    }


    /**
     * Gets number of items present in the shopping cart
     *
     * @return integer Number of items in the shopping cart
     * @throws PDOException
     * @throws DBException
     */
    private function stGetCountItems() : int {
        //No params

        $this->get_count_items_stmt->execute();

        if (!($db_res = $this->get_count_items_stmt->fetch())) {
            throw new DBException("DB failed to count the items");
        }

        //This is not user data, dont need to validate
        return $db_res[0];
    }

    /**
     * Gets the position of item with ID $id
     *
     * @param integer $id ID of the item to get the position of
     * @return integer The position of the item with id $id
     * @throws PDOException 
     * @throws ArgumentException Thrown when item with given $id does not exist
     * @throws DataException Thrown when the data retrieved from DB are not valid
     */
    private function stGetItemPosition(int $id) : int {
        $this->get_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        $this->get_pos_stmt->execute();

        if (!($db_res = $this->get_pos_stmt->fetch())) {
            throw new ArgumentException("Item with given ID does not exist");
        }
        $position = $db_res['position'];

        //TODO: Add flags
        $val = filter_var($position, FILTER_VALIDATE_INT);
        if ($val === null) {
            throw new DataException("Invalid data stored in DB");
        }
        return $val;
    }

    /**
     * Changes position of the item $id to $new_pos
     *
     * @param integer $id ID of the item to change
     * @param integer $new_pos New position of the item
     * @return integer Number of rows changed (0 or 1)
     * @throws PDOException
     */
    private function stSetItemPosition(int $id, int $new_pos) : int {
        $this->set_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->set_pos_stmt->bindValue(':new_position', $new_pos, PDO::PARAM_INT);

        $this->set_pos_stmt->execute();
        
        return $this->set_pos_stmt->rowCount();
    }

    /**
     * Changes position of items from position $lower (including) to position $upper (excluding) by $change
     *
     * @param integer $lower start of the range of position to change
     * @param integer $upper one beyond the end of the positions to change
     * @param integer $change the amount to add to the positions
     * @return integer Number of rows changed
     * @throws PDOException
     */
    private function stChangePositionsFromTo(int $lower, int $upper, int $change) : int {
        if ($lower >= $upper) {
            return 0;
        }

        $this->change_pos_fromto_stmt->bindValue(':lower', $lower, PDO::PARAM_INT);
        $this->change_pos_fromto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT);
        $this->change_pos_fromto_stmt->bindValue(':amount', $change, PDO::PARAM_INT);

        $this->change_pos_fromto_stmt->execute();

        return $this->change_pos_fromto_stmt->rowCount();
    }

    /**
     * Changes position of items from position $lower to the end by $change
     *
     * @param integer $lower Inclusive lower bound of changed positions
     * @param integer $change Value to add to the positions
     * @return integer Number of changed rows
     * @throws PDOException
     */
    private function stChangePositionFrom(int $lower, int $change) {
        $this->change_pos_from_stmt->bindValue(':lower', $lower, PDO::PARAM_INT);
        $this->change_pos_from_stmt->bindValue(':amount', $change, PDO::PARAM_INT);
        
        $this->change_pos_from_stmt->execute();

        return $this->change_pos_from_stmt->rowCount();
    }

    /**
     * Changes position of items from the beggining to the position $upper by $change
     *
     * @param integer $upper Exclusive upper bound of the positions changed
     * @param integer $change Value to add to the positions
     * @return integer Number of changed rows
     * @throws PDOException
     */
    private function stChangePositionUpTo(int $upper, int $change) {
        $this->change_pos_upto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT);
        $this->change_pos_upto_stmt->bindValue(':amount', $change, PDO::PARAM_INT);
        
        $this->change_pos_upto_stmt->execute();

        return $this->change_pos_upto_stmt->rowCount();
    }
}