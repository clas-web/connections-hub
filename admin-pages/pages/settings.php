<?php
/**
 * ConnectionsHub_SettingsAdminPage
 * 
 * This class controls the admin page "Settings".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('ConnectionsHub_SettingsAdminPage') ):
class ConnectionsHub_SettingsAdminPage extends APL_AdminPage
{
	
	private $model = null;	
	
	
	/**
	 * Creates an ConnectionsHub_SettingsAdminPage object.
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
			'connections'		=> 'connections-custom-post-type',
			'connections-group'	=> 'connections-group-taxonomy',
			'connections-link'	=> 'connections-link-taxonomy',
		);
		$names = array(
			'full-single'	=> 'Full Single',
			'full-plural'	=> 'Full Plural',
			'short-single'	=> 'Short Single',
			'short-plural'	=> 'Short Plural',
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
	
	
	public function print_section_connections_custom_post_type( $args )
	{
		apl_print('print_section_connections_custom_post_type');
	}

	public function print_section_connections_group_taxonomy( $args )
	{
		apl_print('print_section_connections_group_taxonomy');
	}

	public function print_section_connections_link_taxonomy( $args )
	{
		apl_print('print_section_connections_link_taxonomy');
	}

	public function print_field_override_name( $args )
	{
		$name = array_merge( 
			array( CONNECTIONS_HUB_OPTIONS, 'name' ),
			$args
		);
		?>
		<input type="text" value="<?php apl_setting_e( $name ); ?>" name="<?php apl_name_e( $name ); ?>">
		<?php
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

