<?php
/**
 * Plugin Name: Inline Google Spreadsheet Viewer
 * Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
 * Description: Retrieves a published, public Google Spreadsheet and displays it as an HTML table.
 * Version: 0.4.1
 * Author: Meitar Moscovitz <meitar@maymay.net>
 * Author URI: http://meitarmoscovitz.com/
 */

class InlineGoogleSpreadsheetViewerPlugin {

    private $shortcode = 'gdoc';

    public function __construct () {
        add_shortcode($this->shortcode, array($this, 'displayShortcode'));
    }

    private function getDocUrl ($key, $gid) {
        $url = '';
        // Assume a full link.
        if ('pubhtml' === substr($key, -7) && 'http' === substr($key, 0, 4)) {
            $url .= $key;
        } else {
            $url .= "https://spreadsheets.google.com/pub?key=$key&output=csv";
            if ($gid) {
                $url .= "&single=true&gid=$gid";
            }
        }
        return $url;
    }

    private function fetchData ($url) {
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) { return false; } // bail on error
        return $resp;
    }

    private function parseCsv ($csv_str) {
        return $this->str_getcsv($csv_str); // Yo, why is PHP's built-in str_getcsv() frakking things up?
    }

    private function parseHtml ($html_str, $gid = 0) {
        $ret = array();

        $dom = new DOMDocument();
        $dom->loadHTML($html_str);
        $tables = $dom->getElementsByTagName('table');

        for ($i = 0; $i < $tables->length; $i++) {
            $rows = $tables->item($i)->getElementsByTagName('tr');
            for ($z = 0; $z < $rows->length; $z++) {
                $ths = $rows->item($z)->getElementsByTagName('th');
                foreach ($ths as $k => $node) {
                    $ret[$i][$z][$k] = $node->nodeValue;
                }
                $tds = $rows->item($z)->getElementsByTagName('td');
                foreach ($tds as $k => $node) {
                    $ret[$i][$z][$k] = $node->nodeValue;
                }
            }
        }

        // The 0'th table is the sheet names, the 1'st is the first sheet's data
        array_shift($ret);
        // Only return the correct "sheet."
        return $ret[$gid];
    }

    /**
     * @param $r array Multidimensional array representing table data.
     * @param $options array Values passed from the shortcode.
     * @param $caption string Passed via shortcode, should be the table caption.
     * @return An HTML string of the complete <table> element.
     * @see displayShortcode
     */
    private function dataToHtml ($r, $options, $caption) {
        if ($options['strip'] > 0) { $r = array_slice($r, $options['strip']); } // discard

        // Split into table headers and body.
        $thead = ((int) $options['header_rows']) ? array_splice($r, 0, $options['header_rows']) : array_splice($r, 0, 1);
        $tbody = $r;

        $ir = 1; // row number counter
        $ic = 1; // column number counter

        // Prepend a space character onto the 'class' value, if one exists.
        if (!empty($options['class'])) { $options['class'] = " {$options['class']}"; }

        $html = "<table id=\"igsv-{$options['key']}\" class=\"igsv-table{$options['class']}\" summary=\"{$options['summary']}\">";
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

    private function evenOrOdd ($x) {
        return ((int) $x % 2) ? 'odd' : 'even'; // cast to integer just in case
    }

    /**
     * Simple CSV parsing, taken directly from PHP manual.
     * @see http://www.php.net/manual/en/function.str-getcsv.php#100579
     */
    private function str_getcsv ($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
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
    public function displayShortcode ($atts, $content = null) {
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
            'key'      => false,                // Google Doc URL or ID
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'strip'    => 0,                    // If spreadsheet, how many rows to omit from top
            'header_rows' => 1                  // Number of rows in <thead>
        ), $atts, $this->shortcode);

        $resp = $this->fetchData($this->getDocUrl($x['key'], $x['gid']));
        return $this->displayData($resp, $x, $content);
    }

    private function displayData($resp, $atts, $content) {
        $html = '';
        $type = explode(';', $resp['headers']['content-type']);
        switch ($type[0]) {
            case 'text/html':
                $gid = ($atts['gid']) ? $atts['gid'] : 0;
                $r = $this->parseHtml($resp['body'], $gid);
                break;
            case 'text/csv':
            default:
                $r = $this->parseCsv($resp['body']);
            break;
        }
        $html .= $this->dataToHtml($r, $atts, $content);
        return $html;
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
