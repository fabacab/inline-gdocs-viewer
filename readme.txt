=== Plugin Name ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TJLPJYXHSRBEE&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer&item_number=Inline%20Google%20Spreadsheet%20Viewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Google Docs, Google, Spreadsheet, shortcode, Chart, data, visualization, infographics
Requires at least: 3.3
Tested up to: 4.1
Stable tag: 0.6.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Embeds a public Google Spreadsheet in a WordPress post or page as an HTML table or interactive chart.

== Description ==

Easily turn data stored in a Google Spreadsheet into a beautiful interactive chart or graph, a sortable and searchable table, or both!

The Inline Google Spreadsheet Viewer fetches a publicly shared Google Spreadsheet using a `[gdoc key=""]` WordPress shortcode, then renders it as an HTML table or interactive chart, embedded in your blog post or page. The only required parameter is `key`, which specifies the document you'd like to retrieve and will render a feature-rich table. Additional parameters let you customize how you display your data in the table, or transforms the table into an interactive bar chart, pie chart, or other information visualization.

Your spreadsheet must be shared [using either the "Public on the web" or "Anyone with the link" options](https://support.google.com/drive/?p=visibility_options&hl=en_US). Currently, private Google Spreadsheets or Spreadsheets shared with "Specific people" are not supported.

After setting the appropriate Sharing setting, copy the URL you use to view the Spreadsheet from your browser's address bar into the shortcode. For example, to display the spreadsheet at `https://docs.google.com/spreadsheets/d/ABCDEFG/edit`, use the following shortcode in your WordPress post or page:

    [gdoc key="https://docs.google.com/spreadsheets/d/ABCDEFG/edit"]

If your spreadsheet uses the "old" Google Spreadsheets, you need to [ensure that your spreadsheet is "Published to the Web"](https://docs.google.com/support/bin/answer.py?hl=en&answer=47134) and you need to copy only the "key" out of the URL. For instance, if the URL of your old Google Spreadsheet is `https://docs.google.com/spreadsheets/pub?key=ABCDEFG`, then your shortcode should look like this:

    [gdoc key="ABCDEFG"]

To create an interactive chart from your Spreadsheet's data, use the `chart` attribute to a supported chart type. These include:

* `Area` charts
* `Bar` charts
* `Bubble` charts
* `Candlestick` charts
* `Column` charts
* `Combo` charts
* `Histogram` charts
* `Line` charts
* `Pie` charts
* `Scatter` charts
* `Stepped` area charts

For example, if you have a Google Spreadsheet for a sports league that records the goals each team has scored (where the first column is the team name and the second column is their total goals), you can create a bar chart, with an optional title, from that data using a shortcode like this:

    [gdoc key="ABCDEFG" chart="Bar" title="Total goals per team"]

Depending on the type of chart you chose, you can customize your chart with a number of options, such as colors. For example, to create a 3D red and green pie chart whose slices are labelled with your data's values:

    [gdoc key="ABCDEFG" chart="Pie" chart_colors="red green" chart_dimensions="3" chart_pie_slice_text="value"]

To render an HTML table with additional metadata, such as supplying the table's `title`, `summary`, `<caption>`, and a customized `class` value, you can do the following:

    [gdoc key="ABCDEFG" class="my-sheet" title="Tooltip text displayed on hover" summary="An example spreadsheet, with a summary."]This is the table's caption.[/gdoc]

The above shortcode will produce HTML that looks something like the following:

    <table id="igsv-ABCDEFG" class="igsv-table my-sheet" title="Tooltip text displayed on hover" summary="An example spreadsheet, with a summary.">
        <caption>This is the table's caption.</caption>
        <!-- ...rest of table code using spreadsheet data here... -->
    </table>

You can also `strip` a certain number of rows (e.g., `strip="3"` omits the top 3 rows of the spreadsheet).

You can use the `gid` attribute to fetch data from a worksheet other than the first one (the one on the far left). For example, to display a worksheet published at `https://spreadsheets.google.com/pub?key=ABCDEFG&gid=4`, use the following shortcode in your WordPress post or page:

    [gdoc key="ABCDEFG" gid="4"]

The `header_rows` attribute lets you specify how many rows should be rendered as the [table header](http://reference.sitepoint.com/html/thead). For example, to render a worksheet's top 3 rows inside the `<thead>` element, use:

    [gdoc key="ABCDEFG" header_rows="3"]

Be default, all tables are progressively enhanced with jQuery [DataTables](https://datatables.net/) to provide sorting, searching, and pagination functions on the table display itself. If you'd like a specific table not to include this functionality, use the `no-datatables` `class` in your shortcode. For instance:

    [gdoc key="ABCDEFG" class="no-datatables"]

For DataTables-enhanced tables, you can also specify columns that you'd like to "freeze" when the user scrolls large tables horizontally. To do so, use the `FixedColumns-left-N` and `FixedColumns-right-N` classes, where `N` is the number of columns you'd like to freeze. For instance, to display the three left-most columns and the right-most column in a fixed (frozen) position, use the following in your shortcode:

    [gdoc key="ABCDEFG" class="FixedColumns-left-3 FixedColumns-right-1"]

Web addresses and email addresses in your data are turned into links. If this causes problems, you can disable this behavior by specifying `no` to the `linkify` attribute in your shortcode. For instance:

    [gdoc key="ABCDEFG" linkify="no"]

You can pre-process your Google Spreadsheet before retrieving data from it by passing a [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query to the shortcode's `query` attribute. This lets you interact with the data in your Google Spreadsheet as though the spreadsheet were a relational database table. For instance, if you wish to display the team that scored the most goals on your website, you might use a shortcode like this to query your Google Spreadsheet and display the highest-scoring team:

    [gdoc key="ABCDEFG" query="SELECT team WHERE max(goals)"]

Queries are also useful if your spreadsheet contains complex data from which many different charts can be created, allowing you to select only the parts of your spreadsheet that you'd like to use to compose the interactive chart.

== Installation ==

1. Upload `inline-gdocs-viewer.php` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use the `[gdoc key="ABCDEFG"]` shortcode wherever you'd like to insert the Google Spreadsheet.

== Frequently Asked Questions ==

= The default style is ugly. Can I change it? =
Yes, if you're able to change your theme's style sheet. The plugin renders HTML with plenty of [CSS](http://en.wikipedia.org/wiki/Cascading_Style_Sheets) hooks. Use the `igsv-table` class from your style sheets to target the plugin's `<table>` element.

Additionally, each row (`<tr>`) and cell (`<td>`) is assigned a specific `class` attribute value. The first `<tr>` element is assigned the `row-1` class, the second is assigned `row-2`, and the last `row-N` where `N` is the number of rows in the rendered table. Similarly, each cell is assigned a class based on its columnar position; the first cell in a row is assigned the `col-1` class, the second `col-2`, and so on:

    .igsv-table .row-2 .col-5 { /* styles for the cell in the 2nd row, 5th column */ }

Finally, both rows and cells (based on columns) are assigned an additional class of either `odd` or `even`, allowing for easy zebra-striping in [CSS3](http://www.w3.org/TR/css3-selectors/) non-conformant browsers.

    .igsv-table tr.odd  { /* styles for odd-numbered rows   (row 1, 3, 5...) */ }
    .igsv-table tr.even { /* styles for even-numbered rows  (row 2, 4, 6...) */ }
    .igsv-table td.odd  { /* styles for odd-numbered cells  (column 1, 3, 5...) */ }
    .igsv-table td.even { /* styles for even-numbered cells (column 2, 4, 6...) */ }

= A table appears, but it's not my spreadsheet's data! And it looks weird! =
If you're still using the "old" Google Spreadsheets, you should triple-check that you've published your spreadsheet. Google provides instructions for doing this. Be sure to follow steps 1 and 2 in [Google Spreadsheets Help: Publishing to the Web](http://docs.google.com/support/bin/answer.py?hl=en&answer=47134). If you're using the "new" Google Spreadsheets, be sure you've selected either the ["Public on the web" or "Anyone with the link" Sharing options](https://support.google.com/drive/answer/2494886?p=visibility_options) for your Google Spreadsheet.

= Nothing appears where my chart should be. =

The best way to determine what's wrong with a chart that isn't displaying properly is to try displaying the chart's data as a simple HTML table (by removing the `chart` attribute from your shortcode), and seeing what the tabular data source looks like.

Charts most likely fail to display because of a mismatch between the chart you are using and the format of your spreadsheet.

Each type of chart expects to retrieve data with a certain number of rows and/or columns. If your Google Spreadsheet is not already designed to create data for a chart, you might be able to use the `query` attribute to select only the rows and/or columns that the `chart` you're using expects. Otherwise, consider creating a new sheet with the proper formatting and using the `gid` attribute in your shortcode.

To learn more about the correct spreadsheet formats for each chart type, please refer to [Google's Chart Gallery documentation](https://google-developers.appspot.com/chart/interactive/docs/gallery) for the type of chart you are using.

= Can I remove certain columns from appearing on my webpage? =
If you're using the "new" Google Spreadsheets, you can strip out columns by `select`ing only those columns you wish to retrieve by passing a [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query to the shortcode's `query` attribute. For example, to retrieve and display only the first, second, and third columns in a spreadsheet, use a shortcode like this:

    [gdoc key="ABCDEFG" query="select A, B, C"]

Alternatively, you can [hide columns using CSS](http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/comment-page-2/#comment-294582) with code such as, `.col-4 { display: none; }`, for example.

= How do I change the default settings, like can I turn paging off? Can I change the page length? Can I change the sort order? =

If you're able to add JavaScript to your theme, you can do all of these things, and more. Any and all DataTables-enhanced tables can be modified by using the DataTables API.

For instance, to disable paging, add a JavaScript to your theme that looks like this:

    jQuery(window).load(function () {
        jQuery('#igsv-MY_TABLE_KEY').dataTable().api().page.len(-1).draw();
    });

Or, to have your DataTables-enhanced table automatically sort itself by the second column:

    jQuery(window).load(function () {
        jQuery('#igsv-MY_TABLE_KEY').dataTable().api().order([1, 'desc']).draw();
    });

(Replace `MY_TABLE_KEY` with the Google Spreadsheet document ID of your spreadsheet, of course.)

Please refer to the [DataTables API reference manual](https://datatables.net/reference/api) for more information about customizing DataTables-enhanced tables.

Another option for sorting your table, for example, is to use the `query` attribute and pass along an appropriate [Google Charts API Query Language query that includes an `order by` clause](https://developers.google.com/chart/interactive/docs/querylanguage#Order_By).

= How do I customize my chart? =

Using specific shortcode attributes, you can choose from a huge number of configurable options to customize the look and feel of your chart. The specific shortcode attributes available to you depend on the type of chart you chose. Refer to the [Google Chart API documentation](https://developers.google.com/chart/interactive/docs/gallery) to learn which configuration options are available for which type of charts.

Each configuration option is accessible through a shortcode of a similar name. For instance, the `colors` configuration option is accessible to you through the `chart_colors` attribute. It accepts a list of colors, which you supply to the shortcode in a similar way as you might provide a `class` value:

    [godc key="ABCDEFG" chart="Pie" chart_colors="red green"]

To create a 3D chart, specify `chart_dimensions="3"`.

With a few exceptions, the name of a shortcode attribute is always an underscore-separated translation of the camelCase name of the option in the Google Chart API. For instance, to disable chart interactivity by setting the chart's `enableInteractivity` option to `false`, use a shortcode like:

    [gdoc key="ABCDEFG" chart="Pie" chart_enable_interactivity="false"]

Some configuration options call for an `Object` value. For these, the shortcode attribute value should be a [JSON](http://JSON.org/) object. For instance, to use the different properties of the `backgroundColor` option:

    [gdoc key="ABCDEFG" chart="Pie" chart_background_color='{"fill":"yellow","stroke":"red","strokeWidth":5}']

Note that when a JSON object is used as a value, the shortcode attribute's value must be single-quoted.

The list of attributes for configurable options is:

    * `chart_annotations`
    * `chart_aggregation_target`
    * `chart_area_opacity`
    * `chart_axis_titles_position`
    * `chart_background_color`
    * `chart_bars`
    * `chart_bubble`
    * `chart_candlestick`
    * `chart_chart_area`
    * `chart_color_axis`
    * `chart_colors`
    * `chart_crosshair`
    * `chart_curve_type`
    * `chart_data_opacity`
    * `chart_dimensions`
    * `chart_enable_interactivity`
    * `chart_explorer`
    * `chart_focus_target`
    * `chart_font_name`
    * `chart_font_size`
    * `chart_force_i_frame`
    * `chart_h_axes`
    * `chart_h_axis`
    * `chart_height`
    * `chart_interpolate_nulls`
    * `chart_is_stacked`
    * `chart_legend`
    * `chart_line_width`
    * `chart_orientation`
    * `chart_pie_hole`
    * `chart_pie_residue_slice_color`
    * `chart_pie_residue_slice_label`
    * `chart_pie_slice_border_color`
    * `chart_pie_slice_text`
    * `chart_pie_slice_text_stlye`
    * `chart_pie_start_angle`
    * `chart_point_shape`
    * `chart_point_size`
    * `chart_reverse_categories`
    * `chart_selection_mode`
    * `chart_series`
    * `chart_size_axis`
    * `chart_slice_visibility_threshold`
    * `chart_slices`
    * `chart_theme`
    * `chart_title_position`
    * `chart_title_text_style`
    * `chart_tooltip`
    * `chart_trendlines`
    * `chart_v_axis`
    * `chart_width`

= Why am I getting errors when I try to use the `query` attribute? =

If your `query` includes an angle bracket, such as a less than (`<`) or a greater than (`>`) sign, [WordPress will assume you are trying to write HTML](https://core.trac.wordpress.org/ticket/28564) and strip everything except the first word of your query, resulting in syntax error. Instead, use the URL-encoded equivalents of these characters (`%3C` and `%3E`, for `<` and `>`, respectively), which WordPress will pass to the plugin unmolested and which the plugin is specifically aware of how to handle correctly.

== Change log ==

= Version 0.6.3 =

* Feature: Massively customizeable charts.
    * You can now use a huge number of shortcode options to customize the look and feel of your charts. Most configurable options defined in the [Google Chart API](https://developers.google.com/chart/interactive/docs/gallery) are supported through shortcode attributes. For instance,
        * to choose **custom chart colors**, use the `chart_colors` attribute with a space-separated list of color strings (like `chart_colors="red #CCC"`).
        * Use [JSON](http://json.org/) syntax in an attribute whose value calls for an `Object`. For instance, `chart_background_color='{"fill":"yellow","stroke":"red","strokeWidth":5}'`, and notice the single quotes around the attribute value and double quotes for correct JSON parsing.

= Version 0.6.2.2 =

* [Bugfix](https://wordpress.org/support/topic/using-greaterless-than-signs-in-where-clause): Workaround WordPress parser garbling `<` and `>` comparison operators in `query` shortcode attribute. Instead, use the URL-encoded equivalents, `%3C` and `%3E`, respectively.

= Version 0.6.2.1 =

* Bugfix: Show the QuickTag button only on post edit screens.

= Version 0.6.2 =

* [Bugfix](https://wordpress.org/support/topic/with-06-ver-i-have-an-error-uncaught-referenceerror-google-is-not-defined?replies=8#post-6010795): Fix bug that failed to load Chart visualizations in combination with `gid` parameter.

= Version 0.6.1 =

* [Bugfix](https://wordpress.org/support/topic/with-06-ver-i-have-an-error-uncaught-referenceerror-google-is-not-defined): Fix bug that caused JavaScript loading to fail when certain `gdoc` shortcodes were used.

= Version 0.6 =

* Feature: Use the `chart` attribute to display your Google Spreadsheet's data as an interactive chart or graph. Supported chart types include:
    * Area charts
    * Bar charts
    * Bubble charts
    * Candlestick charts
    * Column charts
    * Combo charts
    * Histograms
    * Line charts
    * Pie charts
    * Scatter charts
    * Stepped area charts
* Feature: Customize the tooltip by supplying a `title` attribute in the shortcode for your table or chart.
* Performance: Load large JavaScript libraries only when needed for the kind of chart or table that's being displayed.

= Version 0.5.1 =

* Feature: New `gdoc` quicktag allows point-and-click insertion of `[gdoc key="ABCDEFG"]` shortcode when using the HTML editor.
* Feature: On-line help using WordPress's built-in help viewer.
* Usability: More error detection and suggestions for possible fixes.
* Localization: Translation infrastructure added. [Help translate Inline Google Spreadsheet Viewer into your langauge](https://www.transifex.com/projects/p/inline-gdocs-viewer/)!

= Version 0.5 =

* [Feature](https://wordpress.org/support/topic/work-great-12): Query your spreadsheet like a database using the new `query` shortcode attribute. The value of the `query` attribute is any [Google Charts API Query Language query](https://developers.google.com/chart/interactive/docs/querylanguage). For example, to retrieve only the first two columns in rows whose first column begins with "Mario Bros." in a Google Spreadsheet, use a shortcode like `[gdoc key="ABCDEFG" query="select A, B where A starts with 'Mario Bros.'"]`.

= Version 0.4.7.1 =

* Usability: Show a user-friendly error with suggestions to fix the detected problem.

= Version 0.4.7 =

* Feature: Support for the [FixedColumns extension](https://datatables.net/extensions/fixedcolumns/) for DataTables-enhanced tables.
* Large DataTables-enhanced tables now scroll horinzontally by default to avoid common layout issues.

= Version 0.4.6 =

* Update to support Google's new `gid` attribute requirements. (Dear Google, please stop changing things, sincerely, [a homeless hacker who survives thanks to donations for free software](http://maymay.net/).)

= Version 0.4.5 =

* Bugfix: Correctly output the table's `id` attribute for "new" Google Spreadsheets.
* Security: Added additional output escaping.

= Version 0.4.4 =

* Enhancement: Update DataTables library to version 1.10. Notably, this brings [client-side DataTable ordering (sorting) capability](https://datatables.net/reference/api/order%28%29) to your theme's JavaScripts.
* Feature: Include DataTables [ColVis](https://datatables.net/extensions/colvis/) and [TableTools](https://datatables.net/extensions/tabletools) extensions by default.

= Version 0.4.3 =

* Feature: "New" Google Spreadsheets are now officially supported.

= Version 0.4.2 =

* Feature: Detect Web addresses and email addresses and turn them into clickable links. Optionally disable this behavior by adding `linkify="no"` to your shortcode.

= Version 0.4.1 =

* Bugfix: Correctly pass `gid` attribute.

= Version 0.4 =

* Feature: Support the "new" Google Spreadsheets through HTML parsing.
    * *This feature is experimental and is not recommended for production websites because [Google's "new" Google Spreadsheets are still under active development](https://support.google.com/drive/answer/3543688).* I strongly suggest you continue to use the "old" Google Spreadsheets for any documents with which you use this plugin. More information about [reverting back to the old Google Spreadsheets](https://support.google.com/drive/answer/3544847#workarounds) is available on Google's help page.

= Version 0.3.3 =

* Bugfix: Correctly load search/sort/filter JavaScript on some systems where it failed.

= Version 0.3.2 =

* Adds jQuery [DataTables](//datatables.net/) plugin to provide column sorting, searching, and pagination. All tables will have DataTables's features applied. If you'd prefer to stick with the old, static table, use the `no-datatables` `class` when calling it. For instance, `[gdoc key="ABDEFG" class="no-datatables"]`. This also means the plugin now requires WordPress version 3.3 or later.

= Version 0.3.1 =

* Bugfix for "Invalid argument supplied for foreach()" when using built-in PHP `str_getcsv()`.
* Bugfix for some situations in which debugging code caused a fatal error.

= Version 0.3 =

* Implements `header_rows` attribute in shortcode to allow rendering more than 1 header row.
* Fetches data using `wp_remote_get()` instead of `fopen()` for portability; now requires WordPress 2.7 or higher.
* Updates plugin internals; uses PHP 5.3's `str_getcsv()` function if available.

= Version 0.2 =

* Implements `gid` attribute in shortcode to allow embedding of non-default worksheet.
* Updates plugin internals; now requires WordPress 2.6 or higher.

= Version 0.1 =

* Initial release.

== Other notes ==

Maintaining this plugin is a labor of love. However, if you like it, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer%20WordPress%20Plugin&item_number=inline%2dgdocs%2dviewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) for your use of the plugin, [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or, better yet, contributing directly to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). (Publishing royalties ain't exactly the lucrative income it used to be, y'know?) Your support is appreciated!
