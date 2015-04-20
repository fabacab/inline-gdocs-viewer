<?php
/**
 * Inline Google Spreadsheet Viewer uninstaller
 *
 * @package plugin
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

delete_option('gdoc_settings');

// Delete caches.
global $wpdb;
$wpdb->query($wpdb->prepare(
    "
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '%s'
    ",
    $wpdb->esc_like('_transient_gdoc') . '%'
));
$wpdb->query($wpdb->prepare(
    "
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '%s'
    ",
    $wpdb->esc_like('_transient_timeout_gdoc') . '%'
));
