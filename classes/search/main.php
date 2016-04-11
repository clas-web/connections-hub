<?php
/*
Plugin Name: Connections Hub: Search
Plugin URI: https://github.com/clas-web/connections-hub
Description: Display the Connections search form.
Version: 1.0.0
Author: Crystal Barton
Author URI: http://www.linkedin.com/in/crystalbarton
*/


require_once( __DIR__.'/control.php' );
ConnectionsHubSearch_WidgetShortcodeControl::register_widget();
ConnectionsHubSearch_WidgetShortcodeControl::register_shortcode();

