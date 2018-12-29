<?php 
    $id = htmlspecialchars($id);
    $pos = htmlspecialchars($pos);
    echo "<tr item_id=\"${id}\" pos=\"${pos}\" draggable=\"true\" class=\"item_row\">"
?>
    <td><?= htmlspecialchars($name)?></td>
    <td>
        <div class="amnt_text_holder">
            <?= htmlspecialchars($amount)?>
        </div>
        <div class="amnt_edit_holder">
            <input class="amnt_edit" type="number" name="amount"/>
        </div>              
    </td>
    <td><?php require("FrontEnd/edit_td.php")?></td>
<?= "</tr>"?>