// noinspection JSUnresolvedVariable
let params = mp_ssv_encounter_editor_params;

let encounterEditor = {
    show: function () {
        $('#monsterAddForm').toggle();
        $('#new_encounter_monster').focus();
    }
};

jQuery(document).ready(function ($) {
    // setTimeout(
    //     function () {
    //         $('#new-tag-encounter_monsters').autocomplete({
    //             delay: 10,
    //             cache: true,
    //             minChars: 2,
    //             source: function (name, response) {
    //                 $.ajax({
    //                     type: 'GET',
    //                     dataType: 'json',
    //                     url: params.urls.ajax,
    //                     data: 'action=get_listing_names&name=' + name,
    //                     success: function (data) {
    //                         console.log(data);
    //                         response(data);
    //                     },
    //                     error: function (response) {
    //                     }
    //
    //                 });
    //             }
    //         });
    //     }, 1000);
});
