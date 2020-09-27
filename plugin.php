<?php

/*
 * Plugin Name: Bloom The Newsletter Plugin Integration
 * Plugin URI: 
 * Version: 1.0
 * Description: This plugin integrates "The Newsletter Plugin" in Bloom, Divi and Extra from Elegantthemes.
 * Author: Benedict Adams
 * Author URI: https://github.com/benada002
 * License: GPLv2 or later
 * 
 * 
 * Copyright © 2019  Benedict Adams
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined('ABSPATH') or die();


/*
 * Creates email provider account on activation.
 */

function btnp_activate()
{
    if (!defined('ET_CORE') || !is_plugin_active('newsletter/plugin.php')) {
        include_once('TheNewsletterPluginWrapper.php');

        $btnp = new BTNP_The_Newsletter_Plugin_Bloom('ET_Core', 'The Newsletter Plugin');
        $btnp->fetch_subscriber_lists();
    }
}

register_activation_hook(__FILE__, 'btnp_activate');


/*
 * Checks If The Newletter Plugin and Bloom or Divi or Extra is activated. And return error notice if not.
 */

function btnp_error()
{
    if (!defined('ET_CORE') || !class_exists('Newsletter')) :
        $err_msg = '';
        if (!defined('ET_CORE')) $err_msg = ' Bloom, Divi, Extra or the Divi Builder';
        if (!is_plugin_active('newsletter/plugin.php')) $err_msg = ' "The Newsletter Plugin"';
?>
        <div class="notice notice-error">
            <p>
                You need to install/activate<?php echo esc_html($err_msg); ?>! Otherwise is this plugin worthless.
            </p>
        </div>
<?php
    endif;
}

add_action('admin_notices', 'btnp_error');


/*
 * Bring Bloom to show it in the Provider selection.
 */

function btnp_include($third_party)
{
    require_once('TheNewsletterPluginWrapper.php');
    $third_party["thenewsletterplugin"] = new BTNP_The_Newsletter_Plugin_Bloom('builder', 'THE NEWSLETTER PLUGIN');

    return $third_party;
}

add_filter('et_core_get_third_party_components', 'btnp_include', 10);


function btnp_fetch_list()
{
    et_core_api_email_fetch_lists('thenewsletterplugin', 'THE NEWSLETTER PLUGIN');
}

add_action('admin_init', 'btnp_fetch_list');
