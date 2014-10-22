<?php


//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


define( 'CONNECTIONS_DEBUG', false );
define( 'CONNECTIONS_PLUGIN_NAME', 'Connections Hub' );
define( 'CONNECTIONS_PLUGIN_PATH', dirname(__FILE__) );
define( 'CONNECTIONS_PLUGIN_URL', plugins_url(basename(CONNECTIONS_PLUGIN_PATH)) );

define( 'CRON_LOG', CONNECTIONS_PLUGIN_PATH . '/logs/' . date('Ymd-His') . '.txt' );

$connections_url_replacements = array();

if( CONNECTIONS_DEBUG )
{
	$connections_url_replacements['clas-pages.uncc.edu'] = 'clas-incubator-wp.uncc.edu';
}

