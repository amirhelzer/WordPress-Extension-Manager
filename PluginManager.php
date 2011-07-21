<?php

class PluginManager
{
	private $plugin = null;
	
/**
 * Class constructor
 * 
 * @param string relative plugin path  
 */  	
	public function __construct($plugin_path)
	{
		$this->plugin = $this->getPluginInstance($plugin_path);
	}
	
/** 
 * Get plugin data
 * 
 * @param string relative plugin path 
 */  	
	private function getPluginInstance($plugin_path)
	{
		include_once('Plugin.php');
		return new Plugin($plugin_path);
	}
	
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	private function makeDirs()
	{
		// make directory for plugin revision
		if(!is_dir(EM_DIR_MAIN)) {
			mkdir(EM_DIR_MAIN);
		}
		
		if(!is_dir(EM_DIR_PLUGINS)) {
			mkdir(EM_DIR_PLUGINS);
		}
	
		if(!is_dir(EM_DIR_PLUGINS.'/'.$this->plugin->BackupDir)) {
			mkdir(EM_DIR_PLUGINS.'/'.$this->plugin->BackupDir);
		}
	}
	
/**
 * Make revision dir
 * 
 * @param ing revision number
 */    	
	private function makeRevisionDir($revision_number)
	{
		$dir = EM_DIR_PLUGINS.'/'.$this->plugin->BackupDir.'/'.$revision_number;
		
		if(!is_dir($dir)) {
			mkdir($dir);
		}
	}
	
	/**
	 * Return last revision of wp extension
	 * 
	 * @param string plugin name
	 */   
	public function getLastRevision($plugin_name)
	{
		
	}
	
	public function backupExtensionFiles($in_dir)
	{

	}
	
	private function backupDir($dir,$plugin_name,$revision_number) 
	{	
		$this->makeRevisionDir($revision_number);
		
		$backup_dir = str_replace('plugins','extension_updates/plugins/'.$plugin_name.'/'.$revision_number,$dir);
		
		// make backup dir
		if(!is_dir($backup_dir))
		{
			mkdir($backup_dir);
		}
					
		chdir($dir);
		if(!($dp = opendir($dir))) 
		{
			die("Could not open $dir.");
		}
		
		while($file = readdir($dp)) 
		{
			if(is_dir($file)) 
			{
				if($file != '.' && $file != '..') 
				{
					traverse_dir("$dir/$file",$plugin_name);
					chdir($dir);
				}
			}
			else 
			{
				// copy files to the backup directory
				copy($dir.'/'.$file,$backup_dir.'/'.$file);
			}
		}
		closedir($dp);
	}

/**
 * Create restore point
 */  	
	public function createRestorePoint()
	{
		// make extension revision directories
		$this->makeDirs();

		// add metadata ------------------------------------		
		if($this->plugin->MetaId == 0) // if plugin meta record is not exists
		{
			$this->plugin->MetaId = $this->insertPluginMeta($this->plugin); // insert plugin records to the wp_posts and wp_postmeta tables
		}
				
		$head_revision = $this->incrementHeadRevision($this->plugin->MetaId); 		// update head revision of plugin
		$this->insertRevisionMeta($this->plugin,$head_revision); // insert plugin revision meta 
		
		// backup extension files --------------------------		
		$this->backupDir(WP_PLUGIN_DIR.'/'.$this->plugin->Dir,$this->plugin->BackupDir,$head_revision);
	}
	
/**
 * Return format array of post fields
 */ 	
	private function getPostFieldFormatArray()
	{
		$field_formats = array(
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%d',
		'%s',
		'%d',
		'%s',
		'%s',
		'%d'
		);
		return $field_formats;
	}
	
/** 
 * Insert revision record in DB
 * 
 * @param object Plugin class instance
 * @param int head revision of plugin 
 */  	
	private function insertRevisionMeta(Plugin $plugin,$head_revision)
	{
		global $wpdb;
		
		// set field values for insert query
		$current_user = wp_get_current_user();
		
		$post_author = $current_user->ID;
		$post_date = date('Y-m-d H:i:s');
		$post_date_gmt = '';
		$post_content = '';
		$post_title = 'Plugin '.$plugin->Name.' revision #'.$head_revision;
		$post_excerpt = '';
		$post_status = ''; // posts have statuses: publish, inherit, auto-draft
		$comment_status = '';
		$ping_status = '';
		$post_password = '';
		$post_name = $plugin->MetaId.'-plugin-revision-'.$head_revision; // posts have names like: '5-revision-2', '5-autosave'
		$to_ping = '';
		$pinged = '';
		$post_modified = '';
		$post_modified_gmt = '';
		$post_content_filtered = '';
		$post_parent = $plugin->MetaId;
		$guid = $plugin->RelativePath; // posts have guid like 'http://example.com/?page_id=2' OR 'http://example.com/?p=5'
		$menu_order = 0;
		$post_type = 'plugin-revision'; // for themes post_type will be set to 'theme-revision'
		$post_mime_type = ''; 
		$comment_count = 0;
		
		// insert revision
		$fields = array(
			'post_author' => $post_author,
			'post_date' => $post_date,
			'post_date_gmt' => $post_date_gmt,
			'post_content' => $post_content,
			'post_title' => $post_title,
			'post_excerpt' => $post_excerpt,
			'post_status' => $post_status,
			'comment_status' => $comment_status,
			'ping_status' => $ping_status,
			'post_password' => $post_password,
			'post_name' => $post_name,
			'to_ping' => $to_ping,
			'pinged' => $pinged,
			'post_modified' => $post_modified,
			'post_modified_gmt' => $post_modified_gmt,
			'post_content_filtered' => $post_content_filtered,
			'post_parent' => $post_parent,
			'guid' => $guid,
			'menu_order' => $menu_order,
			'post_type' => $post_type,
			'post_mime_type' => $post_mime_type,
			'comment_count' => $comment_count
		);
		
	
		$res = $wpdb->insert('wp_posts',$fields,$this->getPostFieldFormatArray());
		
		if($res)
		{			
			return $wpdb->insert_id;
		}
		else
		{
			return false;
		}
	}
	
/**
 * Inserts plugin record to the wp_posts
 *
 * @param object Plugin object 
 */ 	
	private function insertPluginMeta(Plugin $plugin)
	{
		global $wpdb;
		
		// set field values for insert query
		$current_user = wp_get_current_user();
		
		$post_author = $current_user->ID;
		$post_date = date('Y-m-d H:i:s');
		$post_date_gmt = '';
		$post_content = '';
		$post_title = 'Plugin '.$plugin->Name;
		$post_excerpt = '';
		$post_status = ''; // posts have statuses: publish, inherit, auto-draft
		$comment_status = '';
		$ping_status = '';
		$post_password = '';
		$post_name = ''; // posts have names like: '5-revision-2', '5-autosave'
		$to_ping = '';
		$pinged = '';
		$post_modified = '';
		$post_modified_gmt = '';
		$post_content_filtered = '';
		$post_parent = 0;
		$guid = $plugin->RelativePath; // posts have guid like 'http://example.com/?page_id=2' OR 'http://example.com/?p=5'
		$menu_order = 0;
		$post_type = 'plugin'; // for themes post_type will be set to 'theme'
		$post_mime_type = ''; 
		$comment_count = 0;
		
		// insert revision
		$fields = array(
			'post_author' => $post_author,
			'post_date' => $post_date,
			'post_date_gmt' => $post_date_gmt,
			'post_content' => $post_content,
			'post_title' => $post_title,
			'post_excerpt' => $post_excerpt,
			'post_status' => $post_status,
			'comment_status' => $comment_status,
			'ping_status' => $ping_status,
			'post_password' => $post_password,
			'post_name' => $post_name,
			'to_ping' => $to_ping,
			'pinged' => $pinged,
			'post_modified' => $post_modified,
			'post_modified_gmt' => $post_modified_gmt,
			'post_content_filtered' => $post_content_filtered,
			'post_parent' => $post_parent,
			'guid' => $guid,
			'menu_order' => $menu_order,
			'post_type' => $post_type,
			'post_mime_type' => $post_mime_type,
			'comment_count' => $comment_count
		);
		
		$res = $wpdb->insert('wp_posts',$fields,$this->getPostFieldFormatArray());
		
		if($res)
		{
			$plugin_id = $wpdb->insert_id;
			$this->insertPluginAdditionalMeta($plugin_id);
			
			return $plugin_id;
		}
		else
		{
			return false;
		}
	}

/**
 * Inser plugin records to the postmeta
 * 
 * @param int plugin metadata record id
 */     
	private function insertPluginAdditionalMeta($plugin_id)
	{
		global $wpdb;
		
		$fields = array(
		'post_id' => $plugin_id, 
		'meta_key' => 'head_revision',
		'meta_value' => 0
		);
		
		return $wpdb->insert('wp_postmeta', $fields, array('%d','%s','%d'));
	}
	
/**
 * Update head revision value
 * 
 * @param int plugin metadata record id
 * @param int head revision number
 */
	private function updateHeadRevision($plugin_id,$head_revision)
	{
		global $wpdb;
		
		return $wpdb->update('wp_postmeta', array('meta_value' => $head_revision), array('post_id' => $plugin_id, 'meta_key' => 'head_revision'), array('%d'), array('%d','%s'));
	}
	
/**
 * Increment head revision of plugin
 * 
 * @param int plugin metadata record id
 */    	
	private function incrementHeadRevision($plugin_id)
	{
		global $wpdb;
		
		$q="SELECT *
		FROM wp_postmeta
		WHERE post_id = $plugin_id AND meta_key = 'head_revision'";
		
		$row = $wpdb->get_row($q);		
		
		$new_head_revision = $row->meta_value + 1;
		$this->updateHeadRevision($plugin_id,$new_head_revision);
		
		return $new_head_revision;
	}
}



?>