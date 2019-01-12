<?php
//Thanks to only index.php receiving request, all paths should be relative to it
require("db_config.php");
$conn_string = "mysql:host={$db_config['server']};dbname={$db_config['database']}";
$user = $db_config['login'];
$passwd = $db_config['password'];

$sugg_query = <<<EOT
    SELECT name
    FROM items
    WHERE name LIKE :name
EOT;

$list_query = <<<EOT
    SELECT name, list.id AS id, amount, position
    FROM list
        INNER JOIN items ON items.id = list.item_id
    ORDER BY position
EOT;

$get_item_query = <<<EOT
    SELECT items.name, list.id AS id, list.amount, list.position
    FROM list 
        INNER JOIN items ON items.id = list.item_id
                        AND list.id = :id
EOT;

$get_item_amnt_query = <<<EOT
    SELECT amount
    FROM list
    WHERE id = :id
EOT;

$set_item_amnt_query = <<<EOT
    UPDATE list
    SET amount = :amount
    WHERE id = :id
EOT;

$try_add_item_type_query = <<<EOT
    INSERT IGNORE INTO items(name)
    VALUES (:name)
EOT;

$get_item_type_id_query = <<<EOT
    SELECT id
    FROM items
    WHERE name = :name
EOT;

$add_or_update_to_list_query = <<<EOT
    INSERT INTO list(item_id, amount, position)
    VALUES (:type_id, :amount, :position)
    ON DUPLICATE KEY UPDATE amount = amount + :amount
EOT;

$rmv_item_from_list_query = <<<EOT
    DELETE FROM list
    WHERE id = :id
EOT;

$get_item_count_query = <<<EOT
    SELECT count(*)
    FROM list
EOT;

$get_pos_query = <<<EOT
    SELECT position
    FROM list
    WHERE id = :id
EOT;

$set_pos_query = <<<EOT
    UPDATE list
    SET position = :new_position
    WHERE id = :id
EOT;

$change_pos_fromto_query = <<<EOT
    UPDATE list
    SET position = position + :amount
    WHERE :lower <= position
        AND position < :upper
EOT;

$change_pos_from_query = <<<EOT
    UPDATE list
    SET position = position + :amount
    WHERE :lower <= position
EOT;

$change_pos_upto_query = <<<EOT
    UPDATE list
    SET position = position + :amount
    WHERE position < :upper
EOT;