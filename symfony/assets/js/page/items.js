import '../bootstrap.js';
import $ from 'jquery';

$(document).on('click', '[data-add-item-to-location-modal-opener]', event => {
    $("#add_item_to_location_id").val(event.currentTarget.dataset.itemId);
    // console.log(event.currentTarget.dataset.itemId);
});

$(document).on('click', '[data-remove-item-from-location-modal-opener]', event => {
    $("#remove_item_from_location_id").val(event.currentTarget.dataset.itemId);
});

console.log("ITEMS");