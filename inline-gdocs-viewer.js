jQuery(document).ready(function () {
    jQuery('.igsv-table:not(.no-datatables)').each(function () {
        jQuery(this).dataTable({
            'dom': 'TC<"clear">lfrtip',
            'scrollX': true,
            'tableTools': {
                'sSwfPath': '//datatables.net/release-datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf'
            }
        });
    });
});
