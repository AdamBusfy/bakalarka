import '../bootstrap.js';

import $ from 'jquery';

setTimeout(function() {
    $('.alert').fadeOut('fast');
}, 5000);

$(document).on('click', '[data-delete-modal-opener]', event => {
    $("#delete_form_id").val(event.currentTarget.dataset.id);
});