<?php
/**
 * Controls the admin page "Settings".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_SettingsAdminPage') ):
class ConnectionsHub_SettingsAdminPage extends APL_AdminPage
{
	/**
	 * The main model for the Connections Hub plugin.
	 * @var  ConnectionsHub_Model
	 */
	private $model = null;
	
	
	/**
	 * Controller.
	 */
	public function __construct(
		$name = 'settings',
		$menu_title = 'Settings',
		$page_title = 'Settings',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ConnectionsHub_Model::get_instance();
	}
	

	/**
	 * Register each individual settings for the Settings API.
	 */
	public function register_settings()
	{
		$this->register_setting( CONNECTIONS_HUB_OPTIONS );
	}
	

	/**
	 * Add the sections used for the Settings API. 
	 */
	public function add_settings_sections()
	{
		$this->add_section(
			'connections-custom-post-type',
			'Connections custom post type',
			'print_section_connections_custom_post_type'
		);
		$this->add_section(
			'connections-group-taxonomy',
			'Connections Group taxonomy',
			'print_section_connections_group_taxonomy'
		);
		$this->add_section(
			'connections-link-taxonomy',
			'Connections Link taxonomy',
			'print_section_connections_link_taxonomy'
		);
	}
	
	
	/**
	 * Add the settings used for the Settings API. 
	 */
	public function add_settings_fields()
	{
		$sections = array( 
			'connection'	=> 'connections-custom-post-type',
			'group'			=> 'connections-group-taxonomy',
			'link'			=> 'connections-link-taxonomy',
		);
		$names = array(
			'full_single'	=> 'Full Single',
			'full_plural'	=> 'Full Plural',
			'short_single'	=> 'Short Single',
			'short_plural'	=> 'Short Plural',
			'slug'			=> 'Slug',
		);
		
		foreach( $sections as $sname => $section )
		{
			foreach( $names as $name => $title )
			{
				$this->add_field(
					$section,
					$name,
					$title,
					'print_field_override_name',
					array( $sname, $name )
				);
			}
		}
	}
	
	
	/**
	 * Print the any instructions for the Connections custom post type section.
	 * @param  Array  $args  The passed args.
	 */
	public function print_section_connections_custom_post_type( $args )
	{
		apl_print('print_section_connections_custom_post_type');
	}


	/**
	 * Print the any instructions for the Connections group taxonomy section.
	 * @param  Array  $args  The passed args.
	 */
	public function print_section_connections_group_taxonomy( $args )
	{
		apl_print('print_section_connections_group_taxonomy');
	}


	/**
	 * Print the any instructions for the Connections link taxonomy section.
	 * @param  Array  $args  The passed args.
	 */
	public function print_section_connections_link_taxonomy( $args )
	{
		apl_print('print_section_connections_link_taxonomy');
	}


	/**
	 * Print override textbox for post type or taxonomy field.
	 * @param  Array  $args  The passed args.
	 */
	public function print_field_override_name( $args )
	{
		$name = array_merge( 
			array( CONNECTIONS_HUB_OPTIONS, 'name' ),
			$args
		);
		$current_value = $this->get_connection_setting( $name );
		//print_r ($name);
		?>
		<input type="text" value="<?php echo $current_value; ?>" name="<?php apl_name_e( $name ); ?>">
		<span class="current-value"><?php echo $current_value; ?></span>
		<?php
	}
	
	
	/**
	 * Get a Connections setting.
	 * @param  Array  $args  The passed args.
	 * @return  string|null  The setting, or Null if not found.
	 */
	protected function get_connection_setting( $args )
	{
		$settings = Connections_ConnectionCustomPostType::get_settings();
		
		$value = null;

		for( $i = 1; $i < count($args); $i++ )
		{
			if( !array_key_exists($args[$i], $settings) ) break;
		
			$settings = $settings[$args[$i]];
		
			if( count($args) == $i + 1 )
			{
				$value = $settings;
				break;
			}
		
			if( !is_array($settings) ) break;
		}
	
		return $value;	
	}
	
	
	/**
	 * Processes the current admin page's Settings API input.
	 * @param  array  $settings  The inputted settings from the Settings API.
	 * @param  string  $option  The option key of the settings input array.
	 * @return  array  The resulted array to store in the db.
	 */
	public function process_settings( $settings, $option )
	{
		$settings = parent::process_settings( $settings, $option );
		if( $option !== CONNECTIONS_HUB_OPTIONS ) return $settings;
		
		$sections = array( 
			'connection',
			'group',
			'link',
		);
		
		foreach( $settings['name'] as &$section )
		{
			foreach( $section as $key => $value )
			{
				if( empty($value) ) unset($section[$key]);
			}
		}
		
		return $settings;
	}
	
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
	}
	
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		$this->print_settings();
	}

} // class ConnectionsHub_SettingsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ConnectionsHub_SettingsAdminPage') )

