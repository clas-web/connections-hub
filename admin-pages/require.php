<?php

// Load the base admin classes
require_once( __DIR__.'/../includes/class-admin-page.php' );
require_once( __DIR__.'/../includes/class-admin-handler.php' );

// Load the admin pages
require_once( __DIR__.'/pages/export-connections.php' );
require_once( __DIR__.'/pages/import-connections.php' );
require_once( __DIR__.'/pages/synch-connections.php' );
require_once( __DIR__.'/pages/settings.php' );

