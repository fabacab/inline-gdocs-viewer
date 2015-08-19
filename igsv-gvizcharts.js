// Google Visualizations
google.load('visualization', '1.0', {
    'packages' : [
        'corechart',
        'gauge'
    ]
});
jQuery(document).ready(function () {
    jQuery('.igsv-chart').each(function () {
        var chart_id = jQuery(this).attr('id'),
            query = new google.visualization.Query(jQuery(this).data('datasource-href')),
            options = lowerFirstCharInKeys(stripPrefixInKeys('chart', jQuery(this).data()));

        // Special-case chart options.
        options.title = jQuery(this).attr('title');
        delete options.type; // handled elsewhere
        for (x in options) {
            switch (x) {
                case 'colors':
                    options.colors = jQuery(this).data('chart-colors').split(' ');
                break;
                case 'dimensions':
                    options.is3D = (3) ? true : false;
                    delete options.dimensions;
                break;
            }
        }

        query.send(function (response) {
            if (response.isError()) {
                return; // bail, let Google handle displaying errors
            }
            var data = response.getDataTable(),
                chart;
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
                case 'Gauge':
                    chart = new google.visualization.Gauge(document.getElementById(chart_id));
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

        // Chart helpers.
        function stripPrefixInKeys (prefix, obj) {
            var new_obj = {};
            for (x in obj) {
                if (0 === x.indexOf(prefix)) {
                    new_obj[x.slice(prefix.length)] = obj[x];
                } else {
                    new_obj[x] = obj[x];
                }
            }
            return new_obj;
        }
        function lowerFirstCharInKeys (obj) {
            var new_obj = {};
            for (x in obj) {
                new_obj[x.charAt(0).toLowerCase() + x.slice(1)] = obj[x];
            }
            return new_obj;
        }
    });
});
