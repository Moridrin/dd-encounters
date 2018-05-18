// noinspection JSUnresolvedVariable
let params = mp_ssv_creature_manager_params;

let creatureManager = {

    editor: {

        current: null,
        isOpen: false,

        getInputPlayer: function (title, name, value, type, events) {
            // console.log(events.onkeydown);
            // if (events.onkeydown === undefined) {
            events.onkeydown = 'creatureManager.editor.onKeyDown()';
            // }
            let eventsString = '';
            for (let [eventName, event] of Object.entries(events)) {
                eventsString += eventName + '="' + event + '" ';
            }
            let html =
                '<label id="' + name + '_container">' +
                '   <span class="title">' + title + '</span>' +
                '   <span class="input-text-wrap">'
            ;
            if (type === 'textarea') {
                html += '<textarea name="' + name + '">' + value + '</textarea>';
            } else {
                html += '<input type="' + type + '" name="' + name + '" value="' + value + '" autocomplete="off" style="width: 100%;" ' + eventsString + '>';
            }
            html +=
                '   </span>' +
                '</label>'
            ;
            return html;
        },

        getCheckboxInputPlayer: function (title, name, value, description, events) {
            let checked = (value === true || value === 'true') ? 'checked="checked"' : '';
            let eventsString = '';
            for (let [eventName, event] of Object.entries(events)) {
                eventsString += eventName + '="' + event + '" ';
            }
            return '' +
                '<label>' +
                '   <span class="title">' + title + '</span>' +
                '   <span class="input-text-wrap">' +
                '       <input type="checkbox" name="' + name + '" value="true" ' + checked + ' title="' + description + '" ' + eventsString + '>' +
                '   </span>' +
                '</label>'
                ;
        },

        getSelectInputPlayer: function (title, name, options, values, events) {
            let multiple = name.endsWith('[]') ? ' multiple="multiple"' : '';
            let eventsString = '';
            if (!Array.isArray(values)) {
                values = [values];
            }
            for (let [eventName, event] of Object.entries(events)) {
                eventsString += eventName + '="' + event + '" ';
            }
            let html =
                '<label>' +
                '   <span class="title">' + title + '</span>' +
                '   <span class="input-text-wrap">'
            ;
            html += '<select name="' + name + '" style="width: 100%;" ' + eventsString + multiple + '>';
            if (options instanceof Object) {
                options = Object.values(options);
            }
            for (let i = 0; i < options.length; ++i) {
                if (values.indexOf(options[i]) !== -1) {
                    html += '<option selected="selected">' + options[i] + '</option>';
                } else {
                    html += '<option>' + options[i] + '</option>';
                }
            }
            html += '</select>';
            html +=
                '   </span>' +
                '</label>'
            ;
            return html;
        },

        onKeyDown: function () {
            let $nameInput = event.path[0];
            let editType = document.getElementById('edit-type').dataset.editType;
            if (editType === 'edit') {
                if (event.keyCode === 13) {
                    creatureManager.saveEdit();
                    event.preventDefault();
                    return false;
                } else {
                    $nameInput.setCustomValidity('');
                    $nameInput.reportValidity();
                }
            } else if (editType === 'customize') {
                if (event.keyCode === 13) {
                    creatureManager.saveCustomization();
                    event.preventDefault();
                    return false;
                }
            }
        },

        addTextValueContainer: function (value) {
            document.getElementById('value_container').innerHTML =
                '<div class="inline-edit-col">' +
                '   <label>' +
                '       <span class="title">Value</span>' +
                '       <span class="input-text-wrap">' +
                '            <input type="text" name="value" value="' + value + '" autocomplete="off" data-old-value="' + value + '">' +
                '       </span>' +
                '   </label>' +
                '</div>'
            ;
        },

        addSelectValueContainer: function (options, tags) {
            let tr = document.getElementById('model_' + this.current);
            let properties = JSON.parse(tr.dataset.properties);
            let selected = properties.value;
            if (selected === undefined || !Array.isArray(selected)) {
                selected = [];
            }
            selected.forEach(function (value) {
                if (options.indexOf(value) === -1) {
                    options.push(value);
                }
            });
            document.getElementById('value_container').innerHTML = this.getSelectInputPlayer('Options', 'options[]', options, selected, []);
            jQuery('[name="options[]"]').select2({
                tags: tags,
                tokenSeparators: [';']
            });
        },

        removeValueContainer: function () {
            document.getElementById('value_container').innerHTML = '';
        },

        switchNamePlayerToSelect: function () {
            let container = document.getElementById('name_container');
            let value = container.querySelector('[name="name"]').value;
            let newPlayer = document.createElement('div');
            newPlayer.innerHTML = this.getSelectInputPlayer('Name', 'name', params.roles, value, []);
            container.parentElement.replaceChild(newPlayer, container);
        },

        switchNamePlayerToInput: function () {
            let container = document.getElementById('name_container');
            let value = container.querySelector('[name="name"]').value;
            let newPlayer = document.createElement('div');
            newPlayer.innerHTML = this.getInputPlayer('Name', 'name', value, 'text', []);
            container.parentElement.replaceChild(newPlayer, container);
        },
    },

    addNew: function (containerId) {
        let container = document.getElementById(containerId);
        let tr = document.createElement('tr');
        let properties = {
            name: '',
            level: '0',
            hp: '10',
        };

        tr.setAttribute('id', 'model_' + null);
        tr.dataset.properties = JSON.stringify(properties);

        generalFunctions.removeElement(document.getElementById('no-items'));
        container.appendChild(tr);

        console.log(tr);
        console.log(container);

        this.edit(null);
        tr.querySelector('[name="name"]').focus();
    },

    edit: function (id) {
        this.closeEditor();
        this.editor.current = id;
        this.editor.isOpen = true;
        let tr = document.getElementById("model_" + id);
        let properties = jQuery.parseJSON(tr.dataset.properties);
        console.log(properties);
        tr.setAttribute('class', 'inline-edit-row');

        let html =
            '<td colspan="5" class="colspanchange" id="editor">' +
            '   <fieldset class="inline-edit-col" style="width: 30%;">' +
            '      <legend class="inline-edit-legend" id="edit-type" data-edit-type="edit">Edit Player</legend>'
        ;
        html += this.editor.getInputPlayer('Name', 'name', properties.name, 'text', []);
        html +=
            '   </fieldset>' +
            '   <fieldset class="inline-edit-col" style="width: 30%; margin: 32px 2% 0;">'
        ;
        html += this.editor.getInputPlayer('Level', 'level', properties.level, 'number', []);
        html +=
            '   </fieldset>' +
            '   <fieldset class="inline-edit-col" style="width: 30%; margin: 32px 2% 0;">'
        ;
        html += this.editor.getInputPlayer('HP', 'hp', properties.hp, 'number', []);
        html +=
            '   </fieldset>' +
            '   <fieldset id="value_container" class="inline-edit-col" style="width: 30%; margin-top: 32px;">' +
            '   </fieldset>' +
            '   <div class="submit inline-edit-save">' +
            '       <button type="button" class="button cancel alignleft" onclick="creatureManager.cancel()">Cancel</button>' +
            '       <button type="button" class="button button-primary save alignright" onclick="creatureManager.saveEdit()">Save</button>' +
            '       <br class="clear">' +
            '   </div>' +
            '</td>'
        ;
        tr.innerHTML = html;

        jQuery('#model_' + id + ' select[name="type"]').select2({
            tags: true,
        });
        this.typeChanged();
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

    typeChanged: function () {
        let type = jQuery('#model_' + this.editor.current + ' select[name=type]').val();
        if (type === 'role_checkbox') {
            this.editor.switchNamePlayerToSelect();
        } else {
            this.editor.switchNamePlayerToInput();
        }
        if (type === 'hidden') {
            this.editor.addTextValueContainer('');
        } else if (type === 'select') {
            this.editor.addSelectValueContainer([], true);
        } else if (type === 'role_select') {
            this.editor.addSelectValueContainer(params.roles, false);
        } else {
            this.editor.removeValueContainer();
        }
    },

    cancel: function () {
        this.closeEditor();
    },

    saveEdit: function () {
        let tr = document.getElementById('model_' + this.editor.current);
        let id = this.editor.current;
        let properties = JSON.parse(tr.dataset.properties);
        properties.name = tr.querySelector('input[name="name"]').value;
        properties.level = tr.querySelector('input[name="level"]').value;
        properties.hp = tr.querySelector('input[name="hp"]').value;
        tr.dataset.properties = JSON.stringify(properties);
        jQuery.post(
            params.urls.ajax,
            {
                action: params.actions.save,
                id: id,
                name: properties.name,
                level: properties.level,
                hp: properties.hp,
            },
            function (data) {
                if (generalFunctions.ajaxResponse(data)) {
                    let id = JSON.parse(data)['id'];
                    tr.setAttribute('id', 'model_' + id);
                    creatureManager.editor.current = id;
                    creatureManager.closeEditor();
                }
            }
        );
    },

    closeEditor: function () {
        if (this.editor.isOpen === false) {
            return;
        }
        let id = this.editor.current;
        let tr = document.getElementById('model_' + id);
        if (id === null) {
            let container = tr.parentElement;
            generalFunctions.removeElement(tr);
            if (container.childElementCount === 0) {
                container.innerHTML = this.getEmptyRow();
            }
            this.editor.current = null;
            this.editor.isOpen = false;
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
            '       <span class="inline"><a href="javascript:void(0)" onclick="creatureManager.edit(\'' + id + '\')" class="editinline">Edit</a> | </span>' +
            '       <span class="trash"><a href="javascript:void(0)" onclick="creatureManager.deleteRow(\'' + id + '\')" class="submitdelete">Trash</a></span>' +
            '   </div>' +
            '</td>' +
            '<td>' + properties.level + '</td>' +
            '<td>' + properties.hp + '</td>'
        ;
        tr.setAttribute('class', 'inactive');
        this.editor.current = null;
        this.editor.isOpen = false;
    },

    getEmptyRow: function () {
        return '' +
            '<tr id="no-items" class="no-items">' +
            '    <td class="colspanchange" colspan="8">No Items found</td>' +
            '</tr>'
            ;
    },
};
