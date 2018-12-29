<?php
function out_table_row(int $id, int $pos, string $name, int $amount) {
    require("FrontEnd/table_row.php");
}

function out_table_heading() {
    require("FrontEnd/table_heading.php");
}

function out_edit_td() {
    require("FrontEnd/edit_td.php");
}

function out_table() {
    require("FrontEnd/table.php");
}

