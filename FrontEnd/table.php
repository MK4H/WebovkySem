<table id="main_table">
    <thead>
        <?php include("FrontEnd/table_heading.php") ?>
    </thead>
    <tbody>
    <?php
        include_once('Logic/data_model.php');
        if (!isset($data)) {
            $data = new ShopData();
        }

        if (($list = $data->getShoppingList()) === false) {
            //TODO: Show exception
            die("Could not get shopping list");
        }

        foreach ($list as $key => $value) {
            $id = $value['id'];
            $name = $value['name'];
            $amount = $value['amount'];
            $pos = $value['position'];
            include("FrontEnd/table_row.php");
        }
    ?>
    </tbody>
</table>