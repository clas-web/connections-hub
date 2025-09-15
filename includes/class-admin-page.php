<?php
/**
 * Base admin page class for Connections Hub - APL-independent
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_AdminPage') ):
abstract class ConnectionsHub_AdminPage
{
	/**
	 * The name/slug of the page.
	 * @var  string
	 */
	public $name;

	/**
	 * The title of the page.
	 * @var  string
	 */
	public $page_title;

	/**
	 * The title show on the left menu.
	 * @var  string
	 */
	public $menu_title;

	/**
	 * The capability needed to displayed to the user.
	 * @var string
	 */
	public $capability;
	
	/**
	 * The WordPress page hook.
	 * @var string
	 */
	public $page_hook;

	/**
	 * Array of notices to display.
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Constructor.
	 */
	public function __construct( $name, $menu_title, $page_title, $capability = 'manage_options' )
	{
		$this->name = $name;
		$this->menu_title = $menu_title;
		$this->page_title = $page_title;
		$this->capability = $capability;
	}

	/**
	 * Initialize the admin page. Called during "admin_init" action.
	 */
	public function init()
	{
		// Override in child classes if needed
	}

	/**
	 * Loads the admin page. Called during "load-{page}" action.
	 */
	public function load()
	{
		// Override in child classes if needed
	}

	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
		// Override in child classes if needed
	}

	/**
	 * Displays the current admin page.
	 */
	abstract public function display();

	/**
	 * Register a setting for the Settings API.
	 */
	public function register_setting( $option_name, $args = array() )
	{
		register_setting( $this->name, $option_name, $args );
	}

	/**
	 * Add a section for the Settings API.
	 */
	public function add_section( $id, $title, $callback = null )
	{
		add_settings_section( $id, $title, $callback, $this->name );
	}

	/**
	 * Add a field for the Settings API.
	 */
	public function add_field( $id, $title, $callback, $section = 'default', $args = array() )
	{
		add_settings_field( $id, $title, $callback, $this->name, $section, $args );
	}

	/**
	 * Register settings for the Settings API.
	 */
	public function register_settings()
	{
		// Override in child classes if needed
	}

	/**
	 * Add sections for the Settings API.
	 */
	public function add_settings_sections()
	{
		// Override in child classes if needed
	}

	/**
	 * Add fields for the Settings API.
	 */
	public function add_settings_fields()
	{
		// Override in child classes if needed
	}

	/**
	 * Enqueue scripts and styles for this admin page.
	 */
	public function enqueue_scripts()
	{
		// Override in child classes if needed
	}

	/**
	 * Get the admin page URL.
	 */
	public function get_page_url( $args = array() )
	{
		$url = admin_url( 'edit.php?post_type=connection&page=' . $this->name );
		
		if( !empty($args) )
		{
			$url = add_query_arg( $args, $url );
		}
		
		return $url;
	}

	/**
	 * Print a section description (placeholder for Settings API).
	 */
	public function print_section_info( $args = array() )
	{
		// Override in child classes if needed
	}

	/**
	 * Start a form with POST method.
	 */
	public function form_start( $class = null, $attributes = array(), $action = null, $query = array() )
	{
		if( !is_array($attributes) ) $attributes = array();
		if( !is_array($query) ) $query = array();
		
		if( $action ) {
			$query['action'] = $action;
		}
		
		?>
		<form action="<?php echo $this->get_page_url( $query ); ?>" 
		      method="POST" 
		      class="<?php echo esc_attr($class); ?>"
		      <?php foreach( $attributes as $key => $value ) { echo esc_attr($key).'="'.esc_attr($value).'" '; } ?>
		      >
		<?php wp_nonce_field( $this->name . '_action', $this->name . '_nonce' ); ?>
		<?php
	}

	/**
	 * Start a form with GET method.
	 */
	public function form_start_get( $class = null, $attributes = array(), $action = null, $query = array() )
	{
		if( !is_array($attributes) ) $attributes = array();
		if( !is_array($query) ) $query = array();
		
		if( $action ) {
			$query['action'] = $action;
		}
		
		?>
		<form action="<?php echo $this->get_page_url(); ?>" 
		      method="GET"
		      class="<?php echo esc_attr($class); ?>"
		      <?php foreach( $attributes as $key => $value ) { echo esc_attr($key).'="'.esc_attr($value).'" '; } ?>
		      >
		<input type="hidden" name="post_type" value="connection" />
		<input type="hidden" name="page" value="<?php echo esc_attr($this->name); ?>" />
		<?php foreach( $query as $key => $value ) { ?>
			<input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" />
		<?php } ?>
		<?php
	}

	/**
	 * End a form.
	 */
	public function form_end()
	{
		?>
		</form>
		<?php
	}

	/**
	 * Add a notice to be displayed.
	 */
	public function add_notice( $notice, $save = false )
	{
		$this->notices[] = $notice;
		if( $save ) {
			// For WordPress admin notices, we'll use transients
			set_transient( 'connections_hub_admin_notice_' . get_current_user_id(), $this->notices, 30 );
		}
	}

	/**
	 * Display admin notices.
	 */
	public function display_notices()
	{
		// Get saved notices
		$saved_notices = get_transient( 'connections_hub_admin_notice_' . get_current_user_id() );
		if( $saved_notices ) {
			$this->notices = array_merge( $this->notices, $saved_notices );
			delete_transient( 'connections_hub_admin_notice_' . get_current_user_id() );
		}

		if( !empty($this->notices) ) {
			foreach( $this->notices as $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
			}
		}
	}

	/**
	 * Start a form for Settings API.
	 */
	public function form_start_settings_api( $attributes = array() )
	{
		?>
		<form action="options.php" method="POST" 
		    <?php
		    foreach( $attributes as $key => $value ):
		      	echo esc_attr($key).'="'.esc_attr($value).'" ';
		    endforeach; ?>
		    >   
		<?php
		settings_fields( $this->name );
	}

	/**
	 * Print settings sections and fields.
	 */
	public function print_settings()
	{
		$this->form_start_settings_api();
		?>
		
		<div class="top-submit"><?php submit_button(); ?></div>
		<div style="clear:both"></div>
		<input type="hidden" name="page" value="<?php echo esc_attr($this->name); ?>" />
		
		<?php
		do_settings_sections( $this->name );
		?>
		
		<div class="bottom-submit"><?php submit_button(); ?></div>
		
		<?php
		$this->form_end();
	}
}
endif;
