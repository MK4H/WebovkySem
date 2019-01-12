<?php

abstract class View {
    public abstract function render();
}

//TODO: Singleton
class EditTDView extends View {
    public function __construct() 
    { }

    public function render() {
        require("FrontEnd/Templates/edit_td.php");
    }
}

class TableRowView extends View {
    
    private $id;
    private $name;
    private $amount;
    private $pos;
    private $edit_td;

    public function __construct(int $id, string $name, int $amount, int $pos) {
        $this->id = htmlspecialchars($id);
        $this->name = htmlspecialchars($name);
        $this->amount = htmlspecialchars($amount);
        $this->pos = htmlspecialchars($pos);
        $this->edit_td = new EditTDView();
    }

    public function render() {
        require("FrontEnd/Templates/table_row.php");
    }

    private function incl_edit_td() {
        $this->edit_td->render();
    }
}

//TODO: Singleton
class TableHeadingView extends View {
    public function __construct() 
    { }

    public function render() {
        require("FrontEnd/Templates/table_heading.php");
    }
}

class TableView extends View {

    private $rows;
    private $table_heading;

    public function __construct(Shop $data) {
        $shopping_list = $data->getShoppingList();

        $this->rows = [];
        foreach ($shopping_list as $row_data) {
            $this->rows[] = new TableRowView($row_data['id'], $row_data['name'], $row_data['amount'], $row_data['position']);
        }
        
        $this->table_heading = new TableHeadingView();
    }

    public function render() {
        require("FrontEnd/Templates/table.php");
    }

    private function incl_table_heading() {
        $this->table_heading->render();
    }

    private function incl_table_rows() {
        foreach ($this->rows as $row) {
            $row->render();            
        }
    }
}

class AddItemFormView extends View {
    
    private $suggs;
    private $preset_typename;
    private $preset_amount;

    public function __construct(Shop $data, array $preset_values) {
        $suggestions = $data->getSuggestions("");

        $this->suggs = [];

        foreach ($suggestions as $sugg) {
            $this->suggs[] = htmlspecialchars($sugg);
        }

        if (isset($preset_values['type_name'])) {
            $this->preset_typename =  "value=\"" . htmlspecialchars($preset_values['type_name']) . "\"";
        }
        else if (isset($preset_values['type_name_error'])) {
            $this->preset_typename = "class=\"error\"";
        }
        else {
            $this->preset_typename = "";
        }

        if (isset($preset_values['amount'])) {
            $this->preset_amount = "value=\"" . htmlspecialchars($amount) . "\"";
        }
        else if (isset($preset_values['amount_error'])) {
            $this->preset_amount = "class=\"error\"";
        }
        else {
            $this->preset_amount = "";
        }
    }

    public function render() {
        require("FrontEnd/Templates/add_item_form.php");
    }
}

class PageView extends View {

    private $table;
    private $add_item_form;

    public function __construct(Shop $data, array $preset_values) {
        $this->table = new TableView($data);
        $this->add_item_form = new AddItemFormView($data, $preset_values);
    }

    public function render() {
        require("FrontEnd/Templates/main_page.php");
    }

    private function incl_table() {
        $this->table->render();
    }

    private function incl_add_item_form() {
        $this->add_item_form->render();
    }
}
