=== Plugin Name ===
Contributors: codex.is.poetry
Tags: Google, maps, organization, map
Requires at least: 2.3.1
Tested up to: 2.3.1
Stable tag: 0.2

This plugin provides a mapping capability built on the Google Maps API.

== Description ==

There are several plugins that can embed Google Maps into Wordpress pages. However, most, if not all, of them require the user to specify the markup in a separate KML file. I found that troublesome, and hence created a plugin that allows the administrator to create a marker simply by clicking on a spot on the map, AJAX-style. The markup data is then stored in a database and is retrieved when the page is accessed.

The administrator can also specify the text (or HTML) to be displayed in the info window when the user clicks on the marker. Furthermore, the plugin allows him/her to categorize the markers and set a custom icon for each marker category.

The map is displayed on its own page together with its legend and a marker list. The legend is a list of the marker categories that the administrator has created, with each element being an icon-name pair. The marker list is a list of all the markers that are currently displayed on the map. It is contained in a floating window that extends on mouse over and rolls up on mouse out. Clicking on an element in the marker list centralizes the corresponding marker and opens its info window.

Additional features include marker filtering and getting driving directions.

== Installation ==

1. Upload the `orgmaps` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin through the 'Options > Org Maps' menu in WordPress
1. Modify CSS styles in `/wp-content/plugins/orgmaps/style.css`
1. View your map at the new page entitled 'Map'

== Frequently Asked Questions ==

= How do I modify the interface? =

You can:

1. Modify `orgmaps/style.css`, or
1. Modify the HTML markup in the page entitled 'Map'.

= How do I modify the window theme? =

Not at the moment...

== Screenshots ==

1. The map on the published page.
2. The map, with the marker window expanded.
