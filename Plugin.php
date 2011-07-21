<?php
class Plugin
{
	private $pluginFields = array();

/** 
 * Class constructor 
 *  
 * @param string relative plugin path 
 */ 	
	public function __construct($plugin_path)
	{
		$this->pluginFields = $this->initPlugin($plugin_path);
	}

/** 
 * Plugin initialization
 * 
 * @param string relative plugin path 
 */ 	
	private function initPlugin($plugin_path)
	{
		$plugin = get_plugin_data(WP_PLUGIN_DIR.'/'.$plugin_path);
		$plugin['Dir'] = $this->getPluginDir($plugin_path);
		$plugin['RelativePath'] = $plugin_path;
		$plugin['BackupDir'] = str_replace(' ','_',$plugin['Name']);
		
		$plugin['MetaId'] = $this->getPluginMetaId($plugin_path); // get plugin metadata record id (record in wp_posts)
		return $plugin;
	}
	
/**
 * Class magic get
 * 
 * @param string plugin field name  
 */  	
	public function __get($field)
	{
		return $this->pluginFields[$field];
	}
	
/**
 * Class magic set
 * 
 * @param string plugin field name  
 */  	
	public function __set($field_key,$field_value)
	{
		return $this->pluginFields[$field_key] = $field_value;
	}
		
/**
 *	Return root directory of the plugin 
 *  	
 *  @param string relative plugin path   
 */  
	private function getPluginDir($plugin_path)
	{
		preg_match('/^(.*)\/.*\.[a-zA-Z]{3}$/',$plugin_path,$matches);
		return $matches[1];
	}
	
/**
 * Get plugin metadata record id if it exists
 * 
 * @param object Plugin object
 */    
	private function getPluginMetaId($plugin_relative_path)
	{
		global $wpdb;
		
		$q="SELECT id 
		FROM $wpdb->posts
		WHERE guid = '$plugin_relative_path' AND post_type = 'plugin'";
		$row = $wpdb->get_row($q);
		
		if($row->id > 0 && isset($row->id))
		{
			return $row->id;
		}
		else
		{
			return 0;
		}
	}
}
?>