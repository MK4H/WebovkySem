<form action="index.php?action=add_item" method="POST">
    <div>
        <lable for="type_name">Item:</lable>
        <input id="type_name" list="item_types" name="type_name" required <?= $this->preset_typename ?> />   
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
        <input id="amount" type="number" name="amount" required <?= $this->preset_amount ?> />
        
        
    </div>
    <input type="submit" value="submit"/>
</form>