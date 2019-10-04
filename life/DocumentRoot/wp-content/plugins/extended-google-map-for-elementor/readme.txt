=== Extended Google Map for Elementor ===
Contributors: internetcss, kenng87
Tags: elementor google map widget, elementor addons, elementor plugins, elementor maps widget, google map, elementor google map
Donate link: https://internetcss.com/
Requires at least: 4.5
Tested up to: 5.2.3
Requires PHP: 5.6
Stable tag: 1.2.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

An Extended of Elementor Google Map Widget - Easily add multiple address pins onto the same map with support for different map types (Road Map/Satellite/Hybrid/Terrain) and custom map style. Freely edit info window content of your pins with the standard Elementor text editor. And many more custom map options.

== Description ==

An Extended of Elementor Google Map Widget - Easily add multiple address pins onto the same map with support for different map types (Road Map/Satellite/Hybrid/Terrain) and custom map style. Freely edit info window content of your pins with the standard Elementor text editor. And many more custom map options. Easily find address latitude and longitude right inside elementor.

- Supports using your own Google Map API key
- Easily find address latitude and longitude right inside Elementor
- 10 Color Marker Pin Icons to chooose from (Default, Red, Blue, Yellow, Purple, Green, Orange, Grey, White and Black)
- 4 different map types (Road Map/Satellite/Hybrid/Terrain)
- Custom map style (support Google Map Styling Wizard and Snazzy Maps)
- Choose gesture handling types (Auto/Cooperative/Greedy/None)
- Enable/disable zoom control
- Edit position of zoom control
- Enable/disable default map UI
- Enable/disable control for toggling of map type
- Edit position of map type toggle
- Enable/disable Streetview control
- Edit position of Streetview control
- Google Map Languages (English, Spanish, German, French, Hebrew, Portuguese, Arabic, Japanese, Korean, Chinese Simplified, Vietnamese, Thailand)

InternetCSS presents **[Elementor Google Map Extended](https://internetcss.com/)**.

= About InternetCSS =
Elementor’s mission is to help users design websites in the easiest, fastest and most streamlined way.

We support Elementor’s mission through the development of Elementor add-ons that focus on beauty, subtle animations and visitor engagement.

= Documentation and Support =
- For more information about features, FAQs and documentation, check out our website at [InternetCSS](https://internetcss.com/).

= Fan of using Elementor Page Builder? =
- Join our [Facebook Group](https://www.facebook.com/groups/1181404975268306/).
- More free and premium Elementor Extended Widgets at our [website](https://internetcss.com/).

== Installation ==

= Minimum Requirements =

* WordPress 4.5 or greater
* PHP version 5.6 or greater
* MySQL version 5.0 or greater

= We recommend your host supports: =

* PHP version 5.6 or greater
* MySQL version 5.6 or greater
* WP Memory limit of 64 MB or greater (128 MB or higher is preferred)

= Installation =

1. Install using the WordPress built-in Plugin installer, or Extract the zip file and drop the contents in the `wp-content/plugins/` directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Pages > Add New
4. Press the 'Edit with Elementor' button.
5. Now you have the Elementor Google Map Extended from the left panel which yu can drag onto the content area.

== Frequently Asked Questions ==

**Do I need Elementor Pro for this to work?**

Elementor Pro is not required. You only need to have the Elementor Page Builder plugin installed and activated in your Wordpress installation.

**I am not seeing the Google Map and it is saying Sorry! Something went wrong**

You will need Google Map API in order to use Elementor Map Extended Module. You can generate a api key by going to https://developers.google.com/maps/documentation/javascript/get-api-key

**How do I change the language for the Google Map**

Navigate to Elementor -> Elementor Google Map. You will able to change your google map language from there.

== Changelog ==
= 1.2.1 - 10.09.2019 =
* Fix: Google Map Showing "sorry we have no imagery here" on IE browser due to enqueue dependency for Google Map API script.

= 1.2 - 09.09.2019 =
* Rewrote: Elementor Google Map Extended based on Elementor Hello Plugin.
* Added: Dequeue Google Map Scripts method for compatibility. (If you know the handle script of the other plugin, you can dequeue the script that also enqueue Google Map Script to improve compatiblity. (e.g. essential_addons_elementor-google-map-api, ep-google-maps) separate with a comma.)
* Added: Remove Data on Uninstall Option.
* Enhanced: Overall improvements.
* Fixed: Compatibility issue with Pro Version.

= 1.1.6 - 13.08.2019 =
* Added: Dynamic for Pin Title, Content, Latitude, Longtitude.
* Added: Google Map Languages (Swedish, Chinese for Taiwan and Hong Kong).
* Added: Default value for Map Height.

= 1.1.5 - 12.11.2018 =
* Fixed: Google API key Warning if value is empty.

= 1.1.4 - 12.11.2018 =
* Enhanced: Prepare for those who are using PHP 7.2.0 due to create_function() is deprecated.

= 1.1.3 - 12.11.2018 =
* Fixed: Fatal error: Can’t use function return value due older PHP. User should update their PHP version to move forward to the future and not backwards. Read the requirement of this plugin.

= 1.1.2 - 06.10.2018 =
* Added: A wrapper for pin content.

= 1.1.1 - 06.10.2018 =
* Added: VH unit for Map Height - Now support px and vh.
* Fixed: Pin Global Styles for Content not rendering.

= 1.1.0 - 30.08.2018 =
* Changed: Page setting language typo.
* Added: Google Map Languages (Thailand).
* Added: Plugin ready for localization.
* Added: French Translation - Credit momo-fr.

= 1.0.9 - 29.08.2018 =
* Fixed: Pin not rendering due to Title having apostrophe.
* Added: Google Map Languages (English, Spanish, German, French, Hebrew, Portuguese, Arabic, Japanese, Korean, Chinese Simplified, Vietnamese).

= 1.0.8 - 25.03.2018 =
* Fixed: Missing marker pin icons in WordPress Plugin SVN repository which causes missing images.

= 1.0.7 - 23.03.2018 =
* Added: 10 Color Marker Pin Icons to choose from (Default, Red, Blue, Yellow, Purple, Green, Orange, Grey, White and Black).

= 1.0.6 - 21.03.2018 =
* Removed: Draggable and Scroll Wheel function - Google no longer support this and the property is deprecated. This will be replaced by Gesture Handling.
* Added: Gesture Handling (Auto/Cooperative/Greedy/None).
* Added: InfoWindow Maximum Width - Setting default at 250px.

= 1.0.5 - 20.03.2018 =
* Fixed: Missing PHP tag on rendering which causes error on the frontend.

= 1.0.4 - 19.03.2018 =
* Added: Responsive Map Height Option.
* Fixed: Single and double quotes in pin content.

= 1.0.3 - 17.03.2018 =
* Added: Pressing enter key will now work on the find latitude and longitude input.
* Added: Auto complete longtitude and latitude input field when searching for address for map and pins.

= 1.0.2 - 16.03.2018 =
* Fixed: Google Map API Exposed and removed.

= 1.0.1 - 14.03.2018 =
* Added: InfoWindow will not show up on pin when Title and Content is empty.

= 1.0.0 - 28.02.2018 =
* Official Public Release