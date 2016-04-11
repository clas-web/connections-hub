<?php
/*
Plugin Name: Connections Hub: Random Spotlight
Plugin URI: https://github.com/clas-web/connections-hub
Description: Display up to 20 pairs of Connections posts with matching Connection Links.
Version: 1.0.0
Author: Crystal Barton
Author URI: http://www.linkedin.com/in/crystalbarton
*/


require_once( __DIR__.'/control.php' );
ConnectionsHubRandomSpotlight_WidgetShortcodeControl::register_widget();
ConnectionsHubRandomSpotlight_WidgetShortcodeControl::register_shortcode();

