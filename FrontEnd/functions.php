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

function out_whole_page(array $preset_values) {
    require("FrontEnd/main_page.php");
}

function out_text_error(int $code, string $message) {
    http_response_code($code);
    header('Content-Type: text/plain');
    echo $message;
    exit;
}

