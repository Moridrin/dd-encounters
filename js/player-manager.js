// noinspection JSUnresolvedVariable
let params = mp_ssv_player_manager_params;

let playerManager = {

    playerSpecifications: {
        text: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'autoComplete',
                'placeholder',
                'optionsList',
                'pattern',
                'profilePlayer',
            ],
        },
        email: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'autoComplete',
                'placeholder',
                'optionsList',
                'pattern',
                'profilePlayer',
            ],
        },
        password: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'autoComplete',
                'optionsList',
                'pattern',
            ],
        },
        checkbox: {
            parts: [
                'div',
                'title',
                'label',
            ],
            properties: [
                'title',
                'defaultValue',
                'classes',
                'styles',
                'required',
                'profilePlayer',
            ],
        },
        datetime: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'profilePlayer',
            ],
        },
        file: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'profilePlayer',
            ],
        },
        select: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'multiple',
                'size',
                'profilePlayer',
            ],
        },
        number: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'placeholder',
                'step',
                'min',
                'max',
                'profilePlayer',
            ],
        },
        custom: {
            parts: [
                'div',
                'title',
                'input',
            ],
            properties: [
                'title',
                'classes',
                'defaultValue',
                'styles',
                'required',
                'autoComplete',
                'placeholder',
                'optionsList',
                'pattern',
                'step',
                'min',
                'max',
                'size',
                'multiple',
                'profilePlayer',
            ],
        },
    },

    editor: {

        current: null,
        isOpen: false,

        getInputPlayer: function (title, name, value, type, events) {
            // console.log(events.onkeydown);
            // if (events.onkeydown === undefined) {
            events.onkeydown = 'playerManager.editor.onKeyDown()';
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
                    playerManager.saveEdit();
                    event.preventDefault();
                    return false;
                } else {
                    $nameInput.setCustomValidity('');
                    $nameInput.reportValidity();
                }
            } else if (editType === 'customize') {
                if (event.keyCode === 13) {
                    playerManager.saveCustomization();
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
            '       <button type="button" class="button cancel alignleft" onclick="playerManager.cancel()">Cancel</button>' +
            '       <button type="button" class="button button-primary save alignright" onclick="playerManager.saveEdit()">Save</button>' +
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

    customize: function (id) {
        this.closeEditor();
        this.editor.current = id;
        this.editor.isOpen = true;
        let tr = document.getElementById('model_' + id);
        let properties = JSON.parse(tr.dataset.properties);
        tr.setAttribute('class', 'inline-edit-row');
        tr.removeAttribute('draggable');
        let playerSpecification = null;
        if (this.playerSpecifications[properties.type] !== undefined) {
            playerSpecification = this.playerSpecifications[properties.type];
        } else {
            playerSpecification = this.playerSpecifications['custom'];
        }
        let html =
            '<input type="hidden" name="form_players[]" value="' + tr.dataset.baseid + '">' +
            '<td colspan="5" class="colspanchange">' +
            '   <fieldset class="inline-edit-col" style="width: 50%;">' +
            '       <legend class="inline-edit-legend" id="edit-type" data-edit-type="customize">Customize</legend>'
        ;
        if (playerSpecification.properties.includes('title')) {
            if (properties.title === undefined) {
                properties.title = '';
            }
            html += this.editor.getInputPlayer('Title', 'title', properties.title, 'text', []);
        }
        if (playerSpecification.properties.includes('classes')) {
            if (properties.classes === undefined) {
                properties.classes = [];
            }
            for (let i = 0; i < playerSpecification.parts.length; ++i) {
                let id = playerSpecification.parts[i];
                let title = id.charAt(0).toUpperCase() + id.slice(1);
                if (properties.classes[id] === undefined) {
                    properties.classes[id] = '';
                }
                html += this.editor.getInputPlayer(title + ' Classes', id + '_classes', properties.classes[id], 'textarea', []);
            }
        }
        if (playerSpecification.properties.includes('required')) {
            if (properties.required === undefined) {
                properties.required = 'false';
            }
            html += this.editor.getCheckboxInputPlayer('Required', 'required', properties.required, '', []);
        }
        if (playerSpecification.properties.includes('placeholder')) {
            if (properties.placeholder === undefined) {
                properties.placeholder = '';
            }
            html += this.editor.getInputPlayer('Placeholder', 'placeholder', properties.placeholder, 'text', []);
        }
        if (playerSpecification.properties.includes('pattern')) {
            if (properties.pattern === undefined) {
                properties.pattern = '';
            }
            html += this.editor.getInputPlayer('Pattern', 'pattern', properties.pattern, 'text', []);
        }
        if (playerSpecification.properties.includes('min')) {
            if (properties.max === undefined) {
                properties.max = '';
            }
            html += this.editor.getInputPlayer('Min', 'min', properties.max, 'number', []);
        }
        if (playerSpecification.properties.includes('size')) {
            if (properties.size === undefined) {
                properties.size = '';
            }
            html += this.editor.getInputPlayer('Size', 'size', properties.size, 'number', []);
        }
        html +=
            '   </fieldset>' +
            '   <fieldset class="inline-edit-col" style="width: 50%; margin-top: 32px;">'
        ;
        if (playerSpecification.properties.includes('defaultValue')) {
            if (properties.defaultValue === undefined) {
                properties.defaultValue = '';
            }
            if (tr.dataset.type === 'select') {
                html += this.editor.getSelectInputPlayer('Default Value', 'defaultValue', JSON.parse(properties.value), properties.defaultValue, []);
            } else if (tr.dataset.type === 'checkbox') {
                html += this.editor.getInputPlayer('Label', 'defaultValue', properties.defaultValue, 'text', []);
            } else {
                html += this.editor.getInputPlayer('Default Value', 'defaultValue', properties.defaultValue, 'text', []);
            }
        }
        if (playerSpecification.properties.includes('styles')) {
            if (properties.styles === undefined) {
                properties.styles = [];
            }
            for (let i = 0; i < playerSpecification.parts.length; ++i) {
                let id = playerSpecification.parts[i];
                let title = id.charAt(0).toUpperCase() + id.slice(1);
                if (properties.styles[id] === undefined) {
                    properties.styles[id] = '';
                }
                html += this.editor.getInputPlayer(title + ' Styles', id + '_styles', properties.styles[id], 'textarea', []);
            }
        }
        if (playerSpecification.properties.includes('autoComplete')) {
            if (properties.autoComplete === undefined) {
                properties.autoComplete = 'true';
            }
            html += this.editor.getCheckboxInputPlayer('AutoComplete', 'autoComplete', properties.autoComplete, '', []);
        }
        if (playerSpecification.properties.includes('optionsList')) {
            if (properties.optionsList === undefined) {
                properties.optionsList = '';
            }
            html += this.editor.getInputPlayer('Options List', 'optionsList', properties.optionsList, 'text', []);
        }
        if (playerSpecification.properties.includes('step')) {
            if (properties.step === undefined) {
                properties.step = '';
            }
            html += this.editor.getInputPlayer('Step', 'step', properties.step, 'number', []);
        }
        if (playerSpecification.properties.includes('max')) {
            if (properties.max === undefined) {
                properties.max = '';
            }
            html += this.editor.getInputPlayer('Max', 'max', properties.max, 'number', []);
        }
        if (playerSpecification.properties.includes('multiple')) {
            if (properties.multiple === undefined) {
                properties.multiple = '';
            }
            html += this.editor.getCheckboxInputPlayer('Multiple', 'multiple', properties.multiple, '', []);
        }
        if (playerSpecification.properties.includes('profilePlayer')) {
            if (properties.profilePlayer === undefined) {
                properties.profilePlayer = 'true';
            }
            html += this.editor.getCheckboxInputPlayer('Profile Player', 'profilePlayer', properties.profilePlayer, '', []);
        }
        html +=
            '   </fieldset>' +
            '   <div class="submit inline-edit-save" style="float: none;">' +
            '      <button type="button" class="button cancel alignleft" onclick="playerManager.cancel(\'' + id + '\')">Cancel</button>' +
            '      <input type="hidden" id="_inline_edit" name="_inline_edit" value="' + id + '">' +
            '      <button type="button" class="button button-primary save alignright" onclick="playerManager.saveCustomization(\'' + id + '\')">Update</button>' +
            '      <br class="clear">' +
            '   </div>' +
            '</td>'
        ;
        tr.innerHTML = html;
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
                    playerManager.editor.current = id;
                    playerManager.closeEditor();
                }
            }
        );
    },

    saveCustomization: function () {
        let id = this.editor.current;
        let tr = document.getElementById('model_' + id);
        let properties = JSON.parse(tr.dataset.properties);
        let playerSpecification = null;
        if (typeof(playerManager.playerSpecifications[properties.type]) === 'undefined') {
            playerSpecification = playerManager.playerSpecifications['custom'];
        } else {
            playerSpecification = playerManager.playerSpecifications[properties.type];
        }
        let playerTypeObjects = playerSpecification.parts;
        for (let i = 0; i < playerSpecification.properties.length; ++i) {
            let property = playerSpecification.properties[i];
            if (property === 'classes' || property === 'styles') {
                properties[property] = {};
                for (let j = 0; j < playerTypeObjects.length; ++j) {
                    properties[property][playerTypeObjects[j]] = tr.querySelector('[name="' + playerTypeObjects[j] + '_' + property + '"]').value;
                }
            } else {
                let element = tr.querySelector('[name="' + property + '"]');
                if (element.getAttribute('type') === 'checkbox') {
                    properties[property] = element.checked;
                } else {
                    properties[property] = element.value;
                }
            }
        }
        tr.dataset.properties = JSON.stringify(properties);
        this.closeEditor();
        jQuery.post(
            params.urls.ajax,
            {
                action: params.actions.save,
                shared: params.isShared,
                formId: params.formId,
                properties: properties,
                oldName: properties['name'],
                id: id,
            },
            function (data) {
                generalFunctions.ajaxResponse(data);
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
            '       <span class="inline"><a href="javascript:void(0)" onclick="playerManager.edit(\'' + id + '\')" class="editinline">Edit</a> | </span>' +
            '       <span class="trash"><a href="javascript:void(0)" onclick="playerManager.deleteRow(\'' + id + '\')" class="submitdelete">Trash</a></span>' +
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
