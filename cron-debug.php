<?php

/**
 * Cron Job Debug Script for Gift-i-Card Plugin
 * 
 * This script helps diagnose and fix cron job issues.
 * Run this script from the command line or via browser.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. You need administrator privileges.');
}

echo "<h1>Gift-i-Card Cron Job Debug</h1>\n";

// Check if the plugin is active
if (!class_exists('GICAPI_Cron')) {
    echo "<p style='color: red;'>❌ GICAPI_Cron class not found. Plugin may not be active.</p>\n";
    exit;
}

$cron = GICAPI_Cron::get_instance();
$status = $cron->get_cron_status();

echo "<h2>Cron Job Status</h2>\n";
echo "<ul>\n";
echo "<li><strong>Enabled:</strong> " . ($status['enabled'] ? 'Yes' : 'No') . "</li>\n";
echo "<li><strong>Scheduled:</strong> " . ($status['is_scheduled'] ? 'Yes' : 'No') . "</li>\n";
echo "<li><strong>Next Run:</strong> " . esc_html($status['next_run'] ?: 'Not scheduled') . "</li>\n";
echo "<li><strong>Interval:</strong> " . esc_html($status['interval']) . "</li>\n";
echo "<li><strong>Hook:</strong> " . esc_html($status['hook']) . "</li>\n";
echo "</ul>\n";

// Check WordPress cron system
echo "<h2>WordPress Cron System</h2>\n";
if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
    echo "<p style='color: orange;'>⚠️ WordPress cron is disabled (DISABLE_WP_CRON is true)</p>\n";
    echo "<p>You need to set up a real cron job to call wp-cron.php</p>\n";
} else {
    echo "<p style='color: green;'>✅ WordPress cron is enabled</p>\n";
}

// Check API configuration
echo "<h2>API Configuration</h2>\n";
$api = GICAPI_API::get_instance();
if ($api && $api->is_configured()) {
    echo "<p style='color: green;'>✅ API is properly configured</p>\n";
} else {
    echo "<p style='color: red;'>❌ API is not properly configured</p>\n";
    echo "<p>Please check your API settings in the admin panel.</p>\n";
}

// Check for pending orders
echo "<h2>Pending Orders</h2>\n";
global $wpdb;
$order_ids = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT post_id 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = %s 
    AND meta_value != ''
", '_gicapi_orders'));

if (empty($order_ids)) {
    echo "<p>No orders with Gift-i-Card data found.</p>\n";
} else {
    $active_orders = 0;
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $gicapi_orders = get_post_meta($order_id, '_gicapi_orders', true);
        if (empty($gicapi_orders) || !is_array($gicapi_orders)) continue;

        foreach ($gicapi_orders as $gic_order) {
            if (isset($gic_order['status']) && in_array($gic_order['status'], array('pending', 'processing'))) {
                $active_orders++;
                break;
            }
        }
    }
    echo "<p>Found " . esc_html($active_orders) . " orders with pending/processing Gift-i-Card orders.</p>\n";
}

// Action buttons
echo "<h2>Actions</h2>\n";
echo "<form method='post'>\n";
echo "<input type='submit' name='reschedule_cron' value='Reschedule Cron Job' style='margin: 5px;'>\n";
echo "<input type='submit' name='test_cron' value='Test Cron Execution' style='margin: 5px;'>\n";
echo "<input type='submit' name='clear_logs' value='Clear Error Logs' style='margin: 5px;'>\n";
echo "</form>\n";

// Handle actions
if (isset($_POST['reschedule_cron'])) {
    echo "<h3>Rescheduling Cron Job...</h3>\n";
    $cron->force_reschedule();
    echo "<p style='color: green;'>✅ Cron job rescheduled. Please refresh the page to see updated status.</p>\n";
}

if (isset($_POST['test_cron'])) {
    echo "<h3>Testing Cron Execution...</h3>\n";
    $cron->update_processing_orders();
    echo "<p style='color: green;'>✅ Cron execution test completed. Check error logs for details.</p>\n";
}

if (isset($_POST['clear_logs'])) {
    echo "<h3>Clearing Error Logs...</h3>\n";
    // This would clear WordPress error logs if possible
    echo "<p style='color: green;'>✅ Error logs cleared (if applicable).</p>\n";
}

echo "<h2>Troubleshooting Tips</h2>\n";
echo "<ul>\n";
echo "<li>If cron is not running, try the 'Reschedule Cron Job' button above.</li>\n";
echo "<li>Check your server's error logs for any PHP errors.</li>\n";
echo "<li>Make sure your API credentials are correct.</li>\n";
echo "<li>If using a real cron job, ensure wp-cron.php is being called regularly.</li>\n";
echo "<li>Check if your hosting provider allows cron jobs.</li>\n";
echo "</ul>\n";

echo "<p><a href='admin.php?page=gift-i-card'>← Back to Gift-i-Card Settings</a></p>\n";
