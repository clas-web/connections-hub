<?php


class Connections_CSVImporter
{
	public static $last_error = '';
	

    private function __construct() { }


    public static function import( $filename, &$rows )
    {
    	$headers = null;
		$rows = array();
		
		$delimiter = ',';
		$escape = ';';
		$length = 99999;


		if( !file_exists($filename) )
		{
        	self::$last_error = 'File does not exist: "'.$filename.'".';
        	return false;
		}
        
        $resource = @fopen( $filename, 'r' );
        
        if( $resource === false )
        {
        	self::$last_error = 'Unable to open file: "'.$filename.'".';
        	return false;
		}

        while( $keys = fgetcsv($resource, $length, $delimiter, $escape) )
        {
			//file_put_contents( CONNECTIONS_PLUGIN_PATH.'/keys.txt', print_r($keys, true), FILE_APPEND );

			if( $keys[0] === 'h' )
			{
				$headers = $keys;
				continue;
			}
			
			if( $headers === null ) 
				continue;

			if( $keys[0] !== '#' )
			{
				$row = array();
				
				for( $i = 1; $i < count($keys); $i++ )
				{
					if( ($i < count($headers)) && ($headers[$i] !== '') )
					{
						$row[$headers[$i]] = $keys[$i];
					}
				}
				
				for( $i = count($keys); $i < count($headers); $i++ )
				{
					$row[$headers[$i]] = '';
				}
				
				array_push($rows, $row);
			}
        }

        fclose( $resource );
        return $rows;
    }

}

?>
