<?php
/**
 * Plugin Name: Inline Google Spreadsheet Viewer
 * Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
 * Description: Retrieves a published, public Google Spreadsheet and displays it as an HTML table or interactive chart.
 * Version: 0.6
 * Author: Meitar Moscovitz <meitar@maymay.net>
 * Author URI: http://maymay.net/
 * Text Domain: inline-gdocs-viewer
 * Domain Path: /languages
 */

class InlineGoogleSpreadsheetViewerPlugin {

    private $shortcode = 'gdoc';
    private $invocations = 0;

    public function __construct () {
        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('admin_head', array($this, 'doAdminHeadActions'));
        add_action('admin_enqueue_scripts', array($this, 'addAdminScripts'));
        add_action('admin_print_footer_scripts', array($this, 'addQuickTagButton'));

        add_shortcode($this->shortcode, array($this, 'displayShortcode'));
    }

    public function registerL10n () {
        load_plugin_textdomain('inline-gdocs-viewer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function doAdminHeadActions () {
        $this->registerContextualHelp();
    }

    public function addAdminScripts () {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
    }

    private function getDocUrl ($key, $gid, $query) {
        $url = '';
        // Assume a full link.
        $m = array();
        if (preg_match('/\/(edit|pubhtml).*$/', $key, $m) && 'http' === substr($key, 0, 4)) {
            $parts = parse_url($key);
            $key = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
            $action = ($query) ? 'gviz/tq?tqx=out:csv&tq=' . urlencode($query) : 'export?format=csv';
            $url = str_replace($m[1], $action, $key);
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
        if (is_wp_error($resp)) { // bail on error
            throw new Exception('[' . __('Error requesting Google Spreadsheet data:', 'inline-gdocs-viewer') . $resp->get_error_message() . ']');
        }
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
            throw new Exception('[' . __('Error loading Google Spreadsheet data. Make sure your Google Spreadsheet is shared <a href="https://support.google.com/drive/answer/2494886?p=visibility_options">using either the "Public on the web" or "Anyone with the link" options</a>.', 'inline-gdocs-viewer') . ']');
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
        $title = esc_attr($options['title']);
        $html = "<table id=\"igsv-$id\" class=\"igsv-table$class\" summary=\"$summary\" title=\"$title\">";
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
        $script_dependencies = array();
        $x = shortcode_atts(array(
            'key'      => false,                // Google Doc URL or ID
            'title'    => '',                   // Title (attribute) text or visible chart title
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'strip'    => 0,                    // If spreadsheet, how many rows to omit from top
            'header_rows' => 1,                 // Number of rows in <thead>
            'linkify'  => true,                 // Whether to run make_clickable() on parsed data
            'query'    => false,                // Google Visualization Query Language querystring
            'chart'    => false                 // Type of Chart (for an interactive chart)
        ), $atts, $this->shortcode);
        $url = $this->getDocUrl($x['key'], $x['gid'], $x['query']);

        if (false === $x['chart']) {
            if (false === strpos($x['class'], 'no-datatables')) {
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
                $script_dependencies[] = 'jquery-datatables';
            }
            try {
                $output = $this->displayData($this->fetchData($url), $x, $content);
            } catch (Exception $e) {
                $output = $e->getMessage();
            }
        } else {
            // If a chart but no query, just query for entire spreadsheet
            if (false === $x['query']) {
                $url = preg_replace('/export\?format=csv/', 'gviz/tq', $url);
            }
            wp_enqueue_script('google-ajax-api', '//www.google.com/jsapi');
            $script_dependencies[] = 'google-ajax-api';
            $chart_id = 'igsv-' . $this->invocations . '-' . $x['chart'] . 'chart-'  . $this->getDocKey($x['key']);
            $output = '<div id="' . $chart_id . '" class="igsv-chart" title="' . esc_attr($x['title']) . '" data-chart-type="' . esc_attr(ucfirst($x['chart'])) . '" data-datasource-href="' . esc_attr($url) . '"></div>';
        }

        wp_enqueue_script(
            'inline-gdocs-viewer',
            plugins_url('inline-gdocs-viewer.js', __FILE__),
            $script_dependencies
        );

        $this->invocations++;
        return $output;
    }

    private function displayData($resp, $atts, $content) {
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
        return $this->dataToHtml($r, $atts, $content);
    }

    public function addQuickTagButton () {
        if (wp_script_is('quicktags')) {
?>
<script type="text/javascript">
jQuery(function () {
    var d = jQuery('#qt_content_igsv_sheet_dialog');
    d.dialog({
        'dialogClass'  : 'wp-dialog',
        'modal'        : true,
        'autoOpen'     : false,
        'closeOnEscape': true,
        'minWidth'     : 500,
        'buttons'      : {
            'add' : {
                'text'  : '<?php print esc_js(__('Add Spreadsheet', 'inline-gdocs-viewer'));?>',
                'class' : 'button-primary',
                'click' : function () {
                    var x = jQuery('#content').prop('selectionStart');
                    var cur_txt = jQuery('#content').val();
                    var new_txt = '[gdoc key="' + jQuery('#js-qt-igsv-sheet-key').val() + '"]';

                    jQuery('#content').val([cur_txt.slice(0, x), new_txt, cur_txt.slice(x)].join(''));

                    jQuery('#js-qt-igsv-sheet-key').val('');
                    jQuery(this).dialog('close');
                }
            }
        }
    });
    QTags.addButton(
        'igsv_sheet',
        'gdoc',
        function () {
            jQuery('#qt_content_igsv_sheet').on('click', function (e) {
                e.preventDefault();
                d.dialog('open');
            });
            jQuery('#qt_content_igsv_sheet').click();
        },
        '[/gdoc]',
        '',
        '<?php print esc_js(__('Inline Google Spreadsheet shortcode', 'inline-gdocs-viewer'));?>',
        130
    );
});
</script>
<div id="qt_content_igsv_sheet_dialog" title="<?php esc_attr_e('Insert inline Google Spreadsheet', 'inline-gdocs-viewer');?>">
    <p class="howto"><?php esc_html_e('Enter the key (web address) of your Google Spreadsheet', 'inline-gdocs-viewer');?></p>
    <div>
        <label>
            <span><?php esc_html_e('Key', 'inline-gdocs-viewer');?></span>
            <input style="width: 75%;" id="js-qt-igsv-sheet-key" placeholder="<?php esc_attr_e('paste your Spreadsheet URL here', 'inline-gdocs-viewer');?>" />
        </label>
    </div>
    <?php print $this->showDonationAppeal();?>
</div><!-- #qt_content_igsv_sheet_dialog -->
<?php
        }
    }

    private function registerContextualHelp () {
        $screen = get_current_screen();
        if ($screen->id !== 'post' ) { return; }

        $html = '<p>';
        $html .= sprintf(
            esc_html__('You can insert a Google Spreadsheet in this post. To do so, type %s[gdoc key="YOUR_SPREADSHEET_URL"]%s wherever you would like the spreadsheet to appear. Remember to replace YOUR_SPREADSHEET_URL with the web address of your Google Spreadsheet.', 'inline-gdocs-viewer'),
            '<kbd>', '</kbd>'
        );
        $html .= '</p>';
        $html .= '<p>';
        $html .= sprintf(
            esc_html__('Note that at this time, only Google Spreadsheets that have been shared using either the "Public on the web" or "anyone with the link" options will be visible on this page. If you are having trouble getting your Spreadsheet to show up on your website, you can get help from %sthe Inline Google Spreadsheet Viewer plugin support forum%s. Consider searching the support forum to see if your question has already been answered before posting a new thread.', 'inline-gdocs-viewer'),
            '<a href="https://wordpress.org/support/plugin/inline-google-spreadsheet-viewer/">', '</a>'
        );
        $html .= '</p>';
        ob_start();
        $this->showDonationAppeal();
        $x = ob_get_contents();
        ob_end_clean();
        $html .= $x;
        $screen->add_help_tab(array(
            'id' => $this->shortcode . '-' . $screen->base . '-help',
            'title' => __('Inserting a Google Spreadsheet', 'inline-gdocs-viewer'),
            'content' => $html
        ));
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-size: smaller; margin: 1em auto;"><?php print sprintf(
esc_html__('Inline Google Spreadsheet Viewer is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'inline-gdocs-viewer'),
'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=Inline%20Google%20Spreadsheet%20Viewer%20WordPress%20Plugin&amp;item_number=inline%2dgdocs%2dviewer&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . esc_html__('making a donation', 'inline-gdocs-viewer') . '</a>',
'<a href="http://Cyberbusking.org/">' . esc_html__('houseless, jobless, nomadic developer', 'inline-gdocs-viewer') . '</a>'
);?></p>
</div>
<?php
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
