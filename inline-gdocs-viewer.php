<?php
/*
Plugin Name: Inline Google Spreadsheet Viewer [Patch by Max, maxyudin@gmail.com]
Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
Description: Retrieves a published, public Google Spreadsheet and displays it as an HTML table.
Version: 0.2
Author: Mr. Meitar Moscovitz
Author URI: http://maymay.net/
*/

/**
 * @copyright 2010 Meitar Moscovitz
 */

// Uncomment for assistance from WordPress in debugging.
define('WP_DEBUG', true);

// Filesystem path to this plugin.
define('GDOCS_VIEWER_PATH', WP_PLUGIN_DIR.'/'.dirname(plugin_basename(__FILE__)));

class InlineGoogleSpreadsheetViewerPlugin {

    /**
     * Constructor.
     */
    function InlineGoogleSpreadsheetViewerPlugin () {
        // empty for now
    }

    /**
     * Function csvToHtml grabs CSV data from a URL and returns an HTML table.
     *
     * @param $options array Values passed from the shortcode.
     * @param $caption string Passed via shortcode, should be the table caption.
     * @return An HTML string if successful, false otherwise.
     * @see displayShortcode
     */
    function csvToHtml ($options, $caption) {
        if (!$options['key']) { return false; }
        $url = "https://spreadsheets.google.com/pub?key={$options['key']}&output=csv";
        if ($options['gid']) {
            $url .= "&single=true&gid={$options['gid']}";
        }
        if (false === ($h = fopen($url, 'r')) ) { return false; } // bail on error
        $r = array();
        while (($x = fgetcsv($h)) !== false) {
            $r[] = $x;
        }
        if ($options['strip'] > 0) { $r = array_slice($r, $options['strip']); } // discard

        $ir = 1; // row number counter
        $ic = 1; // column number counter

        // Prepend a space character onto the 'class' value, if one exists.
        if (!empty($options['class'])) { $options['class'] = " {$options['class']}"; }

        $html  = "<table id=\"igsv-{$options['key']}\" class=\"igsv-table{$options['class']}\" summary=\"{$options['summary']}\">";
        if (!empty($caption)) {
            $html .= "<caption>$caption</caption>";
        }
        $html .= "<thead>\n";
        if( !empty($options['thead_rows']) ) {

            $table_head = $r;
            array_splice($table_head, $options['thead_rows']); // thead rows
            $table_body = array_slice($r, $options['thead_rows']); // tbody rows

            for($i=0;$i<$options['thead_rows'];$i++) {
                $html .= '<tr class="row-' . $ir . ' ' . $this->evenOrOdd($ir) . '">';
                foreach ($table_head[$i] as $v) {
                    $html .= '<th class="col-' . $ic . ' ' . $this->evenOrOdd($ic) .'"><div>'.$v.'</div></th>';
                    $ic++;
                }
                $html .= "</tr>";
                $ir++;
                $ic = 1; // reset column counting
            }
        }
        $html .= "</thead><tbody>";

        foreach ($table_body as $v) {
            $html .= "<tr class=\"row-$ir " . $this->evenOrOdd($ir) . "\">";
            $ir++;
            $ic = 1; // reset column counting
            foreach ($v as $td) {
                $html .= "<td class=\"col-$ic " . $this->evenOrOdd($ic) . "\">$td</td>";
                $ic++;
            }
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';

        return $html;
    }

    function evenOrOdd ($x) {
        return ((int) $x % 2) ? 'odd' : 'even'; // cast to integer just in case
    }

    /**
     * WordPress Shortcode handler.
     */
    function displayShortcode ($atts, $content = null) {
        $x = shortcode_atts(array(
            'key'      => false,                // Google Doc ID
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'strip'    => 0,                     // If spreadsheet, how many rows to omit from top
            'thead_rows' => 1                   // Number of rows in <thead>
        ), $atts);

        return $this->csvToHtml($x, $content);
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
add_shortcode('gdoc', array($inline_gdoc_viewer, 'displayShortcode'));
