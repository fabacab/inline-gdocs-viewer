=== Plugin Name ===
Contributors: meitar
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TJLPJYXHSRBEE&lc=US&item_name=Inline%20Google%20Spreadsheet%20Viewer&item_number=Inline%20Google%20Spreadsheet%20Viewer&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Google Docs, Google, Spreadsheet, shortcode
Requires at least: 3.3
Tested up to: 4.0
Stable tag: 0.5

Embeds a public Google Spreadsheet in a WordPress post or page as an HTML table.

== Description ==

Fetches a publicly shared Google Spreadsheet using a `[gdoc key=""]` WordPress shortcode, then renders it as an HTML table, embedded in your blog post or page. The only required parameter is `key`, which specifies the document you'd like to retrieve. Optionally, you can also strip a certain number of rows (e.g., `strip="3"` omits the top 3 rows of the spreadsheet) and you can supply a table `summary`, `<caption>` and customized `class` value.

Your spreadsheet must be shared [using either the "Public on the web" or "Anyone with the link" options](https://support.google.com/drive/?p=visibility_options&hl=en_US). Currently, private Google Spreadsheets or Spreadsheets shared with "Specific people" are not supported.

After setting the appropriate Sharing setting, copy the URL you use to view the Spreadsheet from your browser's address bar into the shortcode. For example, to display the spreadsheet at `https://docs.google.com/spreadsheets/d/ABCDEFG/edit`, use the following shortcode in your WordPress post or page:

    [gdoc key="https://docs.google.com/spreadsheets/d/ABCDEFG/edit"]

If your spreadsheet uses the "old" Google Spreadsheets, you need to [ensure that your spreadsheet is "Published to the Web"](https://docs.google.com/support/bin/answer.py?hl=en&answer=47134) and you need to copy only the "key" out of the URL. For instance, if the URL of your old Google Spreadsheet is `https://docs.google.com/spreadsheets/pub?key=ABCDEFG`, then your shortcode should look like this:

    [gdoc key="ABCDEFG"]

To render the HTML table with additional metadata, you can also do the following:

    [gdoc key="ABCDEFG" class="my-sheet" summary="An example spreadsheet, with a summary."]This is the table's caption.[/gdoc]

The above shortcode will produce HTML that looks something like the following:

    <table id="igsv-ABCDEFG" class="igsv-table my-sheet" summary="An example spreadsheet, with a summary.">
        <caption>This is the table's caption.</caption>
        <!-- ...rest of table code using spreadsheet data here... -->
    </table>

You can use the `gid` attribute to embed a worksheet other than the first one (the one on the far left). For example, to display a worksheet published at `https://spreadsheets.google.com/pub?key=ABCDEFG&gid=4`, use the following shortcode in your WordPress post or page:

    [gdoc key="ABCDEFG" gid="4"]

The `header_rows` attribute lets you specify how many rows should be rendered as the [table header](http://reference.sitepoint.com/html/thead). For example, to render a worksheet's top 3 rows inside the `<thead>` element, use:

    [gdoc key="ABCDEFG" header_rows="3"]

All tables are progressively enhanced with jQuery [DataTables](https://datatables.net/) to provide sorting, searching, and pagination functions on the table display itself. If you'd like a specific table not to include this functionality, use the `no-datatables` `class` in your shortcode. For instance:

    [gdoc key="ABCDEFG" class="no-datatables"]

For DataTables-enhanced tables, you can also specify columns that you'd like to "freeze" when the user scrolls large tables horizontally. To do so, use the `FixedColumns-left-N` and `FixedColumns-right-N` classes, where `N` is the number of columns you'd like to freeze. For instance, to display the three left-most columns and the right-most column in a fixed (frozen) position, use the following in your shortcode:

    [gdoc key="ABCDEFG" class="FixedColumns-left-3 FixedColumns-right-1"]

Web addresses and email addresses in your data are turned into links. If this causes problems, you can disable this behavior by specifying `no` to the `linkify` attribute in your shortcode. For instance:

    [gdoc key="ABCDEFG" linkify="no"]

You can pre-process your Google Spreadsheet before retrieving data from it by passing a [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query to the shortcode's `query` attribute. This lets you interact with the data in your Google Spreadsheet as though the spreadsheet were a relational database table. For instance, if you maintain a spreadsheet of game results for a sports league and wish to display the team that scored the most goals in a single game, then assuming columns named "team" and "goals" existed in your spreadsheet, you might use a shortcode like this:

    [gdoc key="ABCDEFG" query="SELECT team WHERE max(goals)"]

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

= Can I remove certain columns from appearing on my webpage? =
If you're using the "new" Google Spreadsheets, you can strip out columns by `select`ing only those columns you wish to retrieve by passing a [Google Charts API Query Language](https://developers.google.com/chart/interactive/docs/querylanguage#Language_Syntax) query to the shortcode's `query` attribute. For example, to retrieve and display only the first, second, and third columns in a spreadsheet, use a shortcode like this:

    [gdoc key="ABCDEFG" query="select A, B, C"]

Alternatively, you can [hide columns using CSS](http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/comment-page-2/#comment-294582) with code such as, `.col-4 { display: none; }`, for example.

== How do I change the default settings, like can I turn paging off? Can I change the page length? Can I change the sort order? ==

If you're able to add JavaScript to your theme, you can do all of these things, and more. Any and all DataTables-enhanced tables can be modified by using the DataTables API.

For instance, to disable paging, add a JavaScript to your theme that looks like this:

    jQuery(window).load(function () {
        jQuery('#igsv-MY_TABLE_KEY').dataTable().api().page.len(-1).draw();
    });

Or, to have your DataTables-enhanced table automatically sort itself by the second column:

    jQuery(window).load(function () {
        jQuery(‘#igsv-MY_TABLE_KEY’).dataTable().api().order([1, 'desc']).draw();
    });

(Replace `MY_TABLE_KEY` with the Google Spreadsheet document ID of your spreadsheet, of course.)

Please refer to the [DataTables API reference manual](https://datatables.net/reference/api) for more information about customizing DataTables-enhanced tables.

Another option for sorting your table, for example, is to use the `query` attribute and pass along an appropriate [Google Charts API Query Language query that includes an `order by` clause](https://developers.google.com/chart/interactive/docs/querylanguage#Order_By).

== Change log ==

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
