<?php

class ShopData {

    //TODO: Make lastError method, that returns last error that happened

    private $conn;

    private $sugg_stmt;
    private $list_stmt;
    private $get_item_stmt;
    private $get_item_amnt_stmt;
    private $set_item_amnt_stmt;
    private $add_item_type_stmt;
    private $get_item_type_id_stmt;
    private $add_item_to_list_stmt;
    private $rmv_item_from_list_stmt;
    private $get_item_count_stmt;
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
            $this->add_item_type_stmt = $this->conn->prepare($add_item_type_query);
            $this->get_item_type_id_stmt = $this->conn->prepare($get_item_type_id_query);
            $this->add_item_to_list_stmt = $this->conn->prepare($add_item_to_list_query);
            $this->rmv_item_from_list_stmt = $this->conn->prepare($rmv_item_from_list_query);
            $this->get_item_count_stmt = $this->conn->prepare($get_item_count_query);
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

    public function getSuggestions(string $forText) {
        try {
            if (!is_string($forText)) {              
                throw new ArgumentException("Expected string argument");
            }
    
            if ($this->sugg_stmt->bindValue(":name", "%$forText%", PDO::PARAM_STR) === false) {
                //Should throw PDOException, but just in case
                return false;
            }
            
            if (!$this->sugg_stmt->execute()) {
                //Should throw PDOException, but just in case
                return false;
            }
    
            return $this->sugg_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        catch (PDOException $e) {
            throw new DBException("Internal DB error", 0, $e);
        }
    }

    //Returns shopping list ordered by positions
    public function getShoppingList() {
        try {
            if ($this->list_stmt->execute() === false) {
                //Should throw PDOException, but just in case
                return false;
            }
    
            $val = $this->list_stmt->fetchAll();
            //TODO: Return just a cursor
            //TODO: possibly preprocess
            return $val;
        }
        catch (PDOException $e) {
            throw new DBException("Internal DB error", 0, $e);
        }
    }

    public function getItem(int $id) {
        try {
            if ($this->get_item_stmt->bindValue(':id', $id, PDO::PARAM_INT) === false) {
                return false;
            }
    
            if ($this->get_item_stmt->execute() === false){
                return false;
            }
    
            $val = $this->get_item_stmt->fetch();
            //TODO: Validate data from DB
    
            return $val;
        }
        catch (PDOException $e) {
            throw new DBException("Internal DB error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function addItem(string $type_name, int $amount) {
        if ($amount <= 0) {
            //TODO: Throw exception
            return false;
        }
        try {
            if ($this->conn->beginTransaction() === false) {
                return false;
            }
    
            if ($this->stAddItemType($type_name) === false) {
                $this->conn->rollback();
                return false;
            }
    
            if (($type_id = $this->stGetItemTypeID($type_name)) === false) {
                $this->conn->rollback();
                return false;
            }
    
    
            if (($item_cnt = $this->stGetItemCount()) === false){
                $this->conn->rollback();
                return false;
            }
     
            if ($this->stAddItemToList($type_id, $amount, $item_cnt) === false) {
                $this->conn->rollback();
                return false;
            }
    
            return $this->conn->commit();
        }
        catch (PDOException $e) {
            $this->conn->rollback();
            throw new DBException("Internal DB error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function removeItem(int $id) {
        try {
            if ($this->conn->beginTransaction() === false) {
                return false;
            }
    
            if (($item_pos = $this->stGetItemPosition($id)) === false){
                //TODO: Throw exception
                $this->conn->rollback();
                return false;
            }
    
            if ($this->stRemoveItemFromList($id) === false) {
                //TODO: Throw exception
                $this->conn->rollback();
                return false;
            }
    
            if ($this->stChangePositionFrom($item_pos, -1) === false) {
                //TODO: Throw exception
                $this->conn->rollback();
                return false;
            }
           
            return $this->conn->commit();
        }
        catch (PDOException $e) {
            $this->conn->rollback();
            throw new DBException("Internal DB error", 0, $e);
        }
        catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function setItemAmount(int $id, int $new_amount) {
        if ($new_amount <= 0) {
            return $this->stRemoveItemFromList($id);
        }

        if ($this->conn->beginTransaction() === false) {
            return false;
        }

        if ($this->stSetItemAmount($id, $new_amount) === false) {
            $this->conn->rollback();
            return false;
        }

        return $this->conn->commit();
    }

    public function changePosition(int $id, int $new_pos) {
       
        if ($this->conn->beginTransaction() === false) {
            return false;
        }

        if (($c_pos = $this->stGetItemPosition($id)) === false) {
            $this->conn->rollback();
            return false;
        }

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
            $this->conn->commit();
            return true;
        }

        if ($this->stChangePositionsFromTo($lower, $upper, $change) === false) {
            //TODO: throw exception
            $this->conn->rollback();
            return false;
        }

        if ($this->stSetItemPosition($id, $new_pos) === false){
            $this->conn->rollback();
            return false;
        }

        return $this->conn->commit();
    }   

    private function stGetItemAmount(int $id) {

    }
    
    private function stSetItemAmount(int $id, int $new_amount) {
        if ($this->set_item_amnt_stmt->bindValue(':id', $id, PDO::PARAM_INT) === false ||
            $this->set_item_amnt_stmt->bindValue(':amount', $new_amount, PDO::PARAM_INT) === false) {
            return false;
        }

        if ($this->set_item_amnt_stmt->execute() === false){
            return false;
        }

        if ($this->set_item_amnt_stmt->rowCount() !== 1) {
            return false;
        }

        return true;
    }

    private function stAddItemType($name) {
        if ($this->add_item_type_stmt->bindValue(':name', $name, PDO::PARAM_STR) === false) {
            return false;
        }
        
        if ($this->add_item_type_stmt->execute() === false){
            //TODO: Exception
            return false;
        }

        return true;
    }

    private function stGetItemTypeID($name) {
        if ($this->get_item_type_id_stmt->bindValue(':name', $name, PDO::PARAM_STR) === false) {
            return false;
        }
    
        if ($this->get_item_type_id_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        if (($db_res = $this->get_item_type_id_stmt->fetch()) === false) {
            return false;
        }

        if (!isset($db_res['id'])) {
            return null;
        }

        //TODO: Add flags to validation
        $value = filter_var($db_res['id'], FILTER_VALIDATE_INT);
        return $value;
    }

    private function stAddItemToList(int $type_id, int $amount, int $position) {
        if ($this->add_item_to_list_stmt->bindValue(':type_id', $type_id, PDO::PARAM_INT) === false ||
            $this->add_item_to_list_stmt->bindValue(':amount', $amount, PDO::PARAM_INT) === false ||
            $this->add_item_to_list_stmt->bindValue(':position', $position, PDO::PARAM_INT) === false){
            return false;
        }

        if ($this->add_item_to_list_stmt->execute() === false) {
            return false;
        }

        return true;
    }

    private function stRemoveItemFromList($id) {
        if ($this->rmv_item_from_list_stmt->bindValue(':id', $id, PDO::PARAM_INT) === false) {
            return false;
        }

        if ($this->rmv_item_from_list_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        if ($this->rmv_item_from_list_stmt->rowCount() !== 1) {
            //TODO: throw exception
            return false;
        }

        return true;
    }

    private function stGetItemCount() {
        //No params

        if ($this->get_item_count_stmt->execute() === false) {
            //TODO: throw exception;
            return false;
        }

        $db_res = $this->get_item_count_stmt->fetch();

        //TODO: Check if there is a result
        return $db_res[0];
    }

    private function stGetItemPosition($id) {
        if ($this->get_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT) === false) {
            return false;
        }
        
        if ($this->get_pos_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        //TODO: Check if there is a result
        $db_res = $this->get_pos_stmt->fetch();

        return $db_res['position'];
    }

    private function stSetItemPosition(int $id, int $new_pos) {
        //TODO: Maybe create special query for this
        if ($this->set_pos_stmt->bindValue(':id', $id, PDO::PARAM_INT) === false ||
            $this->set_pos_stmt->bindValue(':new_position', $new_pos, PDO::PARAM_INT) === false) {
            return false;
        }

        if ($this->set_pos_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }
        
        return $this->set_pos_stmt->rowCount() === 1;
    }

    //from is inclusive, to is exclusive
    private function stChangePositionsFromTo(int $lower, int $upper, int $change) {
        //TODO: type check params
        if ($lower >= $upper) {
            return;
        }

        if ($this->change_pos_fromto_stmt->bindValue(':lower', $lower, PDO::PARAM_INT) === false ||
            $this->change_pos_fromto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT) === false ||
            $this->change_pos_fromto_stmt->bindValue(':amount', $change, PDO::PARAM_INT) === false) {
                return false;
            }

        if ($this->change_pos_fromto_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        return $this->change_pos_fromto_stmt->rowCount() === $upper - $lower;
    }

    /**
     * Undocumented function
     *
     * @param integer $lower
     * @param integer $change
     * @return mixed
     */
    private function stChangePositionFrom(int $lower, int $change) {
        if ($this->change_pos_from_stmt->bindValue(':lower', $lower, PDO::PARAM_INT) === false ||
            $this->change_pos_from_stmt->bindValue(':amount', $change, PDO::PARAM_INT) === false) {
                return false;
        }
        
        if ($this->change_pos_from_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        return $this->change_pos_from_stmt->rowCount();
    }

    private function stChangePositionUpTo(int $upper, int $change) {
         if ($this->change_pos_upto_stmt->bindValue(':upper', $upper, PDO::PARAM_INT) === false ||
            $this->change_pos_upto_stmt->bindValue(':amount', $change, PDO::PARAM_INT) === false) {
                return false;
        }
        
        if ($this->change_pos_upto_stmt->execute() === false) {
            //TODO: throw exception
            return false;
        }

        return $this->change_pos_upto_stmt->rowCount();
    }
}