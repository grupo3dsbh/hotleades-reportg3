<?php

/**
 * Ensures that the module init file can't be accessed directly.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Reports Charts
Description: Adiciona visualização em gráficos (barras, pizza, gantt) ao relatório de faturas, com cards de resumo, totais do período e modal de detalhes por período/status.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo 3
*/

// --------------------------------------------------------------------
// Language
// --------------------------------------------------------------------

register_language_files('reports_charts', ['reports_charts']);

// --------------------------------------------------------------------
// Admin menu item (position 61 = logo após Reports padrão)
// --------------------------------------------------------------------

hooks()->add_action('admin_init', 'reports_charts_menu_items');

function reports_charts_menu_items()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_children_item('nav_reports', [
        'slug'     => 'reports-charts',
        'name'     => _l('reports_charts_menu'),
        'href'     => admin_url('reports_charts'),
        'position' => 5,
        'icon'     => 'fa fa-bar-chart',
    ]);
}

// --------------------------------------------------------------------
// Enqueue assets only on the charts page
// --------------------------------------------------------------------

hooks()->add_action('admin_init', 'reports_charts_enqueue_assets');

function reports_charts_enqueue_assets()
{
    $CI = &get_instance();

    if ($CI->uri->segment(2) !== 'reports_charts') {
        return;
    }

    // Chart.js via CDN
    add_javascript_to_page('https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js');

    // Module CSS
    add_css_to_page(module_dir_url('reports_charts', 'assets/css/reports_charts.css'));

    // Module JS (loaded after Chart.js)
    add_javascript_to_page(module_dir_url('reports_charts', 'assets/js/reports_charts.js'));
}

// --------------------------------------------------------------------
// Activation / Deactivation / Uninstall
// --------------------------------------------------------------------

register_activation_hook('reports_charts', 'reports_charts_activate');

function reports_charts_activate()
{
    // Nothing to install — module is purely front-end.
}

register_deactivation_hook('reports_charts', 'reports_charts_deactivate');

function reports_charts_deactivate()
{
    // Nothing to clean up on deactivation.
}

register_uninstall_hook('reports_charts', 'reports_charts_uninstall');

function reports_charts_uninstall()
{
    // Nothing to remove on uninstall.
}
