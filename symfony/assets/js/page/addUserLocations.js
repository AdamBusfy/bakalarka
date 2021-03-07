import '../bootstrap.js';

import $ from 'jquery';

$(document).on('click', '[data-delete-modal-opener]', event => {
    $("#delete_form_id").val(event.currentTarget.dataset.id);
});

$(document).on('click', '[data-delete-modal-opener]', event => {
    $("#remove_user_location_id").val(event.currentTarget.dataset.id);
});