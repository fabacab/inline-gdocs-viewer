=== Plugin Name ===
Contributors: meitar
Donate link: 
Tags: Google Docs, Google, Spreadsheet, shortcode
Requires at least: 2.5
Tested up to: 3.0.1
Stable tag: trunk

Retrieves a published, public Google Spreadsheet and displays it as an HTML table.

== Description ==

Fetches a published Google Spreadsheet using a `[gdoc key=""]` WordPress shortcode, then renders it as an HTML table. The only required parameter is `key`, which specifies the document you'd like to retrieve.

For example, to display a spreadsheet at `https://spreadsheets.google.com/pub?key=ABCDEFG`, use the following shortcode in your WordPress post or page:

    [gdoc key="ABCDEFG"]

Currently, this plugin only supports Google Spreadsheets that are "Published as a web page" and therefore public. Private Google Docs are not supported (yet).

== Installation ==

1. Upload `inline-gdocs-viewer.php` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use the `[gdoc key="ABCDEFG"]` shortcode wherever you'd like to insert the Google Spreadsheet.

== Frequently Asked Questions ==

= The default style is ugly. Can I change it? =

Yes. The plugin renders HTML with plenty of [CSS](http://en.wikipedia.org/wiki/Cascading_Style_Sheets) hooks. Use the `igsv-table` class from your style sheets to target the plugin's HTML output.

== Screenshots ==

1. Example styling.

== Change log ==

= Version 0.1 =

* Initial release.
