import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.css';
// import 'bootstrap-select';
// import 'bootstrap-select/dist/css/bootstrap-select.css';
import 'bootstrap-table';
import 'bootstrap-table/dist/bootstrap-table.css'
import $ from "jquery";
import 'datatables.net-bs4';
import 'datatables.net-select-bs4';
import 'datatables.net-bs4/css/dataTables.bootstrap4.css';
import 'datatables.net-select-bs4/css/select.bootstrap4.css';
import './lib/datatables'
import '../css/bootstrap.scss';
import 'bootstrap-datepicker'
import 'bootstrap-datepicker/dist/css/bootstrap-datepicker3.css'
import '@fortawesome/fontawesome-free/css/all.css'
import '@coreui/coreui/dist/css/coreui.min.css'
import '@coreui/coreui/dist/js/coreui.bundle.min'
import '@coreui/icons/css/all.min.css'
// import '/../@coreui/icons/scss'
import 'select2';
import 'select2/dist/css/select2.css'
import '@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.css'

$('.datepicker').datepicker();

$('.selectpicker').select2({
    theme: 'bootstrap4'
});

// $('.input-daterange input').each(function() {
//     $(this).datepicker('clearDates');
// });

$("[data-data-table]").each((_index, table) => {
    const options = JSON.parse(table.dataset.options);
    $(table).initDataTables(options, {
        searching: false,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });
});