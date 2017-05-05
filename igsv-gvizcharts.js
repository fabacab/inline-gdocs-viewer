/**
 * Inline Google Spreadsheet Viewer's Google Chart API integrations.
 *
 * @file Loads and draws Google Chart API visualizations.
 * @license GPL-3.0
 * @author Meitar Moscovitz <meitarm+wordpress@gmail.com>
 * @copyright Copyright 2017 by Meitar "maymay" Moscovitz
 */

(function () { // start immediately-invoked function expresion (IIFE)

google.load('visualization', '1.0', {
    'packages' : [
        'corechart',
        'annotatedtimeline',
        'annotationchart',
        'gauge',
        'geochart',
        'timeline'
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
                chart_el = document.getElementById(chart_id),
                chart;
            switch (jQuery('#' + chart_id).data('chart-type').toLowerCase()) {
                case 'annotatedtimeline':
                    chart = new google.visualization.AnnotatedTimeLine(chart_el)
                    break;
                case 'annotation':
                    chart = new google.visualization.AnnotationChart(chart_el)
                    break;
                case 'area':
                    chart = new google.visualization.AreaChart(chart_el);
                    break;
                case 'bar':
                    chart = new google.visualization.BarChart(chart_el);
                    break;
                case 'bubble':
                    chart = new google.visualization.BubbleChart(chart_el);
                    break;
                case 'candlestick':
                    chart = new google.visualization.CandlestickChart(chart_el);
                    break;
                case 'column':
                default:
                    chart = new google.visualization.ColumnChart(chart_el);
                    break;
                case 'combo':
                    chart = new google.visualization.ComboChart(chart_el);
                    break;
                case 'gauge':
                    chart = new google.visualization.Gauge(chart_el);
                    break;
                case 'geo':
                    chart = new google.visualization.GeoChart(chart_el);
                    break;
                case 'histogram':
                    chart = new google.visualization.Histogram(chart_el);
                    break;
                case 'line':
                    chart = new google.visualization.LineChart(chart_el);
                    break;
                case 'pie':
                    chart = new google.visualization.PieChart(chart_el);
                    break;
                case 'scatter':
                    chart = new google.visualization.ScatterChart(chart_el);
                    break;
                case 'stepped':
                case 'steppedarea':
                    chart = new google.visualization.SteppedAreaChart(chart_el);
                    break;
                case 'timeline':
                    chart = new google.visualization.Timeline(chart_el);
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

})(); // end IIFE
