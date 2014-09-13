// DataTables
jQuery(document).ready(function () {
    jQuery('.igsv-table:not(.no-datatables)').each(function () {
        var table = jQuery(this).DataTable({
            'dom': 'TC<"clear">lfrtip',
            'scrollX': true,
            'tableTools': {
                'sSwfPath': '//datatables.net/release-datatables/extensions/TableTools/swf/copy_csv_xls_pdf.swf'
            }
        });

        var x;
        if (jQuery(this).is('.FixedColumns')) {
            new jQuery.fn.dataTable.FixedColumns(table);
        } else if (x = this.className.match(/FixedColumns-(left|right)-([0-9])*/g)) {
            var l_n = 0;
            var r_n = 0;
            for (var i = 0; i < x.length; i++) {
                var z = x[i].split('-');
                if ('left' === z[1]) {
                    l_n = z[2];
                } else {
                    r_n = z[2];
                }
            }
            new jQuery.fn.dataTable.FixedColumns(table, {
                'leftColumns': l_n,
                'rightColumns': r_n
            });
        }
    });
});

// Google Visualizations
if (google && google.load) {
    google.load('visualization', '1.0', {
        'packages' : [
            'corechart'
        ]
    });
}
jQuery(document).ready(function () {
    jQuery('.igsv-chart').each(function () {
        var chart_id = jQuery(this).attr('id');
        var query = new google.visualization.Query(jQuery('#' + chart_id).data('datasource-href'));
        var options = {
            'title': jQuery('#' + chart_id).attr('title')
        };
        query.send(function (response) {
            if (response.isError()) {
                return; // bail, let Google handle displaying errors
            }
            var data = response.getDataTable();
            var chart;
            switch (jQuery('#' + chart_id).data('chart-type')) {
                case 'Area':
                    chart = new google.visualization.AreaChart(document.getElementById(chart_id));
                    break;
                case 'Bar':
                    chart = new google.visualization.BarChart(document.getElementById(chart_id));
                    break;
                case 'Bubble':
                    chart = new google.visualization.BubbleChart(document.getElementById(chart_id));
                    break;
                case 'Candlestick':
                    chart = new google.visualization.CandlestickChart(document.getElementById(chart_id));
                    break;
                case 'Column':
                default:
                    chart = new google.visualization.ColumnChart(document.getElementById(chart_id));
                    break;
                case 'Combo':
                    chart = new google.visualization.ComboChart(document.getElementById(chart_id));
                    break;
                case 'Histogram':
                    chart = new google.visualization.Histogram(document.getElementById(chart_id));
                    break;
                case 'Line':
                    chart = new google.visualization.LineChart(document.getElementById(chart_id));
                    break;
                case 'Pie':
                    chart = new google.visualization.PieChart(document.getElementById(chart_id));
                    break;
                case 'Scatter':
                    chart = new google.visualization.ScatterChart(document.getElementById(chart_id));
                    break;
                case 'Stepped':
                case 'SteppedArea':
                case 'Steppedarea':
                    chart = new google.visualization.SteppedAreaChart(document.getElementById(chart_id));
                    break;
            }
            chart.draw(data, options);
        });
    });
});
