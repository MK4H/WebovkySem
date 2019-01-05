<form action="index.php?action=add_item" method="POST" id="add_item_form">
    <div>
        <label for="type_name">Item:</label>
        <input id="type_name" list="item_types" name="type_name" placeholder="Item type..." required <?= $this->preset_typename ?> />   
        <datalist id="item_types">
            <?php 
                foreach ($this->suggs as $sugg) {
                    echo "<option value=\"${sugg}\"/>";
                }
             ?>
        </datalist>
    </div>
    <div>
        <label for="amount">Amount:</label>
        <input id="amount" type="number" name="amount" placeholder="Item amount..." min="1" required <?= $this->preset_amount ?> />
    </div>
    <input type="submit" value="Add"/>
</form>