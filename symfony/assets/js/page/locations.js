import '../bootstrap.js';

import $ from 'jquery';

$(document).on('click', '[data-delete-modal-opener]', event => {
    $("#delete_form_id").val(event.currentTarget.dataset.id);
});

console.log("LOCATIONS");