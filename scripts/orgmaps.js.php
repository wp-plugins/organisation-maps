//<script type="text/javascript"> this does nothing, really. just to cheat dreamweaver.
<!--

var Orgmaps = {
	
	/*
	 * Map attributes from db
	 */
	width: '',				// map width
	height: '',				// map height
	lat: '',				// latitude of map center
	lon: '',				// longitude of map center
	zoom: '',				// zoom level
	defCat: '',				// default category (to set for each new marker and to show when map is first loaded)
	defType: '',			// default map type
	numCol: '',				// number of columns in legend
	
	/*
	 * JS objects
	 */
	map: '',				// the all impt GMap2 object
	directions: '',			// the GDirections object
	markers: '',			// data of markers		, loaded from db
	cats: '',				// categories of markers, loaded from db
	markerTbl: $H(),		// hashmap of GMarkers
	targetMarker: '',		// destination marker (to get driving directions to)
	driveForm: '',			// DOM object - form used to get starting point to get driving driections from
	markerForm: '',			// DOM object - form used to get marker data
	win: '',				// Window to show marker list
	driveWin: '',			// Window to show driving directions
	
	/*
	 * Flags and misc. data
	 */
	isAdmin: false,			// flag: determine if map is in options page
	url: '',				// url of orgmaps-func.php
	
	
	/*
	 * Initialize map
	 */
	init: function (){
		
		// get map DIV
		var div = $ ("orgmaps");
		
		// check if map is needed on this page
		if (div){
			
			// draw google map
			div.setStyle ({width: Orgmaps.width + "px", height: Orgmaps.height + "px"});
			this.map = new GMap2 (div);
			
			// position map
			this.map.setCenter (new GLatLng (this.lat, this.lon), Number(this.zoom));
			
			// add controls
			this.map.addControl (new GLargeMapControl ());
			this.map.addControl (new GMapTypeControl ());
			
			// set type
			this.map.setMapType (eval("G_" + this.defType.toUpperCase() + "_MAP"));
			
			if (this.isAdmin){
				// only admin uses marker form
				GEvent.bind (this.map, "click", this, this.mapClick);
				this.markerForm = $('orgmaps_marker_form');
				$("orgmaps_marker_div").removeChild (this.markerForm);
			}else {
				// only user uses drive form
				this.driveForm = $('orgmaps_drive_form');
				$('orgmaps_drive_div').removeChild (this.driveForm);
				
				// load driving directions
				this.directions = new GDirections(this.map, $(this.makeDirectionsWindow()));
				GEvent.addListener (this.directions, 'error', function (){$('orgmaps_text_pane').update ("Address Unknown.")});
			}
			
			// draw markers
			this.initMarkers ();
			
			// draw sidebar
			this.makeSidebarWindow ();
			this.makeSidebar ();
			this.win.setContent ('orgmaps_sidebar');
			
			// draw legend
			this.makeLegend ();
			
			// set unload
			document.getElementsByTagName('body')[0].writeAttribute ({unload: "javascript: GUnload()"});
			
		}
		
	},
	
	/*
	 * Build legend - the list of categories below the map
	 */
	makeLegend: function (){
	
		// get the div
		var legend = $('orgmaps_legend');
		
		// create table
		var table = new Element ('table', {cellpadding: 2});
		var tbody = new Element ('tbody');
		var tr = new Element ('tr');
		var i = 0;
			
		// for each cat
		this.cats.each (function (cat){
			
			// create checkbox, icon img, label
			var label = new Element ('label').update (cat.name);
			var img = new Element ('img', {src: cat.icon});			
			var input = new Element ('input', {type: 'checkbox', value: cat.id});
			
			/*if (Orgmaps.defCat == cat.id) {
				input.writeAttribute('checked');
			}*/
			
			// create table cell
			var td = new Element ('td');
			td.appendChild (input);
			td.appendChild (img);
			td.appendChild (label);
			
			i++;
			
			// if exceeded max no. of columns, create new row
			if (i > Orgmaps.numCol){
				table.appendChild (tr);
				tr = new Element ('tr');
				i = 1;
			}
			
			tr.appendChild (td);
			
		});
		
		tbody.appendChild (tr);
		table.appendChild (tbody);
		legend.appendChild (table);
		
		// inefficient but no choice... IE BUG
		legend.select ('input[type="checkbox"]').each (function (ele){
		
			if (ele.value==Orgmaps.defCat){
				ele.writeAttribute ('checked');
			}
			
			Event.observe (ele, "click", function(){Orgmaps.boxClick (ele, ele.value)});
		
		});
	
	},
	
	/*
	 * Initialize sidebar window
	 */
	makeSidebarWindow: function (){
	
		// initialize window
		this.win = new Window({className: "alphacube", title: "Markers", width: 300, height: 400, destroyOnClose: true, recenterAuto: false, maximizable: false, closable: false});
		
		// get viewport dimensions
		var viewD = document.viewport.getDimensions();
		var viewHt = viewD.height;
		
		// get window dimensions
		var winD = this.win.getSize();
		var winHt = winD.height;
		
		// get map coordinates
		var offsets = $('orgmaps').cumulativeOffset ();
		var x = offsets.left;
		var y = offsets.top;
		
		//get scroll offsets
		var scrollOffsets = $('orgmaps').cumulativeScrollOffset ();
		var sx = scrollOffsets.left;
		var sy = scrollOffsets.top;
		
		this.win.showCenter(false, y - sy, x + Number (this.width) + 50 - sx);
		this.win.isOpen = true;
		this.win.minimize();
		
		// add mousout listener
		Event.observe ($(this.win.getId()), 'mouseout', function (){
			if (Orgmaps.winMinTimeout) clearTimeout (Orgmaps.winMinTimeout);
			// if it is open, minimize it
			if (!Orgmaps.win.isMinimized()){
				Orgmaps.winMinTimeout = setTimeout ("Orgmaps.win.minimize();", 1000);
			}
		});
		
		// add mouseover listener
		Event.observe ($(this.win.getId()), 'mouseover', function (){
			if (Orgmaps.winMinTimeout) clearTimeout (Orgmaps.winMinTimeout);
			// if it is minimized, open it.
			if (Orgmaps.win.isMinimized()){
				Orgmaps.win.minimize();
			}
		});
	
	},
	
	/*
	 * Build/rebuild sidebar - the list of markers in the floating window
	 */
	makeSidebar: function (){
		
		// get sidebar, empty it
		var sidediv = $('orgmaps_sidebar').update("");
		
		// create table
		var table = new Element ('table', {cellpadding: '2', cellspacing: '5'});
		var tbody = new Element ('tbody');
		
		// for each marker
		this.markerTbl.each (function (marker){
			if (!marker[1].isHidden()){
				// link
				var a = new Element ('a', {href: "http://"}).update(marker[1].orgmapsOptions.name);
				a.onclick = function(){Orgmaps.sidebarClick (marker[0]); return false;};
				// icon
				var img = new Element ('img', {src: marker[1].orgmapsOptions.icon ? marker[1].orgmapsOptions.icon : "http://www.google.com/intl/en_ALL/mapfiles/marker.png"});
				var tr = new Element ('tr');
				var td = new Element ('td')
				td.appendChild (img);
				tr.appendChild (td);
				td = new Element ('td');
				td.appendChild (a);
				tr.appendChild (td);
				tbody.appendChild (tr);
			}
		});
		
		// assemble
		table.appendChild (tbody);
		sidediv.appendChild (table);
	
	},
	
	/*
	 * Handle checkbox clicks - checkboxes in the legend
	 */
	boxClick: function (box, cat){
		
		if (box.checked){
			this.showCat (cat);
		}else {
			this.hideCat (cat);
		}
		
		this.map.closeInfoWindow();
		this.makeSidebar();
		return true;
	
	},
	
	/*
	 * Show all markers in a certain category - filtering
	 */
	showCat: function (cat){
	
		// for each marker
		this.markerTbl.each (function (marker){
			//compare
			if (marker[1].orgmapsOptions.cat == cat){
				marker[1].show();
			}
			
		});
	
	},
	
	/*
	 * Hide all markers in a certain category - filtering
	 */
	hideCat: function (cat){
	
		// for each marker
		this.markerTbl.each (function (marker){
			//compare
			if (marker[1].orgmapsOptions.cat == cat){
				marker[1].hide();
			}
			
		});
	
	},
	
	/*
	 * Initialize map on options page
	 */
	adminInit: function (){
	
		this.isAdmin = true;
		
		Event.observe ($('orgmaps_edit_cat'), 'click', this.toggleCatForm.bindAsEventListener (this));
		Event.observe ($('orgmaps_new_cat'), 'click', this.toggleCatForm.bindAsEventListener (this));		
		Event.observe ($('orgmaps_cat_form')['edit_id'], 'change', this.updateCatForm.bindAsEventListener (this));
		
		$('orgmaps_edit_cat').setStyle ({color: '#666'});
		$('orgmaps_edit_cat_tbl').hide();
		this.updateCatForm();
		
	},
	
	/*
	 * Update fields in edit category form
	 */
	updateCatForm: function (){
	
		var edit_cat = $('orgmaps_cat_form');
		var id = $F(edit_cat['edit_id']);
		
		var cat = this.cats.find (function (cat){
			return cat.id == id;
		});
		
		edit_cat['edit_name'].value = cat.name;
		edit_cat['edit_icon'].value = cat.icon;
		edit_cat['edit_shadow'].value = cat.shadow;
	
	},
	
	/*
	 * Toggle category form
	 */
	toggleCatForm: function (){
	
		if ($('orgmaps_new_cat_tbl').visible()){
			// if current form is the 'new category' form
			$('orgmaps_new_cat').setStyle ({color: '#666'});
			$('orgmaps_edit_cat').setStyle ({color: 'inherit'});
		}else {
			// if current form is the 'edit category' form
			$('orgmaps_new_cat').setStyle ({color: 'inherit'});
			$('orgmaps_edit_cat').setStyle ({color: '#666'});
		}
		
		$('orgmaps_new_cat_tbl').toggle();
		$('orgmaps_edit_cat_tbl').toggle();
	
	},
	
	/*
	 * Handle mouse clicks, creates/views markers
	 */
	mapClick: function (overlay, point){

		// if user is logged in and he did not click on an existing overlay
		// NOTE: no point hacking here. Authentication is checked again at server-side
		if (this.isAdmin && overlay == null){
			
			this.createMarker (point);
			
		}
		
	},
	
	/*
	 * Returns DOM object of info window to be displayed when new marker is created
	 */
	editMarkerWindow: function (id){
	
		var marker = this.markerTbl.get(id);
		
		// get fields
		var batch = this.markerForm.select ('input[id="orgmaps_input_name"]', 'select[id="orgmaps_input_cat"]', 'textarea[id="orgmaps_input_description"]', 'input[id="orgmaps_input_done"]', 'input[id="orgmaps_input_delete"]');
		
		// update fields
		batch[0].value = marker.orgmapsOptions.name;
		
		batch[1].value = marker.orgmapsOptions.cat;
		
		batch[2].value = marker.orgmapsOptions.description;
		batch[2].update (marker.orgmapsOptions.description);
		batch[3].onclick = function(){Orgmaps.updateMarker (id); return false;};
		batch[4].onclick = function(){Orgmaps.deleteMarker (id); return false;};
		
		this.markerForm.show();
		
		// done!
		return this.markerForm;
	
	},
	
	/*
	 * Return HTML to be displayed when normal user clicks on marker
	 */
	infoMarkerWindow: function (id){
	
		// get marker description
		var marker = this.markerTbl.get(id);
		var html = "<strong>" + marker.orgmapsOptions.name + "</strong><br/>";
		
		// add description
		html += marker.orgmapsOptions.description == null ? '' : marker.orgmapsOptions.description;
		
		// add driving directions link
		html += "<div><a id='orgmaps_drive_link' href='http://' onclick='javascript: Orgmaps.openDirectionsForm("+id+"); return false;'>Get Directions</a></div>";
		html += "<div id='orgmaps_drive_wrapper'></div>";
		
		return html;
	
	},
	
	/*
	 * Open drive form at bottom of bubble
	 */
	openDirectionsForm: function (id){
	
		// set destination marker
		this.targetMarker = id;
	
		// get div and make it appear
		var ele = this.driveForm;
		$('orgmaps_drive_wrapper').appendChild (ele);
		
		// correct info window size
		var everything = this.map.getInfoWindow().getContentContainers()[0].childNodes[0];		// get everything currently displayed
		this.markerTbl.get(id).openInfoWindow(everything, {maxWidth: Orgmaps.width - 20});
		
		// hide the link
		$('orgmaps_drive_link').hide();
		
		// focus input field
		$('orgmaps_drive_start').focus();
		
	},
	
	/*
	 * Load driving directions
	 */
	getDirections: function (){
	
		$('orgmaps_text_pane').update ("");
	
		// get destination coords
		var dest = this.markerTbl.get (this.targetMarker).getLatLng();
		
		// get viewport dimensions
		var viewD = document.viewport.getDimensions();
		var viewHt = viewD.height;
		
		// get window dimensions
		var winD = this.driveWin.getSize();
		var winHt = winD.height;
		
		// get map coordinates
		var offsets = $('orgmaps').cumulativeOffset();
		var x = offsets.left;
		var y = offsets.top;
		
		//get scroll offsets
		var scrollOffsets = $('orgmaps').cumulativeScrollOffset ();
		var sx = scrollOffsets.left;
		var sy = scrollOffsets.top;
		
		// open window
		this.driveWin.showCenter(false, y + 100 - sy, x + Number (this.width) + 50 - sx);
		
		// contact google
		this.directions.load ($('orgmaps_drive_form').getInputs ('text', 'drive_start')[0].value + " to " + dest.lat() + ", " + dest.lng());
	
	},
	
	/*
	 * Build driving directions window
	 */
	makeDirectionsWindow: function (){
	
		this.driveWin = new Window({className: "alphacube", title: "Driving Directions", width: 300, height: 500, destroyOnClose: false, recenterAuto: false, maximizable: false});
		this.driveWin.setHTMLContent ("<div id='orgmaps_text_pane'></div>");
		return 'orgmaps_text_pane';
	
	},
	
	/*
	 * Handle sidebar clicks
	 */
	sidebarClick: function (id){
	
		if (this.isAdmin){
			this.markerTbl.get(id).openInfoWindow (this.editMarkerWindow (id), {maxWidth: Orgmaps.width - 20});
		}else {
			this.markerTbl.get(id).openInfoWindowHtml (this.infoMarkerWindow(id), {maxWidth: Orgmaps.width - 20});
		}
	
	},
	
	/*
	 * Draw markers on map using data loaded from db
	 */
	initMarkers: function (){
	
		this.markers.each(function (marker){
			// set marker coords
			var pt = new GLatLng (marker.lat, marker.lon);
			
			// set icon
			var icon = new GIcon (G_DEFAULT_ICON);
			icon.image = marker.icon;
			icon.shadow = marker.shadow;
			
			// set options
			var options = {icon: icon, draggable: Orgmaps.isAdmin};
			var orgmapsOptions = {name: marker.name, cat: marker.cat, icon: marker.icon, shadow: marker.shadow, description: marker.description};
			
			// create marker
			var gm = Orgmaps._createMarker (pt, marker.id, options, orgmapsOptions);
			
			if (marker.cat != Orgmaps.defCat){
				gm.hide();
			}
			
		});
			
	},
	
	/*
	 * Create marker (AJAX Request)
	 */
	createMarker: function (point){
		
		// send to db
		new Ajax.Request (this.url, {
			method: 'post',
			parameters: {action: "add_marker", lat: point.lat(), lon: point.lng(), cat: Orgmaps.defCat},
			onSuccess: function (transport, json){
			
				// get id
				var id = json.id;
				
				// get coords
				var pt = new GLatLng (point.lat(), point.lng());
				
				var orgmapsOptions = {name: 'Placemark', cat: Orgmaps.defCat, icon: '', shadow: '', description: ''};
		
				// add to map
				Orgmaps._createMarker (pt, id, {draggable: true}, orgmapsOptions);
				Orgmaps.makeSidebar();
				
				// display info window to get user input
				Orgmaps.markerTbl.get(id).openInfoWindow (Orgmaps.editMarkerWindow (id), {maxWidth: this.width -20});
			}
		});
	
	},
	
	/*
	 * Create marker (NOTE: private method)
	 * Add marker to map
	 */
	_createMarker: function (pt, id, options, orgmapsOptions){
		
		// create GMarker
		var marker = new GMarker (pt, options);
		// add to map
		this.map.addOverlay (marker);
		// assemble data
		marker.orgmapsOptions = orgmapsOptions;
		this.markerTbl.set(id, marker);
		
		if (this.isAdmin){
			// if is admin, show edit marker window
			GEvent.addListener (marker, "click", function (){
				marker.openInfoWindow (Orgmaps.editMarkerWindow (id), {maxWidth: this.width - 20});
			});
			// update marker position when dragged
			GEvent.addListener (marker, "dragend", function (){
				Orgmaps.updateDrag (id);
			});
			
		}else {
			// if not admin, show info window when clicked
			marker.bindInfoWindowHtml (this.infoMarkerWindow(id), {maxWidth: this.width - 20});
		
		}
		
		return marker;
	
	},
	
	/*
	 * Delete marker (AJAX request)
	 */
	deleteMarker: function (id){
	
		new Ajax.Request (this.url, {
			method: 'post', 
			parameters: {action: 'delete_marker', id: id},
			onSuccess: Orgmaps._deleteMarker(id)
		});
	
	},
	
	/*
	 * Hide the marker on the map (NOTE: private method)
	 */
	_deleteMarker: function (id){
	
		this.markerTbl.get(id).hide();
		this.markerTbl.unset(id);
		this.map.closeInfoWindow();
		this.makeSidebar();
	
	},
	
	/*
	 * Update marker position when dragged (AJAX request)
	 */
	updateDrag: function (id){
	
		var pt = this.markerTbl.get(id).getLatLng();
	
		new Ajax.Request (this.url, {
			method: 'post',
			parameters: {action: 'update_drag', id: id, lat: pt.lat(), lon: pt.lng()}
		});
	
	},
	
	/*
	 * Update marker when form is submitted  (AJAX request)
	 */
	updateMarker: function (id){
	
		$('orgmaps_marker_form').request ({
			method: 'post', 
			parameters: {action: 'update_marker', id: id},
			onSuccess: Orgmaps._updateMarker (id, $('orgmaps_marker_form').serialize(true))
		});
	
	},
	
	/*
	 * Update marker appearance on map (NOTE: private method)
	 */
	_updateMarker: function (id, formInputs){
		
		// identify marker
		var marker = this.markerTbl.get(id);
		
		// update data
		var thisCat = this.cats.find (function (cat){
			return cat.id == formInputs.cat;
		});
		formInputs.icon = thisCat.icon;
		formInputs.shadow = thisCat.shadow;
		marker.orgmapsOptions = formInputs;
		
		// set icon
		marker.T[0].src = formInputs.icon;
		marker.T[1].src = formInputs.shadow;
		
		// close window
		this.map.closeInfoWindow();
		
		// update sidebar
		this.makeSidebar();
		
	},
	
	/*
	 * Add new category (AJAX Request)
	 */
	addCat: function (){
	
		$('orgmaps_cat_form').request ({
			method: 'post', 
			parameters: {action: 'add_cat'}, 
			onSuccess: function (transport, json){
				var id = json.id;
				Orgmaps._addCat(id);
			}
		});
	
	},
	
	/*
	 * Add new category row to table (NOTE: private method)
	 */
	_addCat: function (id){
	
		// get form inputs
		var inputs = $('orgmaps_cat_form').serialize(true);
		
		// create row
		var tr = new Element ('tr', {id: 'orgmaps_cat_' + id});
		tr.appendChild (new Element ('td').update ('<input type="checkbox" value=' + id + ' name= cats_to_delete[]'));
		tr.appendChild (new Element ('td').update (inputs.name));
		tr.appendChild (new Element ('td').update (inputs.icon));
		tr.appendChild (new Element ('td').update ("<img src='" + inputs.icon + "' />"));
		tr.appendChild (new Element ('td').update (inputs.shadow));
		tr.appendChild (new Element ('td').update ("<img src='" + inputs.shadow + "' />"));
		
		// add to table
		$('orgmaps_cat_tbody').appendChild (tr);
	
	},
	
	/*
	 * Update category data
	 */
	updateCat: function (){
	
		$('orgmaps_cat_form').request ({
			method: 'post',
			parameters: {action: 'update_cat'},
			onSuccess: function (transport, json){
				Orgmaps._updateCat();
			}
		});
		
	},
	
	/*
	 * Update data on page (NOTE: private method)
	 */
	_updateCat: function (){
	
		// get form inputs
		var inputs = $('orgmaps_cat_form').serialize(true);
		
		// find row
		var row = $('orgmaps_cat_' + inputs.edit_id);
		var cells = row.immediateDescendants();
		
		cells[1].update (inputs.edit_name);
		cells[2].update (inputs.edit_icon);
		cells[3].update ("<img src='" + inputs.edit_icon + "' />");
		cells[4].update (inputs.edit_shadow);
		cells[5].update ("<img src='" + inputs.edit_shadow + "' />");
	
	},
	
	/*
	 * Remove checked categories (AJAX Request)
	 */
	deleteCats: function (){
		
		// delete them
		$('orgmaps_delete_cat_form').request({
			method: 'post',
			parameters: {action: 'delete_cats'},
			onSuccess: function (){
				Orgmaps._deleteCats ();
			}
		});
	
	},
	
	/*
	 * Remove category table rows
	 */
	_deleteCats: function (){
	
		// get all checkboxes
		$cats = $('orgmaps_delete_cat_form').select('input[type="checkbox"]');
		
		// find those that are checked
		$cats.each (function (cat){
			if (cat.checked == true){
				$('orgmaps_cat_tbody').removeChild ($('orgmaps_cat_'+cat.value));
			}
		});
	
	}
		
}


// add onload behaviour
if (GBrowserIsCompatible()){
	// good browser
	Event.observe (window, "load", Orgmaps.init.bindAsEventListener(Orgmaps));
}else {
	// bad browser
	alert("Sorry, the Google Maps API is not compatible with this browser");
}

//-->