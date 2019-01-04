
function elementFromHTML(html) {
    let template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;
    return template.content.firstChild;
}

function replaceTable(newTable) {
    let oldTable = document.getElementById("main_table");
    oldTable.parentNode.replaceChild(newTable, oldTable);
    initTable(newTable);
}

function replaceRow(newRow, oldRow) {
    oldRow.parentNode.replaceChild(newRow, oldRow);
    initRow(newRow);
}

function reportResponseError(heading, response) {
    response.text().then(bodyText => {
        let text = `${heading}
        Status: ${response.status} ${response.statusText}
        Message: ${bodyText}
        `;
        alert(text);
    });   
}

function deleteRow(row) {
    let item_id = row.getAttribute("item_id");
    let data = new FormData();
    data.append("item_id", item_id);

    fetch("/index.php?action=delete_item", {
        method: "POST",
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw response;
        }
        return response.text();
    })
    .then(html => {
        let newTable = elementFromHTML(html);
        replaceTable(newTable);
    })
    .catch(response => {
        reportResponseError("Error in delete row:", response);
    });
}

function allowDrop(event) {
    event.preventDefault();
}

function dragStart(event) {
    let item_id = this.getAttribute("item_id");
    event.dataTransfer.setData("text/plain", item_id);
}

function drop(event) {
    event.preventDefault();
    let dragged_item_id = event.dataTransfer.getData("text/plain");
    event.dataTransfer.clearData();
    
    let endPos = this.getAttribute("pos");

    let data = new FormData();
    data.append("item_id", dragged_item_id);
    data.append("end_pos", endPos);

    fetch("/index.php?action=change_position", {
        method: "POST",
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw response;
        }
        return response.text();
    })
    .then(html => {
        let newTable = elementFromHTML(html);
        replaceTable(newTable);
    })
    .catch(response => {
        reportResponseError("Error while swapping rows:", response);
    });
}

function switchAmountEditVisibility(row, edit) {
    //TODO: Check if the collections are not empty before accessing
    let amnt_text_holder = row.getElementsByClassName("amnt_text_holder")[0];
    let amnt_edit_holder = row.getElementsByClassName("amnt_edit_holder")[0];

    let normal_buttons = row.getElementsByClassName("normal_buttons")[0];
    let edit_buttons = row.getElementsByClassName("edit_buttons")[0];

    amnt_text_holder.style.display = (!edit ? "unset" : "none");
    amnt_edit_holder.style.display = (edit ? "unset" : "none");

    normal_buttons.style.display = (!edit ? "unset" : "none");
    edit_buttons.style.display = (edit ? "unset" : "none");
}

function switchToEdit(row) {
    switchAmountEditVisibility(row, true);
}

function saveEditAmount(row) {
    let amnt_edit = row.getElementsByClassName("amnt_edit")[0];
      
    let data = new FormData();
    let item_id = row.getAttribute("item_id");
    //TODO: Validate that its a positive number
    let new_amnt = amnt_edit.value;

    data.append("item_id", item_id);
    data.append("new_amount", new_amnt);

    fetch("/index.php?action=change_amount", {
        method: "POST",
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw response;
        }
        return response.text();
    })
    .then(html => {
        let newRow = elementFromHTML(html);
        replaceRow(newRow, row);
    })
    .catch(response => {
        cancelEditAmount(row);
        reportResponseError("Error while chaning amounts:", response);
    });   
}

function cancelEditAmount(row) {
    switchAmountEditVisibility(row, false);
}

function initRow(row) {
    //TODO: Check if the collections are not empty
    let edit_button = row.getElementsByClassName("edit_button")[0];
    let delete_button = row.getElementsByClassName("delete_button")[0];
    let save_button = row.getElementsByClassName("save_button")[0];
    let cancel_button = row.getElementsByClassName("cancel_button")[0];
    
    //Capture row
    delete_button.addEventListener("click", event => deleteRow(row));
    edit_button.addEventListener("click", event => switchToEdit(row));
    save_button.addEventListener("click", event => saveEditAmount(row));
    cancel_button.addEventListener("click", event => cancelEditAmount(row));

    row.addEventListener('dragstart', dragStart);
    row.addEventListener('dragover', allowDrop);
    row.addEventListener('drop', drop);
}

function initTable(tableElement) {
    //Check if the tbody and tr exist
    let itemRows = tableElement.getElementsByTagName("tbody")[0]
                                .getElementsByTagName("tr");

    for (const row of itemRows) {
        initRow(row);
    }
}

function init(event){
    //TODO: Check if it is not null
    initTable(document.getElementById("main_table")); 
}

window.onload = init;