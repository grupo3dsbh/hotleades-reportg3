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
// Admin menu item
// Adicionado como item filho do menu Reports (slug padrão: nav_reports).
// Caso o slug seja diferente na instalação, também registramos como
// item de topo (position 61) para garantir visibilidade.
// --------------------------------------------------------------------

hooks()->add_action('admin_init', 'reports_charts_menu_items');

function reports_charts_menu_items()
{
    $CI = &get_instance();

    // Tenta adicionar como filho do menu Relatórios
    // Os slugs mais comuns nas versões do Perfex CRM são listados abaixo;
    // o método ignora silenciosamente se o pai não existir.
    foreach (['nav_reports', 'reports', 'nav-reports'] as $parent_slug) {
        $CI->app_menu->add_sidebar_children_item($parent_slug, [
            'slug'     => 'reports-charts',
            'name'     => 'Reports Charts',
            'href'     => admin_url('reports_charts'),
            'position' => 5,
            'icon'     => 'fa fa-bar-chart',
        ]);
    }

    // Fallback: item de topo após Reports (posição 61)
    $CI->app_menu->add_sidebar_menu_item('reports-charts-top', [
        'name'     => 'Reports Charts',
        'href'     => admin_url('reports_charts'),
        'position' => 61,
        'icon'     => 'fa fa-bar-chart',
    ]);
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
