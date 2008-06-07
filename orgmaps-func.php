<?php
/**
 * Contains functions to process AJAX requests
 */

// import necessary wp files
include_once('../../../wp-config.php');
include_once('../../../wp-includes/wp-db.php');
include_once('../../../wp-includes/formatting.php');

// first of all, check user level
global $userdata;
get_currentuserinfo();

if ($userdata->user_level < 8){
	// loser
	header ('HTTP/1.0 403 Forbidden');
	echo "You do not have sufficient access rights.";
	return NULL;
}

// check if action is set
if (isset($_POST['action'])){
	$action = $_POST['action'];
	// execute
	eval ('orgmaps_' . $action . '();');
}else {
	header ('HTTP/1.0 400 Bad Request');
	echo "Required parameters missing.";
	return NULL;
}

/**
 * Add a new category
 * Insert row in DB
 */
function orgmaps_add_cat (){

	// DB interface
	global $wpdb;
	
	$icon = $_POST['icon'] == '' ? ORGMAPS_DEF_ICON : $_POST['icon'];
	$shadow = $_POST['shadow'] == '' ? ORGMAPS_DEF_SHADOW : $_POST['shadow'];
	
	// insert new record (name, icon, shadow)
	$wpdb->query ("INSERT INTO " . $wpdb->prefix . "marker_cats (name, icon, shadow) VALUES ('" . attribute_escape($_POST['name']) . "', '" . attribute_escape($icon) . "', '" . attribute_escape($shadow) . "');");
	
	// get record key
	$id = $wpdb->get_results ("SELECT LAST_INSERT_ID() FROM " . $wpdb->prefix . "marker_cats");
	$ar = (array)$id[0];
	
	// ajax response, return category id in json encoded form
	header('X-JSON: ({id:' . $ar["LAST_INSERT_ID()"] . '})');
	
	echo "Category added.";

}

/**
 * Update current category
 * update row in DB
 */
function orgmaps_update_cat (){

	// DB interface
	global $wpdb;
	
	$icon = $_POST['edit_icon'] == '' ? ORGMAPS_DEF_ICON : $_POST['edit_icon'];
	$shadow = $_POST['edit_shadow'] == '' ? ORGMAPS_DEF_SHADOW : $_POST['edit_shadow'];
	
	// update name, icon, shadow
	$wpdb->query ("UPDATE " . $wpdb->prefix . "marker_cats SET name='" . attribute_escape($_POST['edit_name']) . "', icon='" . attribute_escape($icon) . "', shadow='" . attribute_escape($shadow) . "' WHERE id=" . $_POST['edit_id'] . ";");
	
	echo "Category updated";

}

/*
 * Delete categories
 * Remove corresponding rows from DB
 * NOTE: DB engine enforces referential integrity. All markers of the deleted categories are deleted.
 */
function orgmaps_delete_cats (){

	// DB interface
	global $wpdb;
	
	$cats = $_POST['cats_to_delete'];
	$sql = "DELETE FROM " . $wpdb->prefix . "marker_cats WHERE ";
	
	// loop through all categories
	foreach ($cats as $cat){
		$sql .= "id= " . attribute_escape($cat) . " OR ";
	}
	
	// close string nicely; no record should have id=-1
	$sql .= "id= -1;";
	
	$wpdb->query ($sql);
	
	echo "Categories deleted.";

}

/**
 * Add a new marker to the map
 * Insert row and store attributes in DB
 */
function orgmaps_add_marker (){

	// DB interface
	global $wpdb;
	
	// insert new record (name, lat, lon)
	$wpdb->query ("INSERT INTO " . $wpdb->prefix . "markers (name, lat, lon, cat) VALUES ('Placemark', " . attribute_escape($_POST['lat']) . ", " . attribute_escape($_POST['lon']) . ", " . attribute_escape($_POST['cat']) . ");");
	
	// get marker id
	$id = $wpdb->get_results ("SELECT LAST_INSERT_ID() FROM " . $wpdb->prefix . "markers");
	$ar = (array)$id[0];
	
	// ajax response, return marker id in json encoded form
	header('X-JSON: ({id:' . $ar["LAST_INSERT_ID()"] . '})');
	
	echo "Marker added.";

}


/**
 * Update marker data in DB
 */
function orgmaps_update_marker (){

	// DB interface
	global $wpdb;
	
	// update description, name, and catergory
	$wpdb->query ("UPDATE " . $wpdb->prefix . "markers SET description='" . $_POST['description'] . "', name='" . attribute_escape($_POST['name']) . "', cat='" . attribute_escape($_POST['cat']) . "' WHERE id=" . $_POST['id'] . ";");
	
	echo "Marker updated.";

}

/**
 * Update marker position
 */
function orgmaps_update_drag (){
	
	// DB interface
	global $wpdb;
	
	// update latitude, longitude
	$wpdb->query ("UPDATE " . $wpdb->prefix . "markers SET lat=" . attribute_escape($_POST['lat']) . ", lon=" . attribute_escape($_POST['lon']) . " WHERE id=" . attribute_escape($_POST['id']) . ";");
	
	echo "Marker position updated.";
	
}

/**
 * Delete marker
 * Remove row from DB
 */
function orgmaps_delete_marker (){

	// DB interface
	global $wpdb;
	
	$wpdb->query ("DELETE FROM " . $wpdb->prefix . "markers WHERE id=" . attribute_escape($_POST['id']) . ";");
	
	echo "Marker deleted.";
	
}
?>
