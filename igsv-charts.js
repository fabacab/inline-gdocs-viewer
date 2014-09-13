google.load('visualization', '1.0', {
    'packages' : [
        'corechart'
    ]
});
jQuery(document).ready(function () {
    jQuery('.igsv-chart').each(function () {
        var chart_id = jQuery(this).attr('id');
        var query = new google.visualization.Query(jQuery('#' + chart_id).data('datasource-href'));
        query.send(function (response) {
            if (response.isError()) {
                return; // bail, let Google handle displaying errors
            }
            var data = response.getDataTable();
            var chart;
            switch (jQuery('#' + chart_id).data('chart-type')) {
                case 'Pie':
                    chart = new google.visualization.PieChart(document.getElementById(chart_id));
                    break;
                case 'Bar':
                    chart = new google.visualization.BarChart(document.getElementById(chart_id));
                    break;
                case 'Column':
                default:
                    chart = new google.visualization.ColumnChart(document.getElementById(chart_id));
                    break;
            }
            chart.draw(data);
        });
    });
});
