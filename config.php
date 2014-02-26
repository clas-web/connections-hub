<?php


//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


define( 'CONNECTIONS_DEBUG', true );
define( 'CONNECTIONS_PLUGIN_NAME', 'Connections: Main Site' );
define( 'CONNECTIONS_PLUGIN_PATH', dirname(__FILE__) );
define( 'CONNECTIONS_PLUGIN_URL', plugins_url(basename(CONNECTIONS_PLUGIN_PATH)) );

$connections_url_replacements = array();

if( CONNECTIONS_DEBUG )
{
	$connections_url_replacements['clas-pages.uncc.edu'] = 'clas-incubator-wp.uncc.edu';
}

