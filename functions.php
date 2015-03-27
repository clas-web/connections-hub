<?php


/**
 * @param  
 */
function connections_log( $text )
{
	file_put_contents( dirname(__FILE__).'/log.txt', print_r($text,true)."\n", FILE_APPEND );
}


function connections_fix_url( $url )
{
	$url = trim($url);
	
	if( $url === '' ) return '';
	
	if( CONNECTIONS_DEBUG )
	{
		global $connections_url_replacements;
		foreach( $connections_url_replacements as $find => $replace )
		{
			$url = str_replace( $find, $replace, $url );
		}
	}

// 	$url = str_replace( 'https://', 'http://', $url );
// 
// 	if( strpos( $url, 'http://' ) === FALSE ) $url = 'http://'.$url;
// 
// 	if( substr($url,-1) !== '/' ) $url .= '/';
		
	return $url;
}


/**
 * Return an array of category ids for a post.
 * -- Originally from "CSV Importer" plugin.
 *
 * @param string  $data csv_post_categories cell contents
 * @param integer $common_parent_id common parent id for all categories
 * @return array category ids
 */
function csv_importer_create_or_get_categories($data, $common_parent_id = null)
{
	$ids = array(
		'post' => array(),
		'cleanup' => array(),
	);
	
	if( is_array($data) )
		$items = $data;
	else
		$items = array_map('trim', explode(',', $data));
	
	foreach ($items as $item)
	{
		if (is_numeric($item))
		{
			if (get_category($item) !== null)
				$ids['post'][] = $item;
		}
		else
		{
			$parent_id = $common_parent_id;
			// item can be a single category name or a string such as
			// Parent > Child > Grandchild
			$categories = array_map('trim', explode('>', $item));
			
			if (count($categories) > 1 && is_numeric($categories[0]))
			{
				$parent_id = $categories[0];
				if (get_category($parent_id) !== null)
				{
					// valid id, everything's ok
					$categories = array_slice($categories, 1);
				}
				else
				{
					// ERROR "Category ID {$parent_id} does not exist, skipping.";
					continue;
				}
			}
			
			foreach ($categories as $category)
			{
				if ($category)
				{
					$term = term_exists($category, 'category', $parent_id);
					
					if ($term)
					{
						$term_id = $term['term_id'];
					}
					else
					{
						$term_id = wp_insert_category( array(
							'cat_name' => $category,
							'category_parent' => $parent_id,
						));
						$ids['cleanup'][] = $term_id;
					}
					$parent_id = $term_id;
				}
			}
			
			$ids['post'][] = intval($term_id);
		}
	}
	
	return $ids;
}

