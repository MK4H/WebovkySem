<form action="index.php" method="POST">
    <div>
        <lable for="type_name">Item:</lable>
        <input id="type_name" list="item_types" name="type_name"/>
        <datalist id="item_types">
            <?php
                include_once("Logic/data_model.php");
                if (!isset($data)) {
                    $data = new ShopData();
                }
                //TODO: Catch possible exceptions and show them
                if (($suggestions = $data->getSuggestions("")) === false) {
                    //TODO: show exception
                    die("Could not load suggestions");
                }

                foreach ($suggestions as $value) {
                    $escapedValue = htmlspecialchars($value);
                    echo "<option value=\"${escapedValue}\"/>";
                }
            ?>
        </datalist>
    </div>
    <div>
        <label for="amount">Amount:</label>
        <input id="amount" type="number" name="amount"/> 
    </div>
    <input type="submit" value="submit"/>
    <input type="hidden" name="action" value="add_item"/>
</form>