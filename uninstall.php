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

// Delete RBAC settings.
global $wp_roles;
$delete_caps = array(
    'gdoc_query_sql_databases'
);
foreach ($delete_caps as $cap) {
    foreach (array_keys($wp_roles->roles) as $role) {
        $wp_roles->remove_cap($role, $cap);
    }
}
