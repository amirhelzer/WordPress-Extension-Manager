<?php
/*
	Plugin Name: Extension Manager	 
	Plugin URI: -
	Description: Plugin for tracking extension upgrades and creating of restoring points
	Version: 0.1
	Author: -	 
	Author URI: -
*/

// define constants
define(EM_DIR_MAIN,WP_CONTENT_DIR.'/extension_updates');
define(EM_DIR_PLUGINS,WP_CONTENT_DIR.'/extension_updates/plugins');


include_once('PluginManager.php');

add_action('admin_menu', 'tp_add_tools_menu');
//add_action("pre_current_active_plugins",'empty_update_msg');
//add_action("in_plugin_update_message-txt-as-post/txtaspost.php",'test');

function empty_update_msg()
{
/*	echo "<script language=\"javascript\">
	jQuery(document).ready(function()
	{
		jQuery('.update-message a').each(function()
		{
			old_href = this.href;
			this.href = old_href+'str'; // this is for a test only
		});
		
	});
	</script>";
	*/
}

function test()
{
	echo 'test';
}

/**
 * Main function. Adding item to the menu
 */  
function tp_add_tools_menu() 
{
	add_submenu_page('tools.php', 'WP Extension Manager', 'WP Extension Manager', 10, basename(__FILE__), 'show_index_page');
	add_action('activate_plugin','em_activate_plugin');
}

 
function em_activate_plugin( $plugin, $redirect = '', $network_wide = false ) 
{
	create_restore_point($plugin);
}

function show_index_page()
{
	echo '<h1>WP Extension Manager</h1>';
}


function create_restore_point($plugin) 
{		
	$pm = new PluginManager($plugin);
	$pm->createRestorePoint();
	
	$plugin = $pm->getPlugin();
	
	// log plugin activation
	log_plugin_activation($plugin->Name);
}


function log_plugin_activation($log_message)
{
	// log plugin activation
	$fh = fopen(WP_CONTENT_DIR .'/plugins/wp_extension_manager/plugin_activation.log','a+');
	fputs($fh,date('Y-m-d @ H:i:s').'		'.$log_message."\n");
	fclose($fh);
}
?>