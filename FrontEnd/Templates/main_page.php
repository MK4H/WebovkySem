<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Karel Maděra">
    <meta name="description" content="Simple shopping list implementation
    that provides basic suggestion functionality. 
    Part of the Web Applications course at the MFF UK."/>

    <link rel="stylesheet" href="FrontEnd/base_style.css"/>
    <script type="text/javascript" src="JS/script.js"></script>

    <title>Shopping List</title>
</head>
<body>
    <main>
    <h1>Shopping List</h1>
    <section>
        <div id="table_div">
            <?php $this->incl_table() ?>  
        </div> 
    </section>
    <section>
        <h2>Add Item</h2>
        <?php $this->incl_add_item_form() ?>
    </section>
    </main>
</body>