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

