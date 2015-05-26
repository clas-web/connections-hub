<?php
/*
Plugin Name: Connections Hub: Random Spotlight
Plugin URI: 
Description: Display up to 5 pairs of Connections posts with matching Connection Links.
Version: 1.0.0
Author: Crystal Barton
Author URI: 
*/


require_once( dirname(__FILE__).'/control.php' );
ConnectionHubRandomSpotlight_WidgetShortcodeControl::register_widget();
ConnectionHubRandomSpotlight_WidgetShortcodeControl::register_shortcode();

