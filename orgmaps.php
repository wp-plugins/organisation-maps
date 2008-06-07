<?php
/*
Plugin Name: Organisation Maps
Description: This plugin provides a mapping capability bulit on the Google Maps API. It allows the user to create a map of his organisation's campus on a page and mark out notable places on the map. Additional features include marker categorisation and filtering, info window editing etc.
Version: 0.2.1
Author: Lim Jiunn Haur
Author URI: http://broken-watch.info/
*/

/*  Copyright 2008  Lim Jiunn Haur  (email : fisherman.jiang@broken-watch.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Declare constants
 */
define ("ORGMAPS_DEF_WIDTH", 450);
define ("ORGMAPS_DEF_HEIGHT", 600);
define ("ORGMAPS_DEF_ZOOM", 16);
define ("ORGMAPS_DEF_TYPE", "normal");
define ("ORGMAPS_DEF_COL", 5);
define ("ORGMAPS_DEF_ICON", "http://www.google.com/intl/en_ALL/mapfiles/marker.png");
define ("ORGMAPS_DEF_SHADOW", "http://www.google.com/intl/en_ALL/mapfiles/shadow50.png");

/**
 * Hook for plugin initialization on page load
 */
function orgmaps_init ($options_page = FALSE){

	// if this is not the intended page, stop
	global $post;
	if ($post->ID != get_option ('orgmaps_page_id') && !$options_page) return TRUE;

	// db interface
	global $wpdb;

	// get parameters from db
	$api_key 	= get_option ('orgmaps_api_key');
	$map_lat	= get_option ('orgmaps_map_lat');
	$map_lon	= get_option ('orgmaps_map_lon');
	$width 		= get_option ('orgmaps_width');
	$height 	= get_option ('orgmaps_height');
	$zoom		= get_option ('orgmaps_zoom');
	$def_cat	= get_option ('orgmaps_def_cat');
	$def_type	= get_option ('orgmaps_def_type');
	$num_col	= get_option ('orgmaps_num_col');
	$site_url	= get_settings ('siteurl');
	
	// get markers from db
	$markers = $wpdb->get_results ("SELECT * FROM " . $wpdb->prefix . "marker_cats t1 INNER JOIN " . $wpdb->prefix . "markers t2 ON t1.id=t2.cat ORDER BY t2.cat");
	
	// get categories
	$cats = $wpdb->get_results ("SELECT * FROM " . $wpdb->prefix . "marker_cats");
	
	// if category specified does not exist, use the first in db
	$exists = FALSE;
	// loop till end of array or till a match is found
	for ($i = 0; $i < count($cats) && !$exists; $i++){
		$cat = $cats[$i];
		$exists = $def_cat == $cat->id;
	}
	
	// if no match found, use first in db
	if (!$exists){	
		$def_cat = $cats[0]->id;
	}
	
	?>
	
	<!-- Organisation Maps plugin begin -->
	
	<!-- Google Maps API -->
	<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $api_key ?>"></script>
	<!-- Prototype -->
	<script type="text/javascript" src="<?php echo $site_url . '/wp-content/plugins/orgmaps/scripts/prototype.js' ?>"></script>
	
	<!-- window.js -->
	<script type="text/javascript" src="<?php echo $site_url . '/wp-content/plugins/orgmaps/scripts/window.js' ?>"></script>
	<!-- orgmaps.js -->
	<script type="text/javascript" src="<?php echo $site_url . '/wp-content/plugins/orgmaps/scripts/orgmaps.js.php' ?>"></script>
	<!-- Transfer PHP variables to JS variables -->
	<script type="text/javascript">
	<!--
		Orgmaps.width 	= "<?php echo $width ?>";
		Orgmaps.height 	= "<?php echo $height ?>";
		Orgmaps.lat 	= "<?php echo $map_lat ?>";
		Orgmaps.lon 	= "<?php echo $map_lon ?>";
		Orgmaps.zoom 	= "<?php echo $zoom ?>";
		Orgmaps.url		= "<?php echo $site_url . '/wp-content/plugins/orgmaps/orgmaps-func.php'?>";
		Orgmaps.markers	=  <?php echo orgmaps_jencode ($markers) ?>;
		Orgmaps.cats 	=  <?php echo orgmaps_jencode ($cats) ?>;
		Orgmaps.defCat 	= "<?php echo $def_cat ?>";
		Orgmaps.defType	= "<?php echo $def_type ?>";
		Orgmaps.numCol	=  <?php echo $num_col ?>;
	//-->
	</script>
	
	<link rel="stylesheet" type="text/css" href="<?php echo $site_url ?>/wp-content/plugins/orgmaps/themes/default.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $site_url ?>/wp-content/plugins/orgmaps/themes/alphacube.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $site_url ?>/wp-content/plugins/orgmaps/style.css" />
	
	<!-- Organisation Maps plugin end -->
	
	<?php
}

/**
 * Hook for configuration form
 */
function orgmaps_admin_panel (){

	if (function_exists('add_options_page')) {
		// adds an option page that is printed by orgmaps_admin_panel_setup()
		add_options_page ("Organisation Maps", "Org Maps", 'manage_options', basename(__FILE__), 'orgmaps_admin_panel_setup');
	}
	
}

/**
 * Setup and display configuration form
 */
function orgmaps_admin_panel_setup (){

	?>
	
	<div class="wrap"><h2>Organisation Maps Setup</h2>
		<form method='post' action='options.php'>
			<?php wp_nonce_field('update-options'); ?>
			
			<!-- Begin Map Options -->			
			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Update Options »') ?>" />
			</p>
		
			<!-- API key -->
			<table cellpadding="2" cellspacing="5">
				<tbody>
					<tr valign="top">
						<th scope="row">Your Google Maps API key:</th>
						<td><input type="text" size="90" name="orgmaps_api_key" value="<?php echo get_option('orgmaps_api_key'); ?>" /></td>
					</tr>
				</tbody>
			</table>
			
			<!-- Location -->
			<h3>Location</h3>
			<table cellpadding="2" cellspacing="5">
				<tbody>
					<tr valign="top">
						<th scope="row" align="right">Latitude:</th>
						<td><input type="text" name="orgmaps_map_lat" value="<?php echo get_option('orgmaps_map_lat'); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row" align="right">Longitude:</th>
						<td><input type="text" name="orgmaps_map_lon" value="<?php echo get_option('orgmaps_map_lon'); ?>" /></td>
					</tr>
				</tbody>
			</table>
			
			<?php
				//read parameters from wp db			
				$width		= get_option ('orgmaps_width');
				$height 	= get_option ('orgmaps_height');
				$zoom		= get_option ('orgmaps_zoom');
				$def_cat 	= get_option ('orgmaps_def_cat');
				$def_type	= get_option ('orgmaps_def_type');
				$num_col 	= get_option ('orgmaps_num_col');
				
				// get first row from category table
				global $wpdb;
				
				// get all categories in DB
				$cats = $wpdb->get_results ("SELECT * FROM " . $wpdb->prefix . "marker_cats");
				$db_cat = $cats[0];

			?>
			
			<h3>Map properties</h3>
			<table cellpadding="2" cellspacing="5">
				<tbody>
					<tr valign="top">
						<th scope="row" align="right">Width:</th>
						<td><input type="text" name="orgmaps_width" value="<?php echo $width ?>" /></td>
					</tr>
					
					<tr valign="top">
						<th scope="row" align="right">Height:</th>
						<td><input type="text" name="orgmaps_height" value="<?php echo $height ?>" /></td>
					</tr>
					
					<tr valign="top">
						<th scope="row" align="right">Zoom level:</th>
						<td><input type="text" name="orgmaps_zoom" value="<?php echo $zoom ?>" /></td>
					</tr>
					
					<tr valign="top">
						<th scope="row" align="right">Default category:</th>
						<td>
							<select name="orgmaps_def_cat" style="width:175px">
								<?php
									foreach ($cats as $cat){
								?>
								<option value="<?php echo $cat->id ?>"<?php if ($def_cat==$cat->id) echo "selected='selected' " ?>><?php echo $cat->name ?></option>
								<?php
									}
								?>
							</select>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" align="right">Default map type:</th>
						<td>
							<select name="orgmaps_def_type" style="width:175px">
								<option value="normal" <?php if ($def_type=='normal') echo "selected='selected'" ?>>Normal</option>
								<option value="satellite" <?php if ($def_type=='satellite') echo "selected='selected'" ?>>Satellite</option>
								<option value="hybrid" <?php if ($def_type=='hybrid') echo "selected='selected'" ?>>Hybrid</option>
							</select>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row" align="right">No. of categories per row:</th>
						<td><input type="text" name="orgmaps_num_col" value="<?php echo $num_col ?>" /></td>
					</tr>
				</tbody>
			</table>
			
			<input type="hidden" name="action" value="update" />
			
			<input type="hidden" name="page_options" value="orgmaps_api_key, orgmaps_map_lat, orgmaps_map_lon, orgmaps_width, orgmaps_height, orgmaps_zoom, orgmaps_def_cat, orgmaps_def_type, orgmaps_num_col" />
			
			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Update Options »') ?>" />
			</p>
			<!-- End Map Options -->
			
		</form>
		
		<!-- Begin category manager -->
		<form id='orgmaps_delete_cat_form' action="<?php echo get_settings('siteurl') ?>/wp-content/plugins/orgmaps/orgmaps-func.php" onsubmit="javascript: return false;">
			<div style="clear:right">
			<h3>Categories</h3>
			<table cellspacing="1" cellpadding="2">
			<thead>
				<tr style="padding:5px">
					<th>&nbsp;</th>
					<th>Name</th>
					<th>Icon URL</th>
					<th>Icon</th>
					<th>Shadow URL</th>
					<th>Shadow</th>
				</tr>
			</thead>
			<tbody id='orgmaps_cat_tbody'>
			<?php
				
				// for every category,
				foreach ($cats as $cat){	
					// print one table row
			?>
				<tr id="<?php echo "orgmaps_cat_" . $cat->id ?>">
					<td><input type="checkbox" value="<?php echo $cat->id ?>" name="cats_to_delete[]" /></td>
					<td><?php echo $cat->name ?></td>
					<td><?php echo $cat->icon ?></td>
					<td><img src="<?php echo $cat->icon ?>" /></td>
					<td><?php echo $cat->shadow ?></td>
					<td><img src="<?php echo $cat->shadow ?>" /></td>
				</tr>
			<?php
				}
				
			?>
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td align="right"><input type="submit" value="Delete »" onclick="javascript: Orgmaps.deleteCats(); return false;"/></td>
				</tr>
			</tfoot>
			</table>
			<p><small>Warning: deleting a category <strong>removes all markers</strong> in that category.</small></p>
			</div>
		
		</form>
		<form id='orgmaps_cat_form' action="<?php echo get_settings('siteurl') ?>/wp-content/plugins/orgmaps/orgmaps-func.php" onsubmit="javascript: return false;">	
			<span id='orgmaps_new_cat'><h4>New category</h4></span>&nbsp;|&nbsp;<span id='orgmaps_edit_cat'><h4>Edit category</h4></span>
			<table id='orgmaps_new_cat_tbl' cellspacing="5" cellpadding="2">
				<tr valign="top">
					<th scope="row" align="right">Name:</th>
					<td><input type="text" name="name" /></td>
				</tr>
				<tr valign="top">
					<th scope="row" align="right">Icon:</th>
					<td><input type="text" name="icon" size="70" /></td>
				</tr>
				<tr valign="top">
					<th scope="row" align="right">Shadow:</th>
					<td><input type="text" name="shadow" size="70" /></td>
				</tr>
				<tr>
					<th></th>
					<td align="right"><input type="submit" value="Create »" onclick="javascript: Orgmaps.addCat(); return false;" /></td>
				</tr>
			</table>
			<table id="orgmaps_edit_cat_tbl" cellspacing="5" cellpadding="2">
				<tr valign="top">
					<th scope="row" align="right">Category:</th>
					<td>
						<select name='edit_id'>
							<?php
								foreach ($cats as $cat){
							?>
							<option value='<?php echo $cat->id ?>'><?php echo $cat->name ?></option>	
							<?php
								}
							?>					
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" align="right">Name:</th>
					<td><input type="text" name="edit_name" size="70" /></td>
				</tr>
				<tr valign="top">
					<th scope="row" align="right">Icon:</th>
					<td><input type="text" name="edit_icon" size="70" /></td>
				</tr>
				<tr valign="top">
					<th scope="row" align="right">Shadow:</th>
					<td><input type="text" name="edit_shadow" size="70" /></td>
				</tr>
				<tr>
					<th></th>
					<td align="right"><input type="submit" value="Update »" onclick="javascript: Orgmaps.updateCat(); return false;" /></td>
				</tr>
			</table>
		</form>
		<!-- End category manager -->
		
		<!-- Begin preview -->
		<h2>Preview</h2>
		<div id='orgmaps_left'>
			<div id='orgmaps_sidebar'></div>
		</div>
		<div id='orgmaps_right'>
			<div id='orgmaps'></div>
			<div id='orgmaps_legend'></div>
		</div>
		
		<?php orgmaps_init(TRUE); ?>
		<script type="text/javascript">
		<!--	
			Orgmaps.adminInit();
		//-->
		</script>
		<!-- End preview -->
		
		<!-- Hidden form used to get input for marker data -->
		<div id="orgmaps_marker_div">
		<form id="orgmaps_marker_form" style="display:none" action="<?php echo get_settings('siteurl') ?>/wp-content/plugins/orgmaps/orgmaps-func.php" onsubmit="return false;">
			<table cellspacing="5" cellpadding="2">
				<tbody>
					<tr valign="top">
						<th scope="row" align="right">Name:</th>
						<td><input id="orgmaps_input_name" type="text" name="name" value="Placemark" /></td>
					</tr>
					<tr valign="top">
						<th scope="row" align="right">Category:</th>
						<td>
							<select id="orgmaps_input_cat" name="cat">
								<?php
									foreach ($cats as $cat){
								?>
								<option value="<?php echo $cat->id ?>"><?php echo $cat->name ?></option>
								<?php
									}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" align="right">Description:</th>
						<td><textarea id="orgmaps_input_description" rows="10" cols="18" name="description"></textarea></td>
					</tr>
					<tr>
						<td></td>
						<td align="right"><input id="orgmaps_input_done" type="submit" value="Done" />
						<input id="orgmaps_input_delete" type="submit" value="Delete" /></td>
					</tr>
				</tbody>
			</table>
		</form>
		</div>
		
	</div>
	
	<?php
	
}
	

/**
 * Add configuration options
 */
function orgmaps_set_options (){
	
	// Google Maps API key
	add_option ('orgmaps_api_key');
	
	// location of center of the map
	add_option ('orgmaps_map_lat');
	add_option ('orgmaps_map_lon');
	
	// map dimensions
	add_option ('orgmaps_width', ORGMAPS_DEF_WIDTH);
	add_option ('orgmaps_height', ORGMAPS_DEF_HEIGHT);
	
	// map zoom level
	add_option ('orgmaps_zoom', ORGMAPS_DEF_ZOOM);
	
	// default category
	add_option ('orgmaps_def_cat', ORGMAPS_DEF_CAT);
	
	// default map type
	add_option ('orgmaps_def_type', ORGMAPS_DEF_TYPE);
	
	// no of columns to use in legend table
	add_option ('orgmaps_num_col', ORGMAPS_DEF_COL);
	
	// id of page containing the map
	add_option ('orgmaps_page_id');
	
}

/**
 * Remove configuration options
 */
function orgmaps_unset_options (){

	// remove everything
	delete_option ('orgmaps_api_key');
	delete_option ('orgmaps_map_lat');
	delete_option ('orgmaps_map_lon');
	delete_option ('orgmaps_width');
	delete_option ('orgmaps_height');
	delete_option ('orgmaps_zoom');
	delete_option ('orgmaps_def_cat');
	delete_option ('orgmaps_def_type');
	delete_option ('orgmaps_num_col');
	delete_option ('orgmaps_page_id');
	
}

/**
 * Install plugin
 */
function orgmaps_install (){
	
	// DB interface
	global $wpdb;
	
	$cat_tbl = $wpdb->prefix . "marker_cats";
	
	// check if table exists
	if($wpdb->get_var("show tables like '$cat_tbl'") != $cat_tbl) {
		
		// create category table: MARKER_CATS (id, name, icon, shadow)
		$sql = "CREATE TABLE " . $cat_tbl . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name tinytext NOT NULL COMMENT 'Category Name',
			icon VARCHAR(100) NOT NULL DEFAULT '" . ORGMAPS_DEF_ICON . "' COMMENT 'Marker Icon',
			shadow VARCHAR(100) NOT NULL DEFAULT '" . ORGMAPS_DEF_SHADOW . "' COMMENT 'Icon shadow'
		) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		// insert first default category
		$sql = "INSERT INTO " . $cat_tbl . " (name) VALUES ('Misc.')";
		$wpdb->query ($sql);
		
	}
	
	$marker_tbl = $wpdb->prefix . "markers";
	
	// check if table exists
	if($wpdb->get_var("show tables like '$marker_tbl'") != $marker_tbl) {
		
		// create marker table: MARKERS (id, name, description, lat, lon, cat)
		$sql = "CREATE TABLE " . $marker_tbl . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name tinytext NOT NULL,
			description text NULL,
			lat double NOT NULL COMMENT 'Latitude',
			lon double NOT NULL COMMENT 'Longitude',
			cat mediumint(9) NOT NULL DEFAULT 1 COMMENT 'Marker Category',
			FOREIGN KEY (cat) REFERENCES " . $cat_tbl . " (id) ON DELETE CASCADE
		) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
	
	// add configuration options
	orgmaps_set_options();
	
	// finally, create the page to contain the map
	$content = "<div id=\'orgmaps_left\'>
	<div id=\'orgmaps_sidebar\'></div>
</div>
<div id=\'orgmaps_right\'>
	<div id=\'orgmaps\'></div>
	<div id=\'orgmaps_legend\'></div>
</div>
<div id=\'orgmaps_drive_div\'>
	<form id=\'orgmaps_drive_form\' onsubmit=\'javascript: Orgmaps.getDirections(); return false;\'>
	<small>Start Address</small><br/>
	<input type=\'text\' name=\'drive_start\' id=\'orgmaps_drive_start\' /><input type=\'submit\' value=\'Go\' onclick=\'javascript: Orgmaps.getDirections(); return false;\'/>
	</form>
</div>";
	update_option ('orgmaps_page_id', wp_insert_post (array ("post_title" => "Map", "post_content" => $content, "post_status" => 'publish', "post_type" => 'page')));
	
}

/**
 * Uninstall plugin
 */
function orgmaps_uninstall (){

	// remove tables
	global $wpdb;
	$wpdb->query ("DROP TABLE IF EXISTS " . $wpdb->prefix . "markers, " . $wpdb->prefix . "marker_cats;");

	// delete page
	wp_delete_post (get_option ('orgmaps_page_id'));

	// remove configuration options
	orgmaps_unset_options();

}

/**
 * Encode JSON
 */
function orgmaps_jencode ($value){

	if (function_exists('json_encode')){
		// if PHP 5.2 and above
		return json_encode ($value);
	}else {
		// if not, load encoder script
		require_once 'encoder.php';
		return Zend_Json_Encoder::encode($value, false);
	
	}

}

// adding hooks
add_action ('admin_menu', 'orgmaps_admin_panel');
add_action ('wp_head', 'orgmaps_init');
register_activation_hook (__FILE__, 'orgmaps_install');
register_deactivation_hook (__FILE__, 'orgmaps_uninstall');
?>