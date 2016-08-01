<?php
/**
 * The Buoy plugin for WordPress.
 *
 * WordPress plugin header information:
 *
 * * Plugin Name: Inline Google Spreadsheet Viewer
 * * Plugin URI: http://maymay.net/blog/projects/inline-google-spreadsheet-viewer/
 * * Description: Retrieves data from a public Google Spreadsheet or CSV file and displays it as an HTML table or interactive chart. <strong>Like this plugin? Please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Inline%20Google%20Spreadsheet%20Viewer&amp;item_number=Inline%20Google%20Spreadsheet%20Viewer&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="Send a donation to the developer of Inline Google Spreadsheet Viewer">donate</a>. &hearts; Thank you!</strong>
 * * Version: 0.10.2
 * * Author: Meitar Moscovitz <meitar@maymay.net>
 * * Author URI: http://maymay.net/
 * * Text Domain: inline-gdocs-viewer
 * * Domain Path: /languages
 *
 * @link https://developer.wordpress.org/plugins/the-basics/header-requirements/
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2010-2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\InlineGoogleSpreadsheetViewer
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Plugin class.
 */
class InlineGoogleSpreadsheetViewerPlugin {

    private $shortcode = 'gdoc';
    private $dt_class = 'igsv-table'; //< Default table class.
    private $dt_defaults; //< Defaults for DataTables defaults object.
    private $invocations = 0;
    private $prefix; //< Internal prefix for settings, etc., derived from shortcode.
    private $capabilities; //< List of custom capabilities.
    private $gdoc_url_regex =
        '!https://(?:docs\.google\.com/spreadsheets/d/|script\.google\.com/macros/s/)([^/]+)!';

    public function __construct () {
        // Initialize private defaults.
        $this->prefix = $this->shortcode . '_';
        $this->dt_defaults = json_encode(array(
            'dom' => "B<'clear'>lfrtip",
            'buttons' => array(
                'colvis', 'copy', 'csv', 'excel', 'pdf', 'print'
            )
        ));
        $this->capabilities = array(
            $this->prefix . 'query_sql_databases'
        );

        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('init', array($this, 'maybeFetchGvizDataSource'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('admin_head', array($this, 'doAdminHeadActions'));
        add_action('admin_enqueue_scripts', array($this, 'addAdminScripts'));
        add_action('admin_print_footer_scripts', array($this, 'addQuickTagButton'));

        add_action('wp_enqueue_scripts', array($this, 'addFrontEndScripts'));

        add_shortcode($this->shortcode, array($this, 'displayShortcode'));
        wp_embed_register_handler(
            $this->shortcode . 'spreadsheet',
            $this->gdoc_url_regex,
            array($this, 'oEmbedHandler')
        );

        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate () {
        $options = get_option($this->prefix . 'settings');
        if (!isset($options['datatables_classes'])) { $options['datatables_classes'] = $this->dt_class; }
        if (empty($options['datatables_defaults_object'])) {
            $options['datatables_defaults_object'] = json_decode($this->dt_defaults);
        }
        update_option($this->prefix . 'settings', $options);
        $admin_role = get_role('administrator');
        $admin_role->add_cap($this->prefix . 'query_sql_databases', true);
    }

    public function registerL10n () {
        load_plugin_textdomain('inline-gdocs-viewer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerSettings () {
        register_setting(
            $this->prefix . 'settings',
            $this->prefix . 'settings',
            array($this, 'validateSettings')
        );
    }

    public function registerAdminMenu () {
        add_options_page(
            __('Inline Google Spreadsheet Viewer Settings', 'inline-gdocs-viewer'),
            __('Inline Google Spreadsheet Viewer', 'inline-gdocs-viewer'),
            'manage_options',
            $this->prefix . 'settings',
            array($this, 'renderOptionsPage')
        );
    }

    public function doAdminHeadActions () {
        $this->registerContextualHelp();
    }

    public function addAdminScripts () {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-tabs');
        //wp_enqueue_script('jquery-ui-tooltip');

        wp_enqueue_style(
            'inline-gdocs-viewer',
            plugins_url('inline-gdocs-viewer.css', __FILE__)
        );
    }

    public function addFrontEndScripts () {
        $styles = array(
            'jquery-datatables' => array(
                'src' => '//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css'
            ),
            'datatables-buttons' => array(
                'src' => '//cdn.datatables.net/buttons/1.2.1/css/buttons.dataTables.min.css'
            ),
            'datatables-select' => array(
                'src' => '//cdn.datatables.net/select/1.2.0/css/select.dataTables.min.css'
            ),
            'datatables-fixedheader' => array(
                'src' => '//cdn.datatables.net/fixedheader/3.1.2/css/fixedHeader.dataTables.min.css'
            ),
            'datatables-fixedcolumns' => array(
                'src' => '//cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css'
            ),
            'datatables-responsive' => array(
                'src' => '//cdn.datatables.net/responsive/2.1.0/css/responsive.dataTables.min.css'
            )
        );

        $scripts = array(
            'jquery-datatables' => array(
                'src' => '//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
                'deps' => array('jquery')
            ),
            'datatables-buttons' => array(
                'src' => '//cdn.datatables.net/buttons/1.2.1/js/dataTables.buttons.min.js',
                'deps' => array('jquery-datatables')
            ),
            'datatables-buttons-colvis' => array(
                'src' => '//cdn.datatables.net/buttons/1.2.1/js/buttons.colVis.min.js',
                'deps' => array('datatables-buttons')
            ),
            'datatables-buttons-print' => array(
                'src' => '//cdn.datatables.net/buttons/1.2.1/js/buttons.print.min.js',
                'deps' => array('datatables-buttons')
            ),
            // PDFMake (required for DataTables' PDF buttons)
            'pdfmake' => array(
                'src' => '//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js',
                'deps' => array('datatables-buttons')
            ),
            'pdfmake-fonts' => array(
                'src' => '//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js',
                'deps' => array('pdfmake')
            ),
            // JSZip (required for DataTables' Excel button)
            'jszip' => array(
                'src' => '//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js',
                'deps' => array('datatables-buttons')
            ),
            'datatables-buttons-html5' => array(
                'src' => '//cdn.datatables.net/buttons/1.2.1/js/buttons.html5.min.js',
                'deps' => array('datatables-buttons')
            ),
            'datatables-select' => array(
                'src' => '//cdn.datatables.net/select/1.2.0/js/dataTables.select.min.js',
                'deps' => array('jquery-datatables')
            ),
            'datatables-fixedheader' => array(
                'src' => '//cdn.datatables.net/fixedheader/3.1.2/js/dataTables.fixedHeader.min.js',
                'deps' => array('jquery-datatables')
            ),
            'datatables-fixedcolumns' => array(
                'src' => '//cdn.datatables.net/fixedcolumns/3.2.2/js/dataTables.fixedColumns.min.js',
                'deps' => array('jquery-datatables')
            ),
            'datatables-responsive' => array(
                'src' => '//cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js',
                'deps' => array('jquery-datatables')
            ),
            'igsv-datatables' => array(
                'src' => plugins_url('igsv-datatables.js', __FILE__),
                'deps' => array('jquery-datatables')
            ),
            // Google Charts and Visualization libraries
            'google-ajax-api' => array(
                'src' => '//www.google.com/jsapi'
            ),
            'igsv-gvizcharts' => array(
                'src' => plugins_url('igsv-gvizcharts.js', __FILE__),
                'deps' => array('google-ajax-api')
            )
        );

        $styles  = apply_filters($this->prefix . 'enqueued_front_end_styles', $styles);
        $scripts = apply_filters($this->prefix . 'enqueued_front_end_scripts', $scripts);

        foreach ($styles as $handle => $style) {
            wp_enqueue_style(
                $handle,
                $style['src']
            );
        }

        foreach ($scripts as $handle => $script) {
            wp_enqueue_script(
                $handle,
                $script['src'],
                (isset($script['deps'])) ? $script['deps'] : array()
            );
        }

        if (wp_script_is('igsv-datatables', 'enqueued')) {
            wp_localize_script('igsv-datatables', 'igsv_plugin_vars', $this->getLocalizedPluginVars());
        }
    }

    /**
     * Deterministically makes a unique transient name.
     *
     * @param string $key The ID of the document, extracted from the key attribute of the shortcode.
     * @param string $q The query, if one exists, from the query attribute of the shortcode.
     * @return string A 40 character unique string representing the name of the transient for this key and query.
     * @see https://codex.wordpress.org/Transients_API
     */
    private function getTransientName ($key, $q, $gid) {
        return substr($this->shortcode . hash('sha1', $this->shortcode . $key . $q . $gid), 0, 40);
    }

    /**
     * This simple getter/setter pair works around a bug in WP's own
     * serialization, apparently, by serializing the data ourselves
     * and then base64 encoding it.
     */
    private function getTransient ($transient) {
        return unserialize(base64_decode(get_transient($transient)));
    }
    private function setTransient ($transient, $data, $expiry) {
        return set_transient($transient, base64_encode(serialize($data)), $expiry);
    }

    /**
     * Lazily tests the provided Google Doc "key" (URL or document ID)
     * to determine what type of document it really is. Valid doc
     * types are one of: `spreadsheet`, `gasapp`, `docsviewer`, or `csv`.
     *
     * @param string $key The key passed from the shortcode.
     * @return string A keyword referring to the type of document the key refers to.
     */
    private function getDocTypeByKey ($key) {
        $type = '';
        $p = parse_url($key);
        if ('csv' === strtolower(pathinfo($p['path'], PATHINFO_EXTENSION))) {
            $type = 'csv';
        } else if (!isset($p['scheme']) && 'wordpress' === $p['path']) {
            $type = 'wpdb';
        } else if ('mysql' === $p['scheme']) {
            $type = 'mysql';
        } else if (isset($p['host'])) {
            switch ($p['host']) {
                case 'docs.google.com':
                    $type = 'spreadsheet';
                    break;
                case 'script.google.com':
                    $type = 'gasapp';
                    break;
                default:
                    $type = 'docsviewer';
                    break;
            }
        } else {
            // without a host part, assume an old-style Spreadsheet
            $type = 'spreadsheet';
        }
        return $type;
    }

    private function getSpreadsheetUrl ($atts) {
        $url = '';
        // Assume a full link.
        $m = array();
        if (preg_match('/\/(edit|view|pubhtml|htmlview).*$/', $atts['key'], $m) && 'http' === substr($atts['key'], 0, 4)) {
            $parts = parse_url($atts['key']);
            if (!empty($parts['fragment'])) {
                $frag = array();
                parse_str($parts['fragment'], $frag);
                if ($frag['gid']) { $atts['gid'] = $frag['gid']; }
            }
            $atts['key'] = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
            $action = ($atts['query'] || $atts['chart'])
                ? 'gviz/tq?tqx=out:csv&tq=' . rawurlencode($atts['query'])
                : 'export?format=csv';
            $url = str_replace($m[1], $action, $atts['key']);
            if ($atts['gid']) {
                $url .= '&gid=' . $atts['gid'];
            }
        } else {
            $url .= "https://spreadsheets.google.com/pub?key={$atts['key']}&output=csv";
            if ($gid) {
                $url .= "&single=true&gid={$atts['gid']}";
            }
        }
        return $url;
    }

    private function getGVizDataSourceUrl ($key, $query, $format) {
        $format = ($format) ? $format : 'json';
        $base = trailingslashit(get_site_url()) . '?';
        $qs = 'url=';
        $qs .= rawurlencode($key) . '&tq=' . rawurlencode($query) . "&tqx=out:$format";
        return $base . $qs;
    }

    private function sanitizeQuery ($query) {
        // Due to shortcode parsing limitations of angle brackets (< and > characters),
        // manually decode only the URL encoded values for those values, which are
        // themselves expected to be entered manually by the user. That is, to supply
        // the shortcode with a less than sign, the user ought enter %3C, but after
        // the initial urlencode($query), this will encode the percent sign, returning
        // instead the value %253C, so we manually replace this in the query ourselves.
        return rawurldecode(str_replace('%253E', '%3E', str_replace('%253C', '%3C', rawurlencode($query))));
    }

    /**
     * Gets the shortcode's ID for output as an HTML ID attribute.
     *
     * @param string $key
     *
     * @uses sanitize_title_with_dashes()
     * @uses wp_salt()
     *
     * @return string
     */
    private function getDocId ($key) {
        $m = array();
        preg_match($this->gdoc_url_regex, $key, $m);
        if (!empty($m[1])) {
            $id = $m[1];
        } else {
            $id = sanitize_title_with_dashes($key);
        }
        if ('mysql' === $this->getDocTypeByKey($key)) {
            $id = hash('sha256', wp_salt() . $key);
        }
        return $id;
    }

    /**
     * @return array The roles capable of executing SQL directly from a shortcode.
     */
    function getSqlCapableRoles () {
        global $wp_roles;
        $sql_capable_roles = array();
        foreach ($wp_roles->roles as $k => $v) {
            if (array_key_exists($this->prefix . 'query_sql_databases', $v['capabilities'])) {
                $sql_capable_roles[$k] = $v;
            }
        }
        return $sql_capable_roles;
    }

    /**
     * Retrieves data from the transient cache if available, or via HTTP if not.
     *
     * @param string $url The URL to fetch, if not in cache.
     * @param array $x Values from the shortcode attributes.
     */
    private function fetchData ($url, $x) {
        $transient = $this->getTransientName($x['key'], $x['query'], $x['gid']);
        if (false === $x['use_cache'] || 'no' === strtolower($x['use_cache'])) {
            delete_transient($transient);
            $http_response = $this->doHttpRequest($url, $x['http_opts']);
        } else {
            if (false === ($http_response = $this->getTransient($transient))) {
                $http_response = $this->doHttpRequest($url, $x['http_opts']);
                $this->setTransient($transient, $http_response, (int) $x['expire_in']);
            }
        }
        return $http_response;
    }

    /**
     * Performs an HTTP request as instructed by the shortcode's parameters.
     *
     * @param string $url The URL to request.
     * @param string $http_opts A JSON string representing options to pass to the WordPress HTTP API.
     * @return array $resp The HTTP response from the WordPress HTTP API.
     */
    private function doHttpRequest ($url, $opts) {
        $http_args = array();
        if ($opts) {
            try {
                foreach (json_decode($opts) as $k => $v) {
                    $http_args[$k] = $v;
                }
            } catch (Exception $e) {
                $this->runtimeError(__('Error parsing HTTP options attribute:', 'inline-gdocs-viewer') . $e->getMessage());
            }
        }
        $resp = (empty($http_args)) ? wp_remote_get($url) : wp_remote_request($url, $http_args);
        if (is_wp_error($resp)) { // bail on error
            $this->runtimeError(__('Error requesting data:', 'inline-gdocs-viewer') . ' ' . $resp->get_error_message());
        }
        return $resp;
    }

    private function runtimeError ($msg) {
        throw new Exception(esc_html("[{$this->shortcode} $msg]"));
    }

    public function parseCsv ($csv_str) {
        return $this->str_getcsv($csv_str); // Yo, why is PHP's built-in str_getcsv() frakking things up?
    }

    /**
     * Prints an appropriate HTML attribute string for any HTML5 Data
     * attributes that DataTables can use.
     *
     * @param array $atts Values passed from the shortcode.
     * @return A string representing attribute-value pairs in HTML.
     */
    function dataTablesAttributes ($atts) {
        $str = '';
        foreach ($atts as $k => $v) {
            if (0 === strpos($k, 'datatables_') && false !== $v) {
                $k = str_replace('datatables', 'data', str_replace('_', '-', $k));
                // We urldecode() the value here because WordPress shortcodes
                // use square brackets, but so do JavaScript arrays so users
                // are advised to sometimes enter URL-encoded equivalents.
                $str .= esc_attr($k) . '=\'' . esc_attr(urldecode($v)) . '\' ';
            }
        }
        return $str;
    }

    /**
     * Converts a two-dimensional array representing rows and cells of data
     * into an HTML representation of that data, according to any additional
     * options passed to it.
     *
     * @param array $r Multidimensional array representing table data.
     * @param array $options Values passed from the shortcode.
     * @param string $caption Passed via shortcode, should be the table caption.
     * @return An HTML string of the complete <table> element.
     * @see displayShortcode
     */
    public function dataToHtml ($r, $options, $caption = '') {
        if ($options['strip'] > 0) { $r = array_slice($r, $options['strip']); } // discard

        // Split into table headers and body.
        $thead = ((int) $options['header_rows']) ? array_splice($r, 0, $options['header_rows']) : array_splice($r, 0, 1);
        $tbody = $r;

        $ir = 1; // row number counter
        $ic = 1; // column number counter

        $id = (0 === $this->invocations)
            ? 'igsv-' . $this->getDocId($options['key'])
            : "igsv-{$this->invocations}-" . $this->getDocId($options['key']);
        $html  = '<table id="' . esc_attr($id) . '"';
        // Prepend a space character onto the 'class' value, if one exists.
        if (!empty($options['class'])) { $options['class'] = " {$options['class']}"; }
        $html .= ' class="' . $this->dt_class . esc_attr($options['class']) . '"';
        $html .= ' lang="' . esc_attr($options['lang']) . '"';
        $html .= (false === $options['summary']) ? '' : ' summary="' . esc_attr($options['summary']) . '"';
        $html .= (false === $options['title']) ? '' : ' title="' . esc_attr($options['title']) . '"';
        $html .= ' style="' . esc_attr($options['style']) . '"';
        $html .= (array_search('no-datatables', explode(' ', $options['class'])))
            ? ''
            : ' ' . $this->dataTablesAttributes($options);
        $html .= '>';

        if (!empty($caption)) {
            $html .= '<caption>' . esc_html($caption) . '</caption>';
        }

        $html .= "<thead>\n";
        foreach ($thead as $v) {
            $html .= "<tr class=\"row-$ir " . $this->evenOrOdd($ir) . "\">";
            $ir++;
            $ic = 1; // reset column counting
            foreach ($v as $th) {
                $th = nl2br(esc_html($th));
                $html .= "<th class=\"col-$ic " . $this->evenOrOdd($ic) . "\"><div>$th</div></th>";
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
                $td = nl2br(esc_html($td));
                $el = ($ic <= $options['header_cols']) ? 'th' : 'td';
                $html .= "<$el class=\"col-$ic " . $this->evenOrOdd($ic) . "\">$td</$el>";
                $ic++;
            }
            $html .= "</tr>";
        }
        $html .= '</tbody></table>';

        $html = apply_filters($this->shortcode . '_table_html', $html);

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

    public function oEmbedHandler ($matches, $attr, $url, $rawattr) {
        return $this->displayShortcode(array('key' => $url));
    }

    public function maybeFetchGvizDataSource () {
        if (
            !isset($_GET[$this->prefix . 'get_datasource_nonce'])
            ||
            !$this->isValidNonce($_GET[$this->prefix . 'get_datasource_nonce'], $this->prefix . 'get_datasource_nonce')
        ) { return; }
        $url = rawurldecode($_GET['url']);
        $http_response = $this->doHttpRequest(esc_url($url), false);

        if (isset($_GET['chart'])) {
            $http_response['body'] = $this->setGVizCsvDataTypes($http_response['body']);
        }

        require_once dirname(__FILE__) . '/lib/vistable.php';
        $vt = new csv_vistable(
            (isset($_GET['tqx']))  ? $_GET['tqx'] : '',
            (isset($_GET['tq']))   ? $_GET['tq']  : '',
            (isset($_GET['tqrt'])) ? $_GET['tqrt']: '',
            (isset($_GET['tz']))   ? $_GET['tz']  : 'PDT', // TODO: will get_option('timezone_string') work?
            get_locale(),
            array()
        );
        $vt->setup_table($http_response['body']);
        print @$vt->execute();
        exit;
    }

    /**
     * When trying to do a chart on standard CSV data,
     * the vistable library needs help to hint at the
     * data types of columns, or else it'll always treat
     * the data as a string.
     *
     * @param string $csv_str The raw CSV data.
     * @return string The same CSV data with a type-hinted header row.
     */
    private function setGVizCsvDataTypes ($csv_str) {
        $data = $this->parseCsv($csv_str);
        $head = array_shift($data);
        $cols = array();
        // peek at lines 2 through 20 (not the header)
        $peek = (count($data) > 20) ? 20 : count($data);
        for ($i = 0; $i < $peek; $i++) {
            foreach ($data[$i] as $k => $v) {
                if (ctype_digit($v) || preg_match('/^[0-9]+(?:\.[0-9]*)?$/', $v)) {
                    $cols[$k] = 'number';
                } else if (strtotime($v)) {
                    $cols[$k] = 'datetime';
                } else {
                    $cols[$k] = 'string';
                }
            }
        }
        $head_typed = array();
        foreach ($head as $k => $v) {
            if ('string' === $cols[$k]) {
                $head_typed[] = $v;
            } else {
                $head_typed[] = $v . ' as ' . $cols[$k];
            }
        }
        array_unshift($data, $head_typed);
        $lines = array();
        foreach ($data as $row) {
            $lines[] = implode(',', $row);
        }
        return implode("\n", $lines);
    }

    private function makeNonceUrl ($url) {
        $options = get_option($this->prefix . 'settings');
        $options[$this->prefix . 'get_datasource_nonce'] = wp_create_nonce($this->prefix . 'get_datasource_nonce');
        update_option($this->prefix . 'settings', $options);
        $p = parse_url($url);
        return $p['scheme']
            . '://' . $p['host'] . $p['path']
            . '?' . $p['query'] . '&'
            . $this->prefix . 'get_datasource_nonce=' . $options[$this->prefix . 'get_datasource_nonce'];
    }

    private function isValidNonce ($nonce, $nonce_name) {
        $options = get_option($this->prefix . 'settings');
        $is_valid = ($nonce === $options[$nonce_name]) ? true : false;
        update_option($this->prefix . 'settings', $options);
        return $is_valid;
    }

    /**
     * WordPress Shortcode handler.
     */
    public function displayShortcode ($atts, $content = null) {
        $atts = shortcode_atts(array(
            'key'      => false,                // Google Doc URL or ID
            'title'    => false,                // Title (attribute) text or visible chart title
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => false,                // If spreadsheet, value for summary attribute
            'width'    => '100%',
            'height'   => false,
            'style'    => false,
            'strip'    => 0,                    // If spreadsheet, how many rows to omit from top
            'header_cols' => 0,                 // Number of columns to write as <th> elements
            'header_rows' => 1,                 // Number of rows in <thead>
            'use_cache' => true,                // Whether to use Transients API for fetched data.
            'http_opts' => false,               // Arguments to pass to the WordPress HTTP API.
            // TODO: Make a plugin option setting for default transient expiry time.
            'expire_in' => 10*MINUTE_IN_SECONDS,// Custom time-to-live of cached transient data.
            'lang'     => get_bloginfo('language'),
            'linkify'  => true,                 // Whether to run make_clickable() on parsed data.
            'query'    => false,                // Google Visualization Query Language querystring
            'chart'    => false,                // Type of Chart (for an interactive chart)

            // Depending on the type of chart, the following options may be available.
            'chart_aggregation_target'         => false,
            'chart_animation'                  => false,
            'chart_annotations'                => false,
            'chart_area_opacity'               => false,
            'chart_axis_titles_position'       => false,
            'chart_background_color'           => false,
            'chart_bars'                       => false,
            'chart_bubble'                     => false,
            'chart_candlestick'                => false,
            'chart_chart_area'                 => false,
            'chart_color_axis'                 => false,
            'chart_colors'                     => false,
            'chart_crosshair'                  => false,
            'chart_curve_type'                 => false,
            'chart_data_opacity'               => false,
            'chart_dimensions'                 => false,
            'chart_enable_interactivity'       => false,
            'chart_explorer'                   => false,
            'chart_focus_target'               => false,
            'chart_font_name'                  => false,
            'chart_font_size'                  => false,
            'chart_force_i_frame'              => false,
            'chart_green_color'                => false,
            'chart_green_from'                 => false,
            'chart_green_to'                   => false,
            'chart_h_axes'                     => false,
            'chart_h_axis'                     => false,
            'chart_height'                     => false,
            'chart_interpolate_nulls'          => false,
            'chart_is_stacked'                 => false,
            'chart_legend'                     => false,
            'chart_line_width'                 => false,
            'chart_major_ticks'                => false,
            'chart_max'                        => false,
            'chart_min'                        => false,
            'chart_minor_ticks'                => false,
            'chart_orientation'                => false,
            'chart_pie_hole'                   => false,
            'chart_pie_residue_slice_color'    => false,
            'chart_pie_residue_slice_label'    => false,
            'chart_pie_slice_border_color'     => false,
            'chart_pie_slice_text'             => false,
            'chart_pie_slice_text_style'       => false,
            'chart_pie_start_angle'            => false,
            'chart_point_shape'                => false,
            'chart_point_size'                 => false,
            'chart_red_color'                  => false,
            'chart_red_from'                   => false,
            'chart_red_to'                     => false,
            'chart_reverse_categories'         => false,
            'chart_selection_mode'             => false,
            'chart_series'                     => false,
            'chart_size_axis'                  => false,
            'chart_slice_visibility_threshold' => false,
            'chart_slices'                     => false,
            'chart_theme'                      => false,
            'chart_title_position'             => false,
            'chart_title_text_style'           => false,
            'chart_tooltip'                    => false,
            'chart_trendlines'                 => false,
            'chart_v_axes'                     => false,
            'chart_v_axis'                     => false,
            'chart_width'                      => false,
            'chart_yellow_color'               => false,
            'chart_yellow_from'                => false,
            'chart_yellow_to'                  => false,
            // For some reason this isn't parsing?
            //'chart_is3D'                       => false,

            // DataTables's HTML5 data- attributes.
            // DataTables Features
            // @see https://www.datatables.net/reference/option/#Features
            'datatables_auto_width'    => false,
            'datatables_buttons'       => false,
            'datatables_defer_render'  => false,
            'datatables_info'          => false,
            'datatables_j_query_UI'    => false,
            'datatables_length_change' => false,
            'datatables_ordering'      => false,
            'datatables_paging'        => false,
            'datatables_processing'    => false,
            'datatables_scroll_x'      => false,
            'datatables_scroll_y'      => false,
            'datatables_searching'     => false,
            'datatables_select'        => false,
            'datatables_server_side'   => false,
            'datatables_state_save'    => false,

            // DataTables Data
            // @see https://www.datatables.net/reference/option/#Data
            'datatables_ajax' => false,
            'datatables_data' => false,

            // DataTables Options
            // @see https://www.datatables.net/reference/option/#Options
            'datatables_defer_loading'   => false,
            'datatables_destroy'         => false,
            'datatables_display_start'   => false,
            'datatables_dom'             => false,
            'datatables_length_menu'     => false,
            'datatables_order_cells_top' => false,
            'datatables_order_classes'   => false,
            'datatables_order'           => false,
            'datatables_order_fixed'     => false,
            'datatables_order_multi'     => false,
            'datatables_page_length'     => false,
            'datatables_paging_type'     => false,
            'datatables_renderer'        => false,
            'datatables_retrieve'        => false,
            'datatables_scroll_collapse' => false,
            'datatables_search_cols'     => false,
            'datatables_search_delay'    => false,
            'datatables_search'          => false,
            'datatables_state_duration'  => false,
            'datatables_stripe_classes'  => false,
            'datatables_tab_index'       => false,

            // DataTables Columns
            // @see https://www.datatables.net/reference/option/#Columnes
            'datatables_column_defs' => false,
            'datatables_columns'     => false,
        ), $atts, $this->shortcode);

        $atts['key'] = $this->sanitizeKey($atts['key']);
        $atts['query'] = apply_filters($this->shortcode . '_query', $this->sanitizeQuery($atts['query']), $atts);

        try {
            switch ($this->getDocTypeByKey($atts['key'])) {
                case 'wpdb':
                case 'mysql':
                    $output = $this->getSqlOutput($atts, $content);
                    break;
                default:
                    $output = $this->getHttpOutput($atts, $content);
                break;
            }
        } catch (Exception $e) {
            $output = $e->getMessage();
        }
        $this->invocations++;
        return $output;
    }

    /**
     * Returns the output of an HTTP datasource.
     *
     * @param array $x The shortcode attributes.
     * @param string $content The content of the shortcode.
     * @return string The HTML output as requested by the shortcode or an error message.
     */
    private function getHttpOutput ($x, $content) {
        // Set up datasource URL.
        $url = $x['key']; // in the default case, the URL is the shortcode's key
        $key_type = $this->getDocTypeByKey($x['key']);
        if ('spreadsheet' === $key_type) {
            // if a Google Spreadsheet, the URL to fetch needs to be modified.
            $url = $this->getSpreadsheetUrl($x);
        } else if ('gasapp' !== $key_type) { // don't change the GAS app's URL
            if (!empty($x['chart'])) { $fmt = 'json'; }
            else { $fmt = 'csv'; }
            // the url should be proxied through this plugin
            $url = $this->makeNonceUrl($this->getGVizDataSourceUrl($x['key'], $x['query'], $fmt));
        }

        // Retrieve and set HTML output.
        if ('docsviewer' === $key_type) {
            $output = $this->getGDocsViewerOutput($x);
        } else {
            if (false === $x['chart']) {
                $http_response = $this->fetchData($url, $x);
                $http_content_type = explode(';', $http_response['headers']['content-type']);
                switch ($http_content_type[0]) {
                    case 'text/csv':
                        // This catches any HTTP response served as text/csv
                        $output = $this->csvToDataTable($http_response['body'], $x, $content);
                        break;
                    default:
                        $output = apply_filters($this->shortcode . '_webapp_html', $http_response['body'], $x);
                        if ('csv' === $key_type) {
                            // even if the response is text/plain, parse as CSV if the filename
                            // we detected earlier (by using the key attribute) suggests it is.
                            $output = $this->csvToDataTable($output, $x, $content);
                        }
                        break;
                }
            } else {
                $output = $this->getGVizChartOutput($url, $x);
            }
        }
        return $output;
    }

    /**
     * Returns the output of a SQL datasource.
     *
     * @param array $atts The shortcode attributes.
     * @param string $content The content of the shortcode.
     * @return string The HTML output as requested by the shortcode or an error message.
     */
    private function getSqlOutput ($atts, $content) {
        if (!$this->isSqlDbEnabled()) {
            $this->runtimeError(
                esc_html__('Error:', 'inline-gdocs-viewer') . ' '
                . esc_html__('SQL datasources are disabled.', 'inline-gdocs-viewer')
            );
        }
        if (!$this->canQuerySqlDatabases()) {
            $this->runtimeError(
                esc_html__('Error:', 'inline-gdocs-viewer') . ' '
                . esc_html__('The author does not have permission to perform a SQL query.', 'inline-gdocs-viewer')
            );
        }

        $query = trim($atts['query']);
        if (empty($query)) {
            $this->runtimeError(
                esc_html__('Error:', 'inline-gdocs-viewer') . ' '
                . esc_html__('Missing query.', 'inline-gdocs-viewer')
            );
        }

        if (0 !== strpos(strtoupper($query), 'SELECT')) {
            $this->runtimeError(
                esc_html__('Error:', 'inline-gdocs-viewer') . ' '
                . esc_html__('Unsupported query:', 'inline-gdocs-viewer')
                . ' ' . esc_html($query)
            );
        }

        if ('wpdb' === $this->getDocTypeByKey($atts['key'])) {
            global $wpdb;
        } else {
            $p = parse_url($atts['key']);
            $wpdb = new wpdb(
                isset($p['user']) ? $p['user'] : '',
                isset($p['pass']) ? $p['pass'] : '',
                isset($p['path']) ? basename($p['path']) : '',
                isset($p['port']) ? "{$p['host']}:{$p['port']}" : $p['host']
            );
        }
        $data = $wpdb->get_results($query, ARRAY_A);
        if (empty($data)) {
            $this->runtimeError(
                esc_html__('Error:', 'inline-gdocs-viewer') . ' '
                . esc_html__('Query produced zero results:', 'inline-gdocs-viewer')
                . ' ' . esc_html($query)
            );
        }
        $header = array(array()); // 2D
        foreach ($data[0] as $k => $v) {
            $header[0][] = $k;
        }
        $output = $this->dataToHtml(array_merge($header, $data), $atts, $content);
        return $output;
    }

    /**
     * Whether or not the "Allow SQL queries in shortcodes" option is enabled.
     *
     * @return bool
     */
    private function isSqlDbEnabled () {
        $options = get_option($this->prefix . 'settings');
        return isset($options['allow_sql_db_queries']);
    }
    /**
     * Determines if a user has the required capability to run a SQL query from the shortcode.
     *
     * @return bool Whether or not the author of the current post can do SQL queries.
     */
    private function canQuerySqlDatabases () {
        $author = get_userdata(get_the_author_meta('ID'));
        return $author->has_cap($this->prefix . 'query_sql_databases');
    }

    /**
     * WordPress mangles some HTML in subtle ways. Clean that up.
     *
     * @param string $key The value passed to the shortcode's `key` attribute.
     * @return string The "sanitized" key value.
     */
    private function sanitizeKey ($key) {
        return str_replace('&#038;', '&', $key);
    }

    public function csvToDataTable ($csv, $x, $content) {
        $data = $this->parseCsv($csv);
        return $this->dataToHtml($data, $x, $content);
    }

    private function getGDocsViewerOutput ($x) {
        $output  = '<iframe src="';
        $output .= esc_attr('https://docs.google.com/viewerng/viewer?url=' . esc_url($x['key']) . '&embedded=true');
        $output .= '" width="' . esc_attr($x['width']) . '" height="' . esc_attr($x['height']) . '" style="' . esc_attr($x['style']) . '">';
        $output .= esc_html__('Your Web browser must support inline frames to display this content:', 'inline-gdocs-viewer');
        $output .= ' <a href="' . esc_attr($x['key']) . '">' . esc_html($x['title']) . '</a>';
        $output .= '</iframe>';
        return apply_filters($this->shortcode . '_viewer_html', $output);
    }

    public function getGVizChartOutput ($url, $x) {
        $chart_id = 'igsv-' . $this->invocations . '-' . $x['chart'] . 'chart-'  . $this->getDocId($x['key']);
        $output  = '<div id="' . esc_attr($chart_id) . '" class="igsv-chart" title="' . esc_attr($x['title']) . '"';
        $output .= ' data-chart-type="' . esc_attr(ucfirst($x['chart'])) . '"';
        $output .= ' data-datasource-href="' . esc_attr($url) . '&chart=true"';
        if ($chart_opts = $this->getChartOptions($x)) {
            foreach ($chart_opts as $k => $v) {
                if (!empty($v)) {
                    // use single-quoted attribute-value syntax for later JSON parsing in JavaScript
                    $output .= ' data-' . str_replace('_', '-', $k) . "='" . $v . "'";
                }
            }
        }
        $output .= '></div>'; // .igsv-chart
        return $output;
    }

    /**
     * Retrieves the global plugin options.
     *
     * @return array An array of data suitable for passing to wp_localize_script().
     * @see https://codex.wordpress.org/Function_Reference/wp_localize_script
     */
    private function getLocalizedPluginVars () {
        $options = get_option($this->prefix . 'settings', array());
        $data = array(
            'lang_dir' => plugins_url('languages', __FILE__)
        );
        if (empty($options)) {
            $data['datatables_classes'] = ".{$this->dt_class}:not(.no-datatables)";
        } else {
            $dt_classes = array();
            foreach (explode(' ', $options['datatables_classes']) as $cls) {
                $cls = (empty($cls)) ? $this->dt_class : $cls;
                $dt_classes[] = ".$cls:not(.no-datatables)";
            }
            $data['datatables_classes'] = implode(', ', $dt_classes);
            $data['datatables_defaults_object'] = $options['datatables_defaults_object'];
        }
        return $data;
    }

    private function getChartOptions($atts) {
        $opts = array();
        foreach ($atts as $k => $v) {
            if (0 === strpos($k, 'chart_')) {
                $opts[$k] = $v;
            }
        }
        return $opts;
    }

    public function addQuickTagButton () {
        $screen = get_current_screen();
        if (wp_script_is('quicktags') && 'post' === $screen->base) {
?>
<script type="text/javascript">
jQuery(function () {
    var IGSV_QT = {};
    IGSV_QT.d = jQuery('#qt_content_igsv_dialog');
    IGSV_QT.d.find('#qt_content_igsv_dialog_tabs_container').tabs({
//        'beforeActivate': function (e, ui) {
//            if ('igsv-datasource-tab' === ui.newTab.context.getAttribute('id')) { return; }
//            var is_valid = IGSV_QT.d.find('form').get(0).checkValidity();
//            if (!is_valid) {
//                IGSV_QT.d.find('[name]:invalid').each(function () {
//                    jQuery(this).tooltip();
//                });
//                IGSV_QT.d.find('form :invalid:first-of-type').focus();
//            } else {
//                IGSV_QT.d.find('[name]').each(function () {
//                    var x = jQuery(this).tooltip('instance');
//                    if (x) { x.destroy(); }
//                })
//
//            }
//            return is_valid;
//        }
    });
    IGSV_QT.d.dialog({
        'dialogClass'  : 'wp-dialog',
        'modal'        : true,
        'autoOpen'     : false,
        'closeOnEscape': true,
        'minWidth'     : 700,
        'buttons'      : {
            'add' : {
                'text'  : '<?php print esc_js(__('Add Spreadsheet', 'inline-gdocs-viewer'));?>',
                'class' : 'button-primary',
                'click' : function () {
                    var x = jQuery('#content').prop('selectionStart');
                    var cur_txt = jQuery('#content').val();
                    var new_txt = '[gdoc ' + shortcodeAttributes(getValues()) + ']';
                    jQuery('#content').val([cur_txt.slice(0, x), new_txt, cur_txt.slice(x)].join(''));
                    resetDialogUi();
                    jQuery(this).dialog('close');

                    function getValues () {
                        var atts = {};
                        var http_opts = {};
                        jQuery('#qt_content_igsv_dialog input[type="text"]').each(function () {
                            if (jQuery(this).val().length) {
                                atts[jQuery(this).attr('name')] = jQuery(this).val();
                            }
                        });
                        jQuery('#qt_content_igsv_dialog input[type="number"]').each(function () {
                            if (jQuery(this).val().length) {
                                atts[jQuery(this).attr('name')] = parseInt(jQuery(this).val());
                            }
                        });
                        jQuery('#qt_content_igsv_dialog input[type="checkbox"]').each(function () {
                            if (false === jQuery(this).prop('checked')) {
                                atts[jQuery(this).attr('name')] = 'no';
                            }
                        });
                        for (k in atts) {
                            if (0 === k.indexOf('http_opts')) {
                                if ('no' === atts[k]) {
                                    atts[k] = false;
                                }
                                http_opts[k.match(/\[(.*)\]/)[1]] = atts[k];
                                delete atts[k];
                            }
                        }
                        atts.http_opts = http_opts;
                        return atts;
                    }
                    function shortcodeAttributes (atts) {
                        var str = '';
                        for (k in atts) {
                            var v;
                            switch (k) {
                                case 'http_opts':
                                    v = "'" + JSON.stringify(atts[k]) + "'";
                                    break;
                                case 'query':
                                    v = '"' + atts[k]
                                        .replace('<', encodeURIComponent('<'))
                                        .replace('>', encodeURIComponent('>')) + '"';
                                    break;
                                default:
                                    v = '"' + atts[k] + '"';
                                    break;
                            }
                            str += ' ' + k + '=' + v;
                        }
                        return str;
                    }
                    function resetDialogUi () {
                        jQuery('#qt_content_igsv_dialog input').each(function () {
                            jQuery(this).val('');
                        });
                    }
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
                IGSV_QT.d.dialog('open');
                IGSV_QT.d.find('input').get(0).focus();
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
<div id="qt_content_igsv_dialog" title="<?php esc_attr_e('Insert Spreadsheet Data as a Table or Chart', 'inline-gdocs-viewer');?>">
    <div id="qt_content_igsv_dialog_tabs_container">
        <ul>
            <li><a href="#igsv-datasource-tab"><?php esc_html_e('Datasource', 'inline-gdocs-viewer');?></a></li>
            <li><a href="#igsv-integrations-tab"><?php esc_html_e('Integrations', 'inline-gdocs-viewer');?></a></li>
            <li><a href="#igsv-extras-tab"><?php esc_html_e('Extras', 'inline-gdocs-viewer');?></a></li>
        </ul>
        <form>
            <fieldset id="igsv-datasource-tab">
                <legend><?php esc_html_e('Datasource options', 'inline-gdocs-viewer');?></legend>
                <p><?php esc_html_e('Provide a datasource. A datasource is usually a URL. If your data is in a Google Spreadsheet, paste the Web address of the spreadsheet. The only required attribute is the datasource "key." All other attributes are optional.', 'inline-gdocs-viewer');?> </p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-key"><?php esc_html_e('Key', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-key" name="key" type="text" placeholder="<?php esc_attr_e('paste your Google Spreadsheet or datasource URL here', 'inline-gdocs-viewer');?>" required="required" title="<?php esc_html_e('This attribute is required.', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Paste the web address of your Google Spreadsheet or CSV-formatted data file.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-query"><?php esc_html_e('Query', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-query" name="query" type="text" placeholder="<?php esc_attr_e('type a query', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Enter a query to pre-process your data or to select only the parts of the data you want to use in your post.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-title"><?php esc_html_e('Title', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-title" name="title" type="text" placeholder="<?php esc_attr_e('my data', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('A title usually appears as a tooltip when a user hovers their cursor over a table or is shown as the headline of a chart.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <fieldset id="igsv-integrations-tab">
                <legend><?php esc_html_e('Integrations', 'inline-gdocs-viewer');?></legend>
                <p><?php esc_html_e('If you use a custom theme or write custom functions, you can integrate your spreadsheet table or chart by specifying your integration values here. You can safely ignore these options if you do not have other code or are not using another plugin that needs them.', 'inline-gdocs-viewer');?> </p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-class"><?php esc_html_e('Class', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-class" name="class" type="text" placeholder="<?php esc_attr_e('custom-class other-custom-class', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Add a custom HTML class value to the containing element.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-style"><?php esc_html_e('Style', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-style" name="style" type="text" placeholder="<?php esc_attr_e('style', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Add inline CSS rules to your table or chart if you need to tweak its appearance.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-width"><?php esc_html_e('Width', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-width" name="width" type="text" placeholder="<?php esc_attr_e('width', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Add an explicit width to your chart if you need to tweak its appearance. (Tables ignore this. Use the "style" option instead.)', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-height"><?php esc_html_e('Height', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-height" name="height" type="text" placeholder="<?php esc_attr_e('height', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php esc_html_e('Add an explicit height to your chart if you need to tweak its appearance. (Tables ignore this. Use the "style" option instead.)', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-lang"><?php esc_html_e('Language', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-lang" name="lang" type="text" placeholder="<?php esc_attr_e('lang', 'inline-gdocs-viewer');?>" />
                                <p class="description"><?php print sprintf(
                                    esc_html__('If your datasource content is in a language other than %1$s, enter the %2$sISO-639%3$s language code for that language here.', 'inline-gdocs-viewer'),
                                    '<code>' . get_locale() . '</code>',
                                    '<a href="http://www.iso.org/iso/home/standards/language_codes.htm">', '</a>'
                                );?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <fieldset id="igsv-extras-tab">
                <legend><?php esc_html_e('Extras', 'inline-gdocs-viewer');?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-use-cache"><?php esc_html_e('Cache', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-use-cache" name="use_cache" type="checkbox" checked="checked" />
                                <span class="description"><?php esc_html_e('To improve performance, data from your datasource is cached for ten minutes. If you are making many changes quickly, or if your spreadsheet data is small but frequently updated, you may want to disable caching. Disabling the cache is not recommended for medium or large datasets.', 'inline-gdocs-viewer');?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-expire-in"><?php esc_html_e('Cache lifetime', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-expire-in" name="expire_in" type="number" min="0" placeholder="600" />
                                <p class="description"><?php esc_html_e('You can choose how frequently your site refreshes the data in your datasource by entering a cache lifetime, in seconds. Leaving this blank sets it to the default, which is 600 (ten minutes). The special value 0 turns off cache expiration, effectively caching the data from your datasource forever.');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-http-timeout"><?php esc_html_e('HTTP timeout', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-http-timeout" name="http_opts[timeout]" type="number" min="5" max="120" placeholder="5" />
                                <p class="description"><?php esc_html_e('You can choose how long to wait for your datasource to supply you with the data for your table before giving up. If you have a lot of data, or if your site has a very slow Internet connection, it may take longer than the default 5 second timeout to retrieve all of it. In those situations, raising this value may help prevent issues. Leave this blank to use the default.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-http-useragent"><?php esc_html_e('HTTP User-Agent', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-http-useragent" name="http_opts[user-agent]" type="text" placeholder="WordPress/<?php esc_attr_e(get_bloginfo('version'));?>; <?php esc_attr_e(get_bloginfo('url'));?>" />
                                <p class="description"><?php esc_html_e('You can choose what HTTP User-Agent header to use when retrieving data from a datasource. This might be helpful if your datasource blocks or restricts certain user agents from accessing the data. Leave this blank to use the default.', 'inline-gdocs-viewer');?></p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-http-sslverify"><?php esc_html_e('Verify SSL/TLS', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-http-sslverify" name="http_opts[sslverify]" type="checkbox" checked="checked" />
                                <span class="description"><?php esc_html_e('Certificate verification for HTTPS (SSL/TLS) connections is enabled by default. Turn this off only if you are certain secure connections are not desired.', 'inline-gdocs-viewer');?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="js-qt-igsv-linkify"><?php esc_html_e('Linkify', 'inline-gdocs-viewer');?></label>
                            </th>
                            <td>
                                <input id="js-qt-igsv-linkify" name="linkify" type="checkbox" checked="checked" />
                                <span class="description"><?php esc_html_e('Email addresses and URLs in your data are automatically turned into clickable links. If this causes problems, you can disable the automatic linking feature by unchecking this box.', 'inline-gdocs-viewer');?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </form>
    </div><!-- #qt_content_igsv_dialog_tabs_container -->
    <?php print $this->showDonationAppeal();?>
</div><!-- #qt_content_igsv_dialog -->
<?php
        }
    }

    private function registerContextualHelp () {
        $screen = get_current_screen();
        if (empty($screen->post_type)) { return; }
        $html = '<p>';
        $html .= sprintf(
            esc_html__('You can insert a Google Spreadsheet in this %1$s. To do so, type %2$s[gdoc key="%4$sYOUR_SPREADSHEET_URL%5$s"]%3$s wherever you would like the spreadsheet to appear. Remember to replace %4$sYOUR_SPREADSHEET_URL%5$s with the web address of your Google Spreadsheet.', 'inline-gdocs-viewer'),
            esc_html($screen->post_type),
            '<kbd>', '</kbd>',
            '<var>', '</var>'
        );
        $html .= '</p>';
        $html .= '<p>';
        $html .= esc_html__('Only Google Spreadsheets that have been shared using either the "Public on the web" or "anyone with the link" options will be visible on this page.', 'inline-gdocs-viewer');
        $html .= '</p>';
        $html .= '<p>' . sprintf(
            esc_html__('You can also transform your data into an interactive chart by using the %1$schart%2$s attribute. Supported chart types are Area, Bar, Bubble, Candlestick, Column, Combo, Histogram, Line, Pie, Scatter, and Stepped. For instance, to make a Pie chart, type %1$s[gdoc key="%3$sYOUR_SPREADSHEET_URL%4$s" chart="Pie"]%2$s. Customize your chart with your own choice of colors by supplying a space-separated list of color values with the %1$schart_colors%2$s attribute, like %1$schart_colors="red green"%2$s. Additional options depend on the chart you use.' ,'inline-gdocs-viewer'),
            '<kbd>', '</kbd>',
            '<var>', '</var>'
        ) . '</p>';
        $html .= '<p>' . sprintf(
            esc_html__('Refer to the %1$sshortcode attribute documentation%3$s for a complete list of shortcode attributes, and the %2$sGoogle Chart API documentation%3$s for more information about each option.' ,'inline-gdocs-viewer'),
            '<a href="https://wordpress.org/plugins/inline-google-spreadsheet-viewer/other_notes/" target="_blank">',
            '<a href="https://developers.google.com/chart/interactive/docs/gallery" target="_blank">', '</a>'
        ) . '</p>';
        $html .= '<p>';
        $html .= sprintf(
            esc_html__('If you are having trouble getting your Spreadsheet to show up on your website, you can %sget help from the plugin support forum%s. Consider searching the support forum to see if your question has already been answered before posting a new thread.', 'inline-gdocs-viewer'),
            '<a href="https://wordpress.org/support/plugin/inline-google-spreadsheet-viewer/" target="_blank">', '</a>'
        );
        $html .= '</p>';
        ob_start();
        $this->showDonationAppeal();
        $html .= ob_get_clean();
        $screen->add_help_tab(array(
            'id' => $this->shortcode . '-' . $screen->base . '-help',
            'title' => __('Inserting a Google Spreadsheet', 'inline-gdocs-viewer'),
            'content' => $html
        ));
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-style: italic; margin: 1em 3em;"><?php print sprintf(
esc_html__('Inline Google Spreadsheet Viewer is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'inline-gdocs-viewer'),
'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=Inline%20Google%20Spreadsheet%20Viewer%20WordPress%20Plugin&amp;item_number=inline%2dgdocs%2dviewer&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . esc_html__('making a donation', 'inline-gdocs-viewer') . '</a>',
'<a href="http://Cyberbusking.org/">' . esc_html__('houseless, jobless, nomadic developer', 'inline-gdocs-viewer') . '</a>'
);?></p>
</div>
<?php
    }

    public function validateSettings ($input) {
        $safe_input = array();
        foreach ($input as $k => $v) {
            switch ($k) {
                case 'allow_sql_db_queries':
                    $safe_input[$k] = intval($v);
                    break;
                case 'datatables_classes':
                    if (empty($v)) { $v = $this->dt_class; }
                    $safe_input[$k] = sanitize_text_field($v);
                    break;
                case 'datatables_defaults_object':
                    if (empty($v)) { $v = $this->dt_defaults; }
                    $safe_input[$k] = json_decode($v);
                    break;
            }
        }
        return $safe_input;
    }

    public function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'inline-gdocs-viewer'));
        }
        $options = get_option($this->prefix . 'settings');
        $datatables_defaults_json = (defined('JSON_PRETTY_PRINT'))
            ? json_encode($options['datatables_defaults_object'], JSON_PRETTY_PRINT)
            : json_encode($options['datatables_defaults_object']);
?>
<h2><?php esc_html_e('Inline Google Spreadsheet Viewer Settings', 'inline-gdocs-viewer');?></h2>
<form method="post" action="options.php">
<?php settings_fields($this->prefix . 'settings');?>
<fieldset><legend><?php esc_html_e('DataTables defaults', 'inline-gdocs-viewer');?></legend>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>datatables_classes"><?php esc_html_e('DataTables classes', 'inline-gdocs-viewer');?></label>
            </th>
            <td>
                <input class="regular-text code"
                    id="<?php esc_attr_e($this->prefix);?>datatables_classes"
                    name="<?php esc_attr_e($this->prefix);?>settings[datatables_classes]"
                    value="<?php esc_attr_e($options['datatables_classes'])?>" placeholder="<?php esc_attr_e('class-1 class-2', 'inline-gdocs-viewer')?>"
                />
                <p class="description">
                    <?php print sprintf(
                        esc_html__('A space-separated list of HTML %1$sclass%2$s values. %1$s<table>%2$s elements with these classes will automatically be enhanced with %3$sjQuery DataTables%4$s, unless the given table also has the special %1$sno-datatables%2$s class. Leave blank to use the plugin default.', 'inline-gdocs-viewer'),
                        '<code>', '</code>',
                        '<a href="https://datatables.net/">', '</a>'
                    );?>
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>datatables_defaults_object"><?php esc_html_e('DataTables defaults object', 'inline-gdocs-viewer');?></label>
            </th>
            <td>
                <textarea class="large-text code"
                    id="<?php esc_attr_e($this->prefix);?>datatables_defaults_object"
                    name="<?php esc_attr_e($this->prefix);?>settings[datatables_defaults_object]"
                    placeholder='{ "searching": false, "ordering": false }'
                    style="min-height: 200px;"
                ><?php if (!empty($options['datatables_defaults_object'])) { print stripslashes($datatables_defaults_json); }?></textarea>
                <p class="description"><?php print sprintf(
                    esc_html__('Define a DataTables defaults initialization object. This is useful if you wish to change the default DataTables enhancements for all affected tables on your site at once. All DataTables-enhanced tables will use the DataTables options configured here unless explicitly overriden in the shortcode, HTML, or JavaScript initialization for the given table, itself. To learn more, read the %1$sDataTables manual section on Setting defaults%2$s and refer to the %3$sdocumentation for shortcode attributes available via this plugin%2$s. Leave blank to use the plugin default.'),
                    '<a href="https://datatables.net/manual/options#Setting-defaults">', '</a>',
                    '<a href="https://wordpress.org/plugins/inline-google-spreadsheet-viewer/other_notes/">'
                );?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset><legend><?php esc_html_e('Advanced options', 'inline-gdocs-viewer');?></legend>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>allow_sql_db_queries"><?php esc_html_e('Allow SQL queries in shortcodes?', 'inline-gdocs-viewer');?></label>
            </th>
            <td>
                <input type="checkbox" <?php if (isset($options['allow_sql_db_queries'])) : print 'checked="checked"'; endif; ?> value="1" id="<?php esc_attr_e($this->prefix);?>allow_sql_db_queries" name="<?php esc_attr_e($this->prefix);?>settings[allow_sql_db_queries]" />
                <label for="<?php esc_attr_e($this->prefix);?>allow_sql_db_queries"><span class="description"><?php
        print sprintf(
            esc_html__('Enabling this option permits SQL queries against arbitrary MySQL databases to be inserted as part of a %1$s shortcode. This is useful but can also be easily abused, so it is disabled by default. Even once enabled, such queries will only work in posts whose author has been granted the %2$s capability. (Only Administrators have this capability by default.)', 'inline-gdocs-viewer'),
            $this->shortcode,
            "<code>{$this->prefix}query_sql_databases</code>"
        );
            ?></span><p class="description"><?php esc_html_e('User role(s) capable of using SQL queries:', 'inline-gdocs-viewer');?></p>
            <ul class="description">
            <?php foreach ($this->getSqlCapableRoles() as $k => $v) {
                print '<li>' . esc_html($v['name']) . '</li>';
            }?>
            </ul></label>
            </td>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
<?php
        $this->showDonationAppeal();
    }
}

$inline_gdoc_viewer = new InlineGoogleSpreadsheetViewerPlugin();
