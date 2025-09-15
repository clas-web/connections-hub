<?php
/**
 * Controls the admin page "Settings".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_SettingsAdminPage') ):
class ConnectionsHub_SettingsAdminPage extends ConnectionsHub_AdminPage
{
	/**
	 * The main model for the Connections Hub plugin.
	 * @var  ConnectionsHub_Model
	 */
	private $model = null;
	
	/**
	 * Constructor.
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
		// Register the settings - the first parameter is the option group, 
		// which should match what we use in settings_fields()
		register_setting( 
			CONNECTIONS_HUB_OPTIONS, 
			CONNECTIONS_HUB_OPTIONS,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' )
			)
		);
	}
	
	/**
	 * Add the sections used for the Settings API. 
	 */
	public function add_settings_sections()
	{
		add_settings_section(
			'connections-custom-post-type',
			'Connections custom post type',
			array( $this, 'print_section_connections_custom_post_type' ),
			'connections_hub_settings'
		);
		add_settings_section(
			'connections-group-taxonomy',
			'Connections Group taxonomy',
			array( $this, 'print_section_connections_group_taxonomy' ),
			'connections_hub_settings'
		);
		add_settings_section(
			'connections-link-taxonomy',
			'Connections Link taxonomy',
			array( $this, 'print_section_connections_link_taxonomy' ),
			'connections_hub_settings'
		);
	}
	
	/**
	 * Add the fields used for the Settings API.
	 */
	public function add_settings_fields()
	{
		$options = get_option( CONNECTIONS_HUB_OPTIONS, array() );
		
		// Custom post type fields
		$names = array(
			'connections_custom_post_type_name' => 'Name',
			'connections_custom_post_type_plural_name' => 'Plural Name',
			'connections_custom_post_type_slug' => 'Slug',
		);
		$this->add_custom_post_type_fields( 'connections-custom-post-type', $names );
		
		// Group taxonomy fields
		$names = array(
			'connections_group_taxonomy_name' => 'Name',
			'connections_group_taxonomy_plural_name' => 'Plural Name',
			'connections_group_taxonomy_slug' => 'Slug',
		);
		$this->add_custom_post_type_fields( 'connections-group-taxonomy', $names );
		
		// Link taxonomy fields
		$names = array(
			'connections_link_taxonomy_name' => 'Name',
			'connections_link_taxonomy_plural_name' => 'Plural Name',
			'connections_link_taxonomy_slug' => 'Slug',
		);
		$this->add_custom_post_type_fields( 'connections-link-taxonomy', $names );
	}
	
	/**
	 * Add custom post type fields to a section.
	 */
	private function add_custom_post_type_fields( $section, $names )
	{
		foreach( $names as $name => $title )
		{
			add_settings_field(
				$name,
				$title,
				array( $this, 'print_input_field' ),
				'connections_hub_settings',
				$section,
				array( 'name' => $name )
			);
		}
	}
	
	/**
	 * Print the any instructions for the Connections custom post type section.
	 */
	public function print_section_connections_custom_post_type( $args )
	{
		echo '<p>Configure the custom post type settings for Connections.</p>';
	}
	
	/**
	 * Print the any instructions for the Connections group taxonomy section.
	 */
	public function print_section_connections_group_taxonomy( $args )
	{
		echo '<p>Configure the group taxonomy settings for Connections.</p>';
	}
	
	/**
	 * Print the any instructions for the Connections link taxonomy section.
	 */
	public function print_section_connections_link_taxonomy( $args )
	{
		echo '<p>Configure the link taxonomy settings for Connections.</p>';
	}
	
	/**
	 * Print an input field for settings.
	 */
	public function print_input_field( $args )
	{
		$name = $args['name'];
		$options = get_option( CONNECTIONS_HUB_OPTIONS, array() );
		$current_value = isset( $options[$name] ) ? $options[$name] : '';
		
		echo '<input type="text" value="' . esc_attr($current_value) . '" name="' . esc_attr(CONNECTIONS_HUB_OPTIONS) . '[' . esc_attr($name) . ']" class="regular-text" />';
	}
	
	/**
	 * Sanitize settings before saving.
	 */
	public function sanitize_settings( $input )
	{
		$sanitized = array();
		
		if( is_array($input) ) {
			foreach( $input as $key => $value ) {
				$sanitized[$key] = sanitize_text_field( $value );
			}
		}
		
		return $sanitized;
	}
	
	/**
	 * Process form submission.
	 */
	public function process()
	{
		if( !isset($_POST['action']) || $_POST['action'] !== 'save_settings' ) return;
		
		// Verify nonce
		if( !wp_verify_nonce( $_POST['connections_hub_nonce'], 'connections_hub_save_settings' ) ) {
			$this->add_notice( 'Security check failed. Please try again.', true );
			return;
		}
		
		// Get submitted data
		$submitted_data = isset($_POST[CONNECTIONS_HUB_OPTIONS]) ? $_POST[CONNECTIONS_HUB_OPTIONS] : array();
		
		// Sanitize the data
		$sanitized_data = $this->sanitize_settings( $submitted_data );
		
		// Save to database
		update_option( CONNECTIONS_HUB_OPTIONS, $sanitized_data );
		
		// Add success notice
		$this->add_notice( 'Settings saved successfully!', true );
	}

	/**
	 * Print settings sections and fields (override parent method).
	 */
	public function print_settings()
	{
		?>
		<form method="POST">
		<?php wp_nonce_field( 'connections_hub_save_settings', 'connections_hub_nonce' ); ?>
		<input type="hidden" name="action" value="save_settings" />
		
		<div class="top-submit"><?php submit_button(); ?></div>
		<div style="clear:both"></div>
		
		<?php
		do_settings_sections( 'connections_hub_settings' );
		?>
		
		<div class="bottom-submit"><?php submit_button(); ?></div>
		
		</form>
		<?php
	}

	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		?>
		<div class="wrap">
			<h1>Connections Hub Settings</h1>
			<p>Configure the settings for the Connections Hub plugin.</p>
			<?php $this->print_settings(); ?>
		</div>
		<?php
	}

} // class ConnectionsHub_SettingsAdminPage
endif; // if( !class_exists('ConnectionsHub_SettingsAdminPage') )