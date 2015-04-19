=== Plugin Name ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TJLPJYXHSRBEE&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer&item_number=Inline%20Google%20Spreadsheet%20Viewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Google Docs, Google, Spreadsheet, shortcode, Chart, data, visualization, infographics, embed, live preview, infoviz, tables
Requires at least: 3.5
Tested up to: 4.1.1
Stable tag: 0.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Embeds a public Google Spreadsheet in a WordPress post or page as an HTML table or interactive chart.

== Description ==

Easily turn data stored in a Google Spreadsheet into a beautiful interactive chart or graph, a sortable and searchable table, or both! Embed live previews of PDF, XLS, DOC, and other file formats supported by the [Google Docs Viewer](https://docs.google.com/viewer). A built-in cache provides extra speed.

* Update your blog post or page whenever your Google Spreadsheet changes.
* Create beautiful interactive graphs and charts from your spreadsheet with ease.
* Embed almost any online document to view without leaving your blog.
* Use a powerful and flexible query language and a plethora of configuration options.

= Quick start =

Paste the URL of your public Google Spreadsheet on its own line in your WordPress post or page, then save your post. That's it. :) Your spreadsheet will appear in a sorted, searchable HTML table. See the [screenshots](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/screenshots/) for an example.

Your spreadsheet must be shared using either the "Public on the web" or "Anyone with the link" options [(learn how to share your spreadsheet)](https://support.google.com/drive/?p=visibility_options&hl=en_US). Currently, private Google Spreadsheets or Spreadsheets shared with "Specific people" are not supported.

*Donations for this plugin make up a chunk of my income. If you continue to enjoy this plugin, please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TJLPJYXHSRBEE&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer&item_number=Inline%20Google%20Spreadsheet%20Viewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted). :) Thank you for your support!*

= User guide =

You can transform your spreadsheet into an interactive chart or graph, embed documents other than spreadsheets, and customize the HTML of your table using a `[gdoc key=""]` [WordPress shortcode](https://codex.wordpress.org/Shortcode). The only required parameter is `key`, which specifies the document you'd like to retrieve. All additional attributes are optional.

After setting the appropriate Sharing setting, copy the URL you use to view the Spreadsheet from your browser's address bar into the shortcode. For example, to display the spreadsheet at `https://docs.google.com/spreadsheets/d/ABCDEFG/edit`, use the following shortcode in your WordPress post or page:

    [gdoc key="https://docs.google.com/spreadsheets/d/ABCDEFG/edit"]

If your spreadsheet uses the "old" Google Spreadsheets, you need to [ensure that your spreadsheet is "Published to the Web"](https://docs.google.com/support/bin/answer.py?hl=en&answer=47134) and you need to copy only the "key" out of the URL. For instance, if the URL of your old Google Spreadsheet is `https://docs.google.com/spreadsheets/pub?key=ABCDEFG`, then your shortcode should look like this:

    [gdoc key="ABCDEFG"]

Use the `gid` attribute to fetch data from a worksheet other than the first one (the one on the far left). For example, to display a worksheet published at `https://spreadsheets.google.com/pub?key=ABCDEFG&gid=4`, use the following shortcode in your WordPress post or page:

    [gdoc key="ABCDEFG" gid="4"]

To create an interactive chart from your Spreadsheet's data, use the `chart` attribute with a supported chart type. These include:

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

The `header_rows` attribute lets you specify how many rows should be rendered as the [table header](http://reference.sitepoint.com/html/thead). For example, to render a worksheet's top 3 rows inside the `<thead>` element, use:

    [gdoc key="ABCDEFG" header_rows="3"]

By default, all tables are progressively enhanced with jQuery [DataTables](https://datatables.net/) to provide sorting, searching, and pagination functions on the table display itself. If you'd like a specific table not to include this functionality, use the `no-datatables` `class` in your shortcode. For instance:

    [gdoc key="ABCDEFG" class="no-datatables"]

For DataTables-enhanced tables, you can also specify columns that you'd like to "freeze" when the user scrolls large tables horizontally. To do so, use the `FixedColumns-left-N` and `FixedColumns-right-N` classes, where `N` is the number of columns you'd like to freeze. For instance, to display the three left-most columns and the right-most column in a fixed (frozen) position, use the following in your shortcode:

    [gdoc key="ABCDEFG" class="FixedColumns-left-3 FixedColumns-right-1"]

Web addresses and email addresses in your data are turned into links. If this causes problems, you can disable this behavior by specifying `no` to the `linkify` attribute in your shortcode. For instance:

    [gdoc key="ABCDEFG" linkify="no"]

You can pre-process your Google Spreadsheet before retrieving data from it by passing a [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query to the shortcode's `query` attribute. This lets you interact with the data in your Google Spreadsheet as though the spreadsheet were a relational database table. For instance, if you wish to display the team that scored the most goals on your website, you might use a shortcode like this to query your Google Spreadsheet and display the highest-scoring team, where the team name is the first column (column `A`) and that team's score is the second column (column `B`):

    [gdoc key="ABCDEFG" query="select A where max(B)"]

Queries are also useful if your spreadsheet contains complex data from which many different charts can be created, allowing you to select only the parts of your spreadsheet that you'd like to use to compose the interactive chart.

You can also supply the URL of any file online to load a preview of that document on your blog. To do so, supply the file's URL as your `key`:

    [gdoc key="http://example.com/my_final_paper.pdf"]

To tweak the way your preview looks, you can use the `width`, `height`, or `style` attributes:

    [gdoc key="http://example.com/my_final_paper.pdf" style="min-height:780px;border:none;"]

== Installation ==

1. Upload the unzipped `inline-google-spreadsheet-viewer` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use the `[gdoc key="ABCDEFG"]` shortcode wherever you'd like to insert the Google Spreadsheet.

== Frequently Asked Questions ==

= Will my website be updated when my Google Spreadsheets change? =
Yes. Changes you make to your Google Spreadsheets will be shown on your website within a few minutes.

To improve your website's performance, Inline Google Spreadsheet Viewer automatically caches spreadsheets for 10 minutes. If you are making many changes quickly and/or you don't want to wait for the cache to expire on its own, you can add the `use_cache="no"` attribute to your shortcode to disable the caching mechanism:

    [gdoc key="ABCDEFG" use_cache="no"]

After you save and reload the page, you should see near-instant updates. Note that disabling the plugin's cache can result in decreased performance. Disabling the cache is recommended only for relatively small spreadsheets (less than 100 rows or so) or for debugging purposes.

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

All of [these DataTables options](https://datatables.net/reference/option/) are accessible through shortcode attributes. The shortcode attribute is an underscore-separated version of the DataTables's CamelCase'ed option name, prefixed with `datatables_`. For instance, to turn off paging, you need to set [the DataTables `paging` option](https://datatables.net/reference/option/paging) to `false`, so you would use a shortcode like this:

    [gdoc key="ABCDEFG" datatables_paging="false"]

Similarly, to change how many rows appear per page, you need to use [the DataTables `pageLength` option](https://datatables.net/reference/option/pageLength), setting it to a number. Its default is `10`, so if you wanted to show 15 rows per page, you would use a shortcode like this:

    [gdoc key="ABCDEFG" datatables_page_length="15"]

Some DataTables options need to be given JavaScript array literals, such as in the case of [the DataTables `order` option](https://datatables.net/reference/option/order), which controls the initial sort order for a table. However, using square brackets (`[` and `]`) inside a shortcode confuses the WordPress parser, so these characters must be URL-escaped (into `%5B` and `%5D`, respectively). Suppose you want your table to be sorted by the second column in descending order (instead of the first column in ascending order, which is the default). You need to supply a 2-dimensional array such as `[[ 1, "desc" ]]` to DataTable's `order` option (column counting begins at 0). In a shortcode, with the square brackets URL-escaped, this becomes:

    [gdoc key="ABCDEFG" datatables_order='%5B%5B 2, "desc" %5D%5D']

Note that when a JSON string literal is supplied in a shortcode attribute (`"desc"`), it must be double-quoted, so the shortcode attribute value itself must be single-quoted.

Alternatively, if you're able to add JavaScript to your theme, you can do all of these things, and more because any and all DataTables-enhanced tables can be modified by using the DataTables API.

For instance, to disable paging, add a JavaScript to your theme that looks like this:

    jQuery(window).load(function () {
        jQuery('#igsv-MY_TABLE_KEY').dataTable().api().page.len(-1).draw();
    });

Or, to have your DataTables-enhanced table automatically sort itself by the second column in descending order:

    jQuery(window).load(function () {
        jQuery('#igsv-MY_TABLE_KEY').dataTable().api().order([1, 'desc']).draw();
    });

(Replace `MY_TABLE_KEY` with the Google Spreadsheet document ID of your spreadsheet, of course.)

Please refer to the [DataTables API reference manual](https://datatables.net/reference/api) for more information about customizing DataTables-enhanced tables.

Another option for sorting your table, for example, is to use the `query` attribute and pass along an appropriate [Google Charts API Query Language query that includes an `order by` clause](https://developers.google.com/chart/interactive/docs/querylanguage#Order_By). In this case, however, you may want to disable DataTables's client-side sorting, as the data will be sorted in the HTML source.

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

See [Other Notes](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/other_notes/) for a complete list of attribute for configurable chart options.

= Why am I getting errors when I try to use the `query` attribute? =

If your `query` includes an angle bracket, such as a less than (`<`) or a greater than (`>`) sign, [WordPress will assume you are trying to write HTML](https://core.trac.wordpress.org/ticket/28564) and strip everything except the first word of your query, resulting in syntax error. Instead, use the URL-encoded equivalents of these characters (`%3C` and `%3E`, for `<` and `>`, respectively), which WordPress will pass to the plugin unmolested and which the plugin is specifically aware of how to handle correctly.

== Screenshots ==

1. Use a Google Spreadsheet or create a new one for your WordPress post or page. Make sure the Spreadsheet is "Public on the web." Learn more about [Google Docs sharing settings](https://support.google.com/docs/answer/2494886). If your spreadsheet was created a while ago and still uses an "old" style Google Spreadsheet, [use the "Publish as a webpage" option](https://support.google.com/docs/answer/183965). Make a note of the URL of your Google Spreadsheet's editing page.

2. On-screen help gives you instructions for using the plugin where you need it. Paste the address of your Google Spreadsheet into the `key` parameter of the plugin's shortcode (`[gdoc key="YOUR_SPREADSHEET_URL_HERE"]`), then save your post.

3. By default, Inline Google Spreadsheet Viewer produces a feature-rich HTML table on your site. Sort columns, filter rows, browse long tables by page number, show and hide individual columns, or export the table data in three different formats (CSV, Excel, and PDF). The plugin's ouput includes plenty of CSS and JavaScript hooks for unlimited customizability. Read [the FAQ](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/faq/) for coding details.

4. QuickTags integration lets you embed a spreadsheet with point-and-click ease.

5. Transform your spreadsheet's data into an interactive graph or chart by adding a single shortcode attribute. 11 chart types are supported, including `Area`, `Bar`, `Column`, `Pie`, `Line`, `Scatter` and more. Every chart can be customized with user-defined colors, opacity, and even 3D effects. There are over 50 configuration options to choose from. See [the FAQ](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/faq/) for a detailed list.

6. Use all the features of the [Google Query Language](https://developers.google.com/chart/interactive/docs/querylanguage) to pinpoint the exact data you want. Over 50 additional configuration options let you customize the exact way your graphs, charts, and tables look.

7. This screenshot shows an example of what the previous screenshot might output with a given spreadsheet that contains data for the Aliens, Ninjas, Pirates, and Robots teams, and their player's respective points.

== Change log ==

= Version 0.9 =

* Feature: Fully customizable DataTables.
    * You can now use a huge number of shortcode attributes to customize the features of your DataTables-enhanced tables. All [DataTables Feature, Data, Options, and Columns options groups](https://datatables.net/reference/option/) are supported through an underscore-separated shortcode attribute of a similar name. See [this plugin's FAQ](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/faq/) for details. See this plugin's [Other Notes](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/other_notes/) page for a complete list of shortcode attributes.
* Developer: Update DataTables library to version 1.10.6.

= Version 0.8.7 =

* Developer: New `gdoc_query` filter lets you hook into the Google Visuaization API queries supplied in the `query` attribute of the shortcode. Useful for dynamically generating queries for pre-processing, such as `SELECT`ing only rows referencing the current WordPress user by username or other identification.

= Version 0.8.6 =

* Feature: Automatically detect default site language and display some DataTables labels and buttons in that language.
    * Use the new `lang` attribute to declare that a particular spreadsheet's contents is in a language other than the default. For instance, use `lang="nl-NL"` to declare that a given spreadsheet is in Dutch.
    * Translations are available for all [DataTables internationalization plugins](https://www.datatables.net/plug-ins/i18n/).

= Version 0.8.5 =

* Feature: DataTables-enhanced tables now include the [Responsive](https://datatables.net/extensions/responsive/) plugin enabled by default. This plugin further improves narrow and liquid layouts by displaying columns that won't fit without scrolling horizontally in a child row instead. If you want to keep the old behavior (horizontal scrolling), add the `no-responsive` `class` to your shortcode, like this: `[gdoc key="ABCDEFG" class="no-responsive"]`
* Developer: Update [DataTables](https://datatables.net/) libraries to version 1.10.5.

= Version 0.8.4 =

* Developer: Two new plugin [filters](https://codex.wordpress.org/Plugin_API#Filters), `gdoc_table_html` and `gdoc_viewer_html`, allow other plugin or theme authors to customize the output produced by the `gdoc` shortcode. See the [Other Notes](https://wordpress.org/plugins/inline-google-spreadsheet-viewer/other_notes/) page for documentation and implementation notes.
* [Bugfix](https://wordpress.org/support/topic/bug-and-fix-script-dependencies): Correctly declare JavaScript dependencies.

= Version 0.8.3 =

* [Bugfix](https://wordpress.org/support/topic/how-to-show-cell-line-break): Stricter whitespace checking fixes an issue where space/newline character sequences were unintuitively interpreted.

= Version 0.8.2 =

* Feature: [WordPress Embeds](https://codex.wordpress.org/Embeds) support provides [OEmbed](http://oembed.com/) emulation, so now you don't even need to use a shortcode. In other words, pasting your Spreadsheet's `key` onto its own line is now the equivalent of invoking a shortcode like this: `[gdoc key="https://docs.google.com/spreadsheets/d/ABCDEFG/edit"]` 
    * This feature is only available for "new" Google Spreadsheets.

= Version 0.8.1 =

* [Bugfix](https://wordpress.org/support/topic/fatal-error-on-loading-table?replies=3): Properly serialize and store responses from Google with mismatched encoding.
* Bugfix: Properly save and load caches with shortcodes that use the `gid` attribute.
* Bugfix: Uninstaller correctly clears plugin cache.

= Version 0.8 =

* Feature: Built-in caching. To improve page load speeds, shortcodes whose `key`s output spreadsheet table data will be cached for ten minutes by default.
    * You can use the new `expire_in` attribute to set a custom cache lifetime (in seconds). For instance, to cache for 1 minute instead of 10, use `[gdoc key="ABCDEFG" expire_in="60"]`
    * To turn off caching for a specific spreadsheet, set the `use_cache` attribute to `no`, like `[gdoc key="ABCDEFG" use_cache="no"]`. Disabling the cache for a spreadsheet is only recommended for relatively small datasets (under 100 rows or so) that change often, or for debugging purposes.
    * Changing the `query` in a shortcode will also create a new cache, since a different query may return different data.

= Version 0.7.1 =

* Bugfix: Restore correct `<caption>` (shortcode content) handling.

= Version 0.7 =

* Feature: Previews for direct links to PDF, XLS, DOC, and other file formats supported by the [Google Docs Viewer](https://docs.google.com/viewer) now work, too. Simply use the direct URL to one such file hosted on the public Internet as your `key` to embed an HTML preview of that file.
* Feature: New shortcode attributes `style`, `width`, and `height` enable you to supply inline display dimensions. Note that when used on HTML tables, you must use the CSS equivalents for `width` and `height` by using the `style` attribute rather than the other two directly. For instance: `[gdoc key="ABDCEFG" class="no-datatables" style="width:50%;height:480px;"]`
* Bugfix: Correct `chart_pie_slice_text_style` attribute handling.

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

If you like this plugin, **please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=meitarm%40gmail%2ecom&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer%20WordPress%20Plugin&item_number=inline%2dgdocs%2dviewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) for your use of the plugin**, [purchasing one of Meitar's web development books](http://www.amazon.com/gp/redirect.html?ie=UTF8&location=http%3A%2F%2Fwww.amazon.com%2Fs%3Fie%3DUTF8%26redirect%3Dtrue%26sort%3Drelevancerank%26search-type%3Dss%26index%3Dbooks%26ref%3Dntt%255Fathr%255Fdp%255Fsr%255F2%26field-author%3DMeitar%2520Moscovitz&tag=maymaydotnet-20&linkCode=ur2&camp=1789&creative=390957) or, better yet, contributing directly to [Meitar's Cyberbusking fund](http://Cyberbusking.org/). (Publishing royalties ain't exactly the lucrative income it used to be, y'know?) Your support is appreciated!

= Shortcode attribute documentation =

This plugin provides one shortcode (`gdoc`) that can do many things through a combination of shortcode attributes. Every attribute must have a value. These attributes and their recognized values are documented here.

* `key` - Specifies the document to retrieve.
    * **required** Every `gdoc` shortcode must have one and only one `key` attribute. (All other attributes are optional.)
    * `key` can be one of three types:
        * The fully-qualified URL of a Google Spreadsheet that has been publicly shared, like `[gdoc key="https://docs.google.com/spreadsheets/d/ABCDEFG/edit"]`
        * The document ID of an old-style Google Spreadsheet that has been "Published to the web," like `[gdoc key="ABCDEFG"]`
        * The fully-qualified URL of a document on the Web. PDF, DOC, XLS, and other file formats supported by the [Google Docs Viewer](https://docs.google.com/viewer) will be rendered using the Viewer, like `[gdoc key="http://example.com/my_final_paper.pdf"]`
* `chart` - Displays Google Sheet data as a chart instead of a table. Valid values are:
    * `Area`
    * `Bar`
    * `Bubble`
    * `Candlestick`
    * `Column`
    * `Combo`
    * `Histogram`
    * `Line`
    * `Pie`
    * `Scatter`
    * `Stepped`
* `class` - An optional custom HTML `class` value or space-separated list of values. The following class names are treated specially:
    * `no-datatables` deactivates all DataTables features.
    * `no-responsive` deactivates only DataTables' Responsive features.
    * `FixedColumns-left-N` or `FixedColumns-right-N` freezes the left- or right-most `N` columns in the table, respectively.
* `expire_in` - How long to cache responses from Google for, in seconds. Set to `0` to cache forever. (Default: `600`, which is ten minutes.)
* `gid` - The ID of a worksheet in a Google Spreadsheet to load, other than the first one, like `[gdoc key="ABCDEFG" gid="123"]`
* `header_rows` - A number specifying how many rows to place in the output's `<thead>` element. (Default: `1`.)
* `height` - Height of the containing HTML element. Tables ignore this, use `style` instead. (Default: automatically calculated.)
* `lang` - The [ISO 639](http://www.iso.org/iso/home/standards/language_codes.htm) language code declaring the human language of the spreadsheet's contents. For instance, use `nl-NL` to declare that content is in Dutch. (Default: your site's [global language setting](https://codex.wordpress.org/WordPress_in_Your_Language).)
* `linkify` - Whether or not to automatically turn URLs, email addresses, and so on, into clickable links. Set to `no` to disable this behavior. (Default: `true`.)
* `query` - A [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query string, like `[gdoc key="ABCDEFG" query="select A where max(B)"]`. *Note:* Arrow bracktets (`<` and `>`) in queries must be URL-encoded (`%3C` and `%3E`, respectively) to avoid confusing the WordPress HTML parser. (Default: none.)
* `strip` - The number of leading rows to omit from the resulting HTML table. (Default: `0`.)
* `style` - An inline CSS rule applied to the containing HTML element. For example, to set a fixed height on a table, use `[gdoc key="ABCDEFG" style="height: 480px;"]`. (Default: none.)
* `summary` - A brief description of the information displayed for the `summary` attribute of the resulting HTML `<table>`. (Default: `Google Spreadsheet`.)
* `title` - An optional title for your data visualization or table. This is usually displayed in Web browsers as a tooltip when a user hovers over the table or is shown as the headline of a chart. (Default: none.)
* `use_cache` - Whether or not to cache spreadsheet data. Set this to `no` to disable caching for that shortcode invocation. (Default: `true`.)
* `width` - Width of the containing HTML element. Tables ignore this, use `style` instead. (Default: `100%`.)

= Chart customization options =

To use chart customization options, you must also choose a chart type by including the `chart` attribute.

The **complete list of attributes for configurable chart options** is below. Refer to [Google's Chart Gallery documentation](https://google-developers.appspot.com/chart/interactive/docs/gallery) for the type of chart you are using to learn more about which chart types support which chart options.

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
* `chart_pie_slice_text_style`
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

= DataTables customization options =

To use DataTables customization options, you must not supply the `no-datatables` class.

The **complete list of DataTables customization attributes** is below. Please refer to the [DataTables Options reference](https://datatables.net/reference/option/) for more information about each particular option.

* `datatables_auto_width`
* `datatables_defer_render`
* `datatables_info`
* `datatables_j_query_UI`
* `datatables_length_change`
* `datatables_ordering`
* `datatables_paging`
* `datatables_processing`
* `datatables_scroll_x`
* `datatables_scroll_y`
* `datatables_searching`
* `datatables_server_side`
* `datatables_state_save`
* `datatables_ajax`
* `datatables_data`
* `datatables_defer_loading`
* `datatables_destroy`
* `datatables_display_start`
* `datatables_dom`
* `datatables_length_menu`
* `datatables_order_cells_top`
* `datatables_order_classes`
* `datatables_order`
* `datatables_order_fixed`
* `datatables_order_multi`
* `datatables_page_length`
* `datatables_paging_type`
* `datatables_renderer`
* `datatables_retrieve`
* `datatables_scroll_collapse`
* `datatables_search_cols`
* `datatables_search_delay`
* `datatables_search`
* `datatables_state_duration`
* `datatables_stripe_classes`
* `datatables_tab_index`
* `datatables_column_defs`
* `datatables_columns`

= Plugin hooks =

This section documents hooks that the plugin implements. Developers of other plugins or themes can use these in their code to customize the way this plugin works.

== Filters ==

* `gdoc_table_html` - Filters the Google Spreadsheet data after it has been converted to an HTML `<table>` element. Some notes about this filter:
    * The most common use for this filter is to use [`html_entity_decode()`](https://php.net/html-entity-decode) to allow the data source to include raw HTML that will be displayed. This is considered a potential security risk and so is not recommended unless you are absolutely sure you need this functionality. In the majority of cases where users assume they need this functionality, it turns out there are other, more preferable alternatives, despite its convenience.
    * Another related use case for this filter is to allow [WordPress shortcodes](https://codex.wordpress.org/Shortcode) that are present in the data source to be evaluated at runtime. See [this thread](https://wordpress.org/support/topic/using-filter-hooks-1) for a brief discussion of that use case. However, this can also be problematic and is not recommended unless you are certain the shortcodes being used will not cause issues like invalid and broken markup, since most shortcode functions do not expect to be inside of an HTML `<table>`.
    * This filter runs immediately after HTML conversion is complete, but *before* that HTML is processed through the [`make_clickable()`](https://codex.wordpress.org/Function_Reference/make_clickable) function. This means that the value of the `linkify` shortcode attribute will affect the ultimate output of the shortcode invocation regardless of your filter function, and also means you should not call `make_clickable()` yourself.
* `gdoc_viewer_html` - Same as above, but applied to the `<iframe>` that loads the [Google Docs Viewer](https://docs.google.com/viewer). Use this filter to, for intance, customize the fallback content in the case that the user's browser does not support `<iframe>` elements.
* `gdoc_query` - Filters the Google Visualization API query language query. The first argument is the string supplied to the `query` attribute, or `false` if no query was supplied. The second argument is an array of all the attributes and their values passed to the current invocation of the shortcode.
    * A common use case for this filter is to query a Google Spreadsheet using dynamically generated content, such as the email address or username of a logged-in user.
