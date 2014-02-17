<?php
/**
 * Plugin Name: Inline Google Spreadsheet Viewer
 * Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
 * Description: Retrieves a published, public Google Spreadsheet and displays it as an HTML table.
 * Version: 0.3.3
 * Author: Meitar Moscovitz <meitar@maymay.net>
 * Author URI: http://meitarmoscovitz.com/
 */

class InlineGoogleSpreadsheetViewerPlugin {

    private $shortcode = 'gdoc';

    function __construct () {
        add_shortcode($this->shortcode, array($this, 'displayShortcode'));
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
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) { return false; } // bail on error
        //$r = (function_exists('str_getcsv')) ? str_getcsv($resp['body']) : $this->str_getcsv($resp['body']);
        $r = $this->str_getcsv($resp['body']); // Yo, why is PHP's built-in str_getcsv() frakking things up?
        if ($options['strip'] > 0) { $r = array_slice($r, $options['strip']); } // discard

        // Split into table headers and body.
        $thead = ((int) $options['header_rows']) ? array_splice($r, 0, $options['header_rows']) : array_splice($r, 0, 1);
        $tbody = $r;

        $ir = 1; // row number counter
        $ic = 1; // column number counter

        // Prepend a space character onto the 'class' value, if one exists.
        if (!empty($options['class'])) { $options['class'] = " {$options['class']}"; }

        $html  = "<table id=\"igsv-{$options['key']}\" class=\"igsv-table{$options['class']}\" summary=\"{$options['summary']}\">";
        if (!empty($caption)) {
            $html .= "<caption>$caption</caption>";
        }

        $html .= "<thead>\n";
        foreach ($thead as $v) {
            $html .= "<tr class=\"row-$ir " . $this->evenOrOdd($ir) . "\">";
            $ir++;
            $ic = 1; // reset column counting
            foreach ($v as $th) {
                $html .= "<th class=\"col-$ic " . $this->evenOrOdd($ic) . "\"><div>$th</div></td>";
                $ic++;
            }
            $html .= "</tr>";
        }
        $html .= "</thead><tbody>";

        foreach ($tbody as $v) {
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
     * Simple CSV parsing, taken directly from PHP manual.
     * @see http://www.php.net/manual/en/function.str-getcsv.php#100579
     */
    function str_getcsv ($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
        $temp=fopen("php://memory", "rw");
        fwrite($temp, $input);
        fseek($temp, 0);
        $r = array();
        while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
            $r[] = $data;
        }
        fclose($temp);
        return $r;
    }

    /**
     * WordPress Shortcode handler.
     */
    function displayShortcode ($atts, $content = null) {
        wp_enqueue_style(
            'jquery-datatables',
            '//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css'
        );
        wp_enqueue_script(
            'jquery-datatables',
            '//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js',
            'jquery'
        );
        wp_enqueue_script(
            'igsv-datatables',
            plugins_url('inline-gdocs-viewer.js', __FILE__),
            'jquery-datatables'
        );
        $x = shortcode_atts(array(
            'key'      => false,                // Google Doc ID
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'strip'    => 0,                    // If spreadsheet, how many rows to omit from top
            'header_rows' => 1                  // Number of rows in <thead>
        ), $atts, $this->shortcode);

        return $this->csvToHtml($x, $content);
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
