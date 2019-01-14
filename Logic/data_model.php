<?php

require_once("Logic/exceptions.php");
require_once("Logic/db.php");

interface Shop {
    public function getSuggestions(string $forText) : array;
    public function getShoppingList() : array;
    public function getItem(int $id) : array;
    public function addItem(string $type_name, int $amount) : void;
    public function removeItem(int $id) : void;
    public function setItemAmount(int $id, int $new_amount) : void;
    public function changePosition(int $id, int $new_pos) : void;
}

class ShopData implements Shop {

    private $db;

    public function __construct(DB $db) {
        $this->db = $db;
    }

    /**
     * Gets item type names containing $forText
     *
     * @param string $forText Strinig to filter out any names that do not contain it
     * @return array Item type names containing $forText
     * @throws DBException Thrown when execution of statement fails inside the DB
     */
    public function getSuggestions(string $forText) : array {
        return $this->db->getSuggestions($forText);
    }

    /**
     * Gets list of items in the shopping cart
     *
     * @return void
     */
    public function getShoppingList() : array {
        return $this->db->getShoppingList();
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
        return $this->db->getItem($id);
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
     * @return void
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the $amount is <= 0 or $type_name is empty string 
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function addItem(string $type_name, int $amount) : void {
        if ($amount <= 0) {
            throw new ArgumentException("Amount should be greater than 0", 422);
        }

        if ($type_name === '') {
            throw new ArgumentException("Type name cannot be empty", 422);
        }


        $this->db->beginTransaction();
        
       
        try {
            $this->db->tryAddItemType($type_name);  
            $type_id = $this->db->getItemTypeID($type_name);
            $item_cnt = $this->db->getItemTypeInListCount();   

            $this->db->addOrUpdateToList($type_id, $amount, $item_cnt);  
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Removes item from the list and shifts all the items after it
     * to their new position.
     * Is atomic, on error changes nothing and results in exception.
     *
     * @param integer $id ID of the item to remove
     * @return void
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the item with the id $id does not eist
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function removeItem(int $id) : void {
        
        $this->db->beginTransaction();

        try {  
            $item_pos = $this->db->getItemPosition($id);    
            $this->db->removeItemFromList($id);
            $this->db->changePositionsFrom($item_pos, -1);
           
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
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
     * @return void
     * @throws DBException Thrown when there was an error in the DB
     * @throws ArgumentException Thrown when the item with the $id does not exist
     */
    public function setItemAmount(int $id, int $new_amount) : void {
        
        $this->db->beginTransaction();

        try {
            if ($new_amount <= 0) {
                if ($this->db->removeItemFromList($id) < 1) {
                    throw new ArgumentException("Item with the given id does not exist", 422);
                }          
            }
            else {
                if ($this->db->setItemAmount($id, $new_amount) < 1) {
                    throw new ArgumentException("Item with the given id does not exist", 422);
                }  
            }
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
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
     * @return void
     * @throws DBException Thrown when there was an error during the execution in the DB
     * @throws ArgumentException Thrown when item with $id does not exist or $new_pos is out of bounds
     * @throws DataException Thrown when the data retrieved from DB were not valid
     */
    public function changePosition(int $id, int $new_pos) : void {
       
        $this->db->beginTransaction();

        try {
            $c_pos = $this->db->getItemPosition($id);
            
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
                $this->db->commit();
                return;
            }
            
            if ($this->db->changePositionsFromTo($lower, $upper, $change) !== $upper - $lower) {
                throw new ArgumentException("New position is out of bounds", 422);
            }

            $this->db->setItemPosition($id, $new_pos);
    
            $this->db->commit();
        }
        catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }      
    }   

}