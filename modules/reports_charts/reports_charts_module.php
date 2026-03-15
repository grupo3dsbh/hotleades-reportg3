<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Reports Charts Module
 *
 * Drop-in module for Perfex CRM that adds interactive chart visualizations
 * to the standard Invoices Report page.
 *
 * Integration steps (see README.md for full guide):
 *  1. Copy this folder to application/modules/reports_charts/
 *  2. Include the view in your reports view file (see below).
 *  3. Enqueue the CSS/JS assets in the same view or layout.
 */

class Reports_charts_module
{
    /** Return the absolute path to the module assets directory. */
    public static function assets_path(): string
    {
        return __DIR__ . '/assets';
    }

    /** Return the module web URL for assets (requires $CI to resolve base_url). */
    public static function assets_url(): string
    {
        return base_url('modules/reports_charts/assets');
    }

    /**
     * Render the charts view inline.
     * Call this from the invoices report view:
     *
     *   <?php Reports_charts_module::render(); ?>
     */
    public static function render(): void
    {
        $CI = &get_instance();
        $CI->load->view('reports_charts/charts_view');
    }
}
