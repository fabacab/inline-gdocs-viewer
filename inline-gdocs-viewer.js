jQuery(document).ready(function () {
    jQuery('.igsv-table:not(.no-datatables)').each(function () {
        jQuery(this).dataTable();
    });
});
