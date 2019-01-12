
class Row {

    constructor(table, element) {
        this.table = table;
        this.element = element;
       
        this._init();
    }

    replace(newRowElem) {
        this.element.parentNode.replaceChild(newRowElem, this.element);
        this.element = newRowElem;
        this._init();
    } 

    delete() {
        let data = new FormData();
        data.append("item_id", this.item_id);
    
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
            this.table.replace(newTable);
        })
        .catch(error => {
            reportError("Error in delete row:", error);
        });
    }

    switchToEdit() {
        this._switchAmntEditVis(true);
    }

    saveEditAmount() {
        let amnt_edit = this.element.getElementsByClassName("amnt_edit")[0];
          
        let data = new FormData();
        //Validate in HTML
        let new_amnt = amnt_edit.value;
    
        if (new_amnt <= 0) {
            alert("Amount has to be greater than zero");
            return;
        }

        data.append("item_id", this.item_id);
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
            this.replace(newRow);
        })
        .catch(error => {
            if (error instanceof Response) {
                amnt_edit.classList.add("error"); 
            }
            reportError("Error while chaning amounts:", error);
        });   
    }
    
    cancelEditAmount() {
        this._switchAmntEditVis(false);
    }

    _init() {
        this.item_id = this.element.getAttribute("item_id");

        let amnt_text_holder = this.element.getElementsByClassName("amnt_text_holder")[0];
        let amnt_edit_holder = this.element.getElementsByClassName("amnt_edit_holder")[0];
    
        let normal_buttons = this.element.getElementsByClassName("normal_buttons")[0];
        let edit_buttons = this.element.getElementsByClassName("edit_buttons")[0];
    
        this.amnt_text_holder_disp = amnt_text_holder.style.display;
        this.amnt_edit_holder_disp = amnt_edit_holder.style.display;
        this.normal_buttons_disp = normal_buttons.style.display;
        this.edit_buttons_disp = edit_buttons.style.display;

        this._switchAmntEditVis(false);

        let edit_button = this.element.getElementsByClassName("edit_button")[0];
        let delete_button = this.element.getElementsByClassName("delete_button")[0];
        let save_button = this.element.getElementsByClassName("save_button")[0];
        let cancel_button = this.element.getElementsByClassName("cancel_button")[0];
        
        //Capture row
        delete_button.addEventListener("click", event => this.delete());
        edit_button.addEventListener("click", event => this.switchToEdit());
        save_button.addEventListener("click", event => this.saveEditAmount());
        cancel_button.addEventListener("click", event => this.cancelEditAmount());
    
        this.element.addEventListener('dragstart', dragStart);
        this.element.addEventListener('dragover', allowDrop);
        this.element.addEventListener('drop', drop);
    }

    _switchAmntEditVis(edit) {

        if (edit) {
            table.switchAllFromEdit();
        }

        //TODO: Check if the collections are not empty before accessing
        let amnt_text_holder = this.element.getElementsByClassName("amnt_text_holder")[0];
        let amnt_edit_holder = this.element.getElementsByClassName("amnt_edit_holder")[0];
    
        let normal_buttons = this.element.getElementsByClassName("normal_buttons")[0];
        let edit_buttons = this.element.getElementsByClassName("edit_buttons")[0];
    
        amnt_text_holder.style.display = (!edit ? this.amnt_text_holder_disp : "none");
        amnt_edit_holder.style.display = (edit ? this.amnt_edit_holder_disp : "none");
    
        normal_buttons.style.display = (!edit ? this.normal_buttons_disp : "none");
        edit_buttons.style.display = (edit ? this.edit_buttons_disp : "none");
    }
}

class Table {

    constructor(element) {
        this.elem = element;   
        this._createRows(); 
    }

    replace(newTableElement) {
        //TODO: Maybe cleanup, see if leaking memory
        this.elem.parentNode.replaceChild(newTableElement, this.elem);
        this.elem = newTableElement;
        this._createRows();
    }

    switchAllFromEdit() {
        this.rows.forEach(row => {
            row.cancelEditAmount();
        });
    }

    _createRows() {
        let itemRows = this.elem.getElementsByTagName("tbody")[0]
                                .getElementsByTagName("tr");
        this.rows = new Array();
        for (const rowElem of itemRows) {
            this.rows.push(new Row(this, rowElem));
        }
    }
}

var table;

function elementFromHTML(html) {
    let template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;
    return template.content.firstChild;
}

function reportError(heading, error) {
    if (error instanceof Response) {
        reportResponseError(heading, error);
    }
    else if (error instanceof Error) {
        let text = `${heading}
        Error: ${error.name}
        Message: ${error.message}
        `;
        alert(text);
    }
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
        table.replace(newTable);
    })
    .catch(error => {
        reportError("Error while swapping rows:", error);
    });
}

function init(event){
    //TODO: Check if it is not null
    table = new Table(document.getElementById("main_table"));
}

window.onload = init;