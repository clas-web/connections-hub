<?php
/*
Plugin Name: Connections Hub - Random Spotlight
Plugin URI: 
Description: 
Version: 0.0.1
Author: Crystal Barton
Author URI: 
*/


require_once( dirname(__FILE__).'/control.php' );
ConnectionHubRandomSpotlight_WidgetShortcodeControl::register_widget();
ConnectionHubRandomSpotlight_WidgetShortcodeControl::register_shortcode();

