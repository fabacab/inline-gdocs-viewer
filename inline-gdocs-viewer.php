<?php
/**
 * Plugin Name: Inline Google Spreadsheet Viewer
 * Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
 * Description: Retrieves a published, public Google Spreadsheet and displays it as an HTML table.
 * Version: 0.4.7
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
        $m = array();
        if (preg_match('/\/(edit|pubhtml).*$/', $key, $m) && 'http' === substr($key, 0, 4)) {
            $parts = parse_url($key);
            $key = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
            $url = str_replace($m[1], 'export?format=csv', $key);
            if ($gid) {
                $url .= '&gid=' . $gid;
            }
        } else {
            $url .= "https://spreadsheets.google.com/pub?key=$key&output=csv";
            if ($gid) {
                $url .= "&single=true&gid=$gid";
            }
        }
        return $url;
    }

    private function getDocKey ($key) {
        // Assume a full link.
        if ('http' === substr($key, 0, 4)) {
            $m = array();
            preg_match('/docs\.google\.com\/spreadsheets\/d\/([^\/]*)/i', $key, $m);
            return $m[1];
        } else {
            return $key;
        }
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
        @$dom->loadHTML($html_str);
        $tables = $dom->getElementsByTagName('table');

        // Error early, if no tables were found.
        if (0 === $tables->length) {
            throw new Exception(__('[Error loading Google Spreadsheet data. Make sure your Google Spreadsheet is shared <a href="https://support.google.com/drive/answer/2494886?p=visibility_options">using either the "Public on the web" or "Anyone with the link" options</a>.]', 'inline-gdocs-viewer'));
        }

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
        // Extract the document ID from the key, if a full URL was given.
        $key = $this->getDocKey($options['key']);

        $id = esc_attr($key);
        $class = esc_attr($options['class']);
        $summary = esc_attr($options['summary']);
        $html = "<table id=\"igsv-$id\" class=\"igsv-table$class\" summary=\"$summary\">";
        if (!empty($caption)) {
            $html .= '<caption>' . esc_html($caption) . '</caption>';
        }

        $html .= "<thead>\n";
        foreach ($thead as $v) {
            $html .= "<tr class=\"row-$ir " . $this->evenOrOdd($ir) . "\">";
            $ir++;
            $ic = 1; // reset column counting
            foreach ($v as $th) {
                $th = esc_html($th);
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
                $td = esc_html($td);
                $html .= "<td class=\"col-$ic " . $this->evenOrOdd($ic) . "\">$td</td>";
                $ic++;
            }
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';

        if (false === $options['linkify'] || 'no' === strtolower($options['linkify'])) {
            return $html;
        } else {
            return make_clickable($html);
        }
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
        // Core DataTables.
        wp_enqueue_style(
            'jquery-datatables',
            '//cdn.datatables.net/1.10.0/css/jquery.dataTables.css'
        );
        wp_enqueue_script(
            'jquery-datatables',
            '//cdn.datatables.net/1.10.0/js/jquery.dataTables.js',
            'jquery'
        );
        // DataTables extensions.
        wp_enqueue_style(
            'datatables-colvis',
            '//cdn.datatables.net/colvis/1.1.0/css/dataTables.colVis.css'
        );
        wp_enqueue_script(
            'datatables-colvis',
            '//cdn.datatables.net/colvis/1.1.0/js/dataTables.colVis.min.js',
            'jquery-datatables'
        );
        wp_enqueue_style(
            'datatables-tabletools',
            '//cdn.datatables.net/tabletools/2.2.1/css/dataTables.tableTools.css'
        );
        wp_enqueue_script(
            'datatables-tabletools',
            '//cdn.datatables.net/tabletools/2.2.1/js/dataTables.tableTools.min.js',
            'jquery-datatables'
        );
        wp_enqueue_style(
            'datatables-fixedcolumns',
            '//datatables.net/release-datatables/extensions/FixedColumns/css/dataTables.fixedColumns.css'
        );
        wp_enqueue_script(
            'datatables-fixedcolumns',
            '//datatables.net/release-datatables/extensions/FixedColumns/js/dataTables.fixedColumns.js',
            'jquery-datatables'
        );

        // Plugin initialization.
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
            'header_rows' => 1,                 // Number of rows in <thead>
            'linkify'  => true                  // Whether to run make_clickable() on parsed data
        ), $atts, $this->shortcode);

        $resp = $this->fetchData($this->getDocUrl($x['key'], $x['gid']));
        return $this->displayData($resp, $x, $content);
    }

    private function displayData($resp, $atts, $content) {
        $html = '';
        $type = explode(';', $resp['headers']['content-type']);
        try {
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
        } catch (Exception $e) {
            $html = $e->getMessage();
        }
        return $html;
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
