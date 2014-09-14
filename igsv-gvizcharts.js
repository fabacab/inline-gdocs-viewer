// Google Visualizations
google.load('visualization', '1.0', {
    'packages' : [
        'corechart'
    ]
});
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
