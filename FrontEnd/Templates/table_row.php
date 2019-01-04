<tr item_id="<?= $this->id ?>" pos="<?= $this->pos ?>" draggable="true" class="item_row">

    <td><?= $this->name ?></td>
    <td>
        <div class="amnt_text_holder">
            <?= $this->amount ?>
        </div>
        <div class="amnt_edit_holder">
            <input class="amnt_edit" type="number" name="amount" required/>
        </div>              
    </td>
    <td><?php $this->incl_edit_td() ?></td>
</tr>