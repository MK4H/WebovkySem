<tr data-item_id="<?= $this->id ?>" data-pos="<?= $this->pos ?>" draggable="true" class="item_row">

    <td class="name_td"><?= $this->name ?></td>
    <td class="amnt_td">
        <div class="amnt_text_holder">
            <?= $this->amount ?>
        </div>
        <div class="amnt_edit_holder">
            <input class="amnt_edit" type="number" name="amount" placeholder="New amount..." min="1" required/>
        </div>              
    </td>
    <td class="edit_td"><?php $this->incl_edit_td() ?></td>
</tr>