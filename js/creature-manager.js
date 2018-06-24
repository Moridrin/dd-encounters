// noinspection JSUnresolvedVariable
let params = mp_ssv_monster_manager_params;

let monsterManager = {

    addNew: function (containerId) {
        let container = document.getElementById(containerId);
        let tr = document.createElement('tr');
        let properties = {
            name: '',
            hp: '1D8',
            url: '',
        };

        tr.setAttribute('id', 'model_' + null);
        tr.dataset.properties = JSON.stringify(properties);

        generalFunctions.removeElement(document.getElementById('no-items'));
        container.appendChild(tr);

        this.edit(null);
        tr.querySelector('[name="name"]').focus();
    },

    edit: function (id) {
        this.closeEditor();
        generalFunctions.editor.current = id;
        generalFunctions.editor.isOpen = true;
        let tr = document.getElementById("model_" + id);
        let properties = jQuery.parseJSON(tr.dataset.properties);
        tr.setAttribute('class', 'inline-edit-row');

        let html =
            '<td colspan="5" class="colspanchange" id="editor">' +
            '   <fieldset class="inline-edit-col" style="width: 48%;">' +
            '      <legend class="inline-edit-legend" id="edit-type" data-edit-type="edit">Edit Player</legend>'
        ;
        html += generalFunctions.editor.getInputField('Name', 'name', properties.name, 'text', {'onkeydown': 'monsterManager.onKeyDown()'});
        html += generalFunctions.editor.getDiceInputField('Max HP', 'hp', properties.hp, {'onkeydown': 'monsterManager.onKeyDown()'}, {'addition': true});
        html +=
            '   </fieldset>' +
            '   <fieldset class="inline-edit-col" style="width: 48%; margin: 32px 2% 0;">'
        ;
        html += generalFunctions.editor.getInputField('Initiative Modifier', 'initiativeModifier', properties.initiativeModifier, 'number', {'onkeydown': 'monsterManager.onKeyDown()'});
        html += generalFunctions.editor.getInputField('URL', 'url', properties.url, 'text', {'onkeydown': 'monsterManager.onKeyDown()'});
        html +=
            '   </fieldset>' +
            '   <fieldset id="value_container" class="inline-edit-col" style="width: 30%; margin-top: 32px;">' +
            '   </fieldset>' +
            '   <div class="submit inline-edit-save">' +
            '       <button type="button" class="button cancel alignleft" onclick="monsterManager.cancel()">Cancel</button>' +
            '       <button type="button" class="button button-primary save alignright" onclick="monsterManager.saveEdit()">Save</button>' +
            '       <br class="clear">' +
            '   </div>' +
            '</td>'
        ;
        tr.innerHTML = html;

        jQuery('#model_' + id + ' select[name="type"]').select2({
            tags: true,
        });
    },

    deleteRow: function (id) {
        let tr = document.getElementById('model_' + id);
        let container = tr.parentElement;
        generalFunctions.removeElement(tr);
        if (id !== '') {
            jQuery.post(
                params.urls.ajax,
                {
                    action: params.actions.delete,
                    shared: params.isShared,
                    formId: params.formId,
                    id: id,
                },
                function (data) {
                    generalFunctions.ajaxResponse(data);
                }
            );
        }
        if (container.childElementCount === 0) {
            container.innerHTML = this.getEmptyRow();
        }
    },

    cancel: function () {
        this.closeEditor();
    },

    saveEdit: function () {
        let tr = document.getElementById('model_' + generalFunctions.editor.current);
        let id = generalFunctions.editor.current;
        let properties = JSON.parse(tr.dataset.properties);
        properties.name = tr.querySelector('input[name="name"]').value;
        properties.hp = tr.querySelector('input[name="hpC"]').value + 'D' + tr.querySelector('select[name="hpD"]').value + '+' + tr.querySelector('input[name="hpA"]').value;
        properties.initiativeModifier = tr.querySelector('input[name="initiativeModifier"]').value;
        properties.url = tr.querySelector('input[name="url"]').value;
        tr.dataset.properties = JSON.stringify(properties);
        jQuery.post(
            params.urls.ajax,
            {
                action: params.actions.save,
                id: id,
                name: properties.name,
                hp: properties.hp,
                initiativeModifier: properties.initiativeModifier,
                url: properties.url,
            },
            function (data) {
                if (generalFunctions.ajaxResponse(data)) {
                    let id = JSON.parse(data)['id'];
                    tr.setAttribute('id', 'model_' + id);
                    generalFunctions.editor.current = id;
                    monsterManager.closeEditor();
                }
            }
        );
    },

    closeEditor: function () {
        if (generalFunctions.editor.isOpen === false) {
            return;
        }
        let id = generalFunctions.editor.current;
        let tr = document.getElementById('model_' + id);
        if (id === null) {
            let container = tr.parentElement;
            generalFunctions.removeElement(tr);
            if (container.childElementCount === 0) {
                container.innerHTML = this.getEmptyRow();
            }
            generalFunctions.editor.current = null;
            generalFunctions.editor.isOpen = false;
            return;
        }
        let properties = JSON.parse(tr.dataset.properties);
        if (properties.name === '') {
            this.deleteRow(id);
        }
        tr.innerHTML =
            '<th class="check-column">' +
            '   <input type="checkbox" name="ids[]" value="' + id + '">' +
            '</th>' +
            '<td>' +
            '   <strong>' + properties.name + '</strong>' +
            '   <div class="row-actions">' +
            '       <span class="inline"><a href="javascript:void(0)" onclick="monsterManager.edit(\'' + id + '\')" class="editinline">Edit</a> | </span>' +
            '       <span class="trash"><a href="javascript:void(0)" onclick="monsterManager.deleteRow(\'' + id + '\')" class="submitdelete">Trash</a></span>' +
            '   </div>' +
            '</td>' +
            '<td>' + properties.hp + '</td>' +
            '<td>' + properties.initiativeModifier + '</td>' +
            '<td>' + properties.url + '</td>'
        ;
        tr.setAttribute('class', 'inactive');
        generalFunctions.editor.current = null;
        generalFunctions.editor.isOpen = false;
    },

    getEmptyRow: function () {
        return '' +
            '<tr id="no-items" class="no-items">' +
            '    <td class="colspanchange" colspan="8">No Items found</td>' +
            '</tr>'
            ;
    },

    onKeyDown: function () {
        if (event.keyCode === 13) {
            event.preventDefault();
            this.saveEdit();
            return false;
        }
    },
};
