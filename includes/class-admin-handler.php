<?php
/**
 * Admin handler class for Connections Hub - APL-independent
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_AdminHandler') ):
class ConnectionsHub_AdminHandler
{
	/**
	 * Admin pages to be added as subpages.
	 * @var  Array
	 */
	protected $pages;
	
	/**
	 * The current page being displayed.
	 * @var  ConnectionsHub_AdminPage
	 */
	public $current_page;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->pages = array();
		$this->current_page = null;
	}

	/**
	 * Add a page as a submenu page.
	 * @param  ConnectionsHub_AdminPage  $page        The admin page to add.
	 * @param  string                    $parent_slug The parent menu slug.
	 */
	public function add_page( ConnectionsHub_AdminPage $page, $parent_slug = 'edit.php?post_type=connection' )
	{
		$this->pages[] = array(
			'page' => $page,
			'parent_slug' => $parent_slug
		);
	}

	/**
	 * Setup the admin pages.
	 */
	public function setup()
	{
		add_action( 'admin_menu', array($this, 'admin_menu_setup') );
		add_action( 'admin_init', array($this, 'admin_init') );
	}

	/**
	 * Setup the admin menu items.
	 */
	public function admin_menu_setup()
	{
		foreach( $this->pages as $page_data )
		{
			$page = $page_data['page'];
			$parent_slug = $page_data['parent_slug'];

			$page->page_hook = add_submenu_page(
				$parent_slug,
				$page->page_title,
				$page->menu_title,
				$page->capability,
				$page->name,
				array($this, 'display_page')
			);

			// Add load hook for this specific page
			add_action( 'load-' . $page->page_hook, array($this, 'load_page') );
		}
	}

	/**
	 * Initialize admin functionality.
	 */
	public function admin_init()
	{
		// Always register settings for all pages (required for WordPress Settings API)
		foreach( $this->pages as $page_data )
		{
			$page = $page_data['page'];
			$page->init();
			$page->register_settings();
			$page->add_settings_sections();
			$page->add_settings_fields();
		}
		
		// Process pages if we're on one
		$this->set_current_page();
		
		if( $this->current_page )
		{
			// Process form submissions and URL actions
			if( $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action']) )
			{
				$this->current_page->process();
			}
		}
	}

	/**
	 * Load page-specific functionality.
	 */
	public function load_page()
	{
		$this->set_current_page();
		
		if( $this->current_page )
		{
			$this->current_page->load();
			add_action( 'admin_enqueue_scripts', array($this->current_page, 'enqueue_scripts') );
		}
	}

	/**
	 * Display the current admin page.
	 */
	public function display_page()
	{
		$this->set_current_page();
		
		if( $this->current_page )
		{
			// Display any notices first
			$this->current_page->display_notices();
			
			// Then display the page content
			echo '<div class="wrap">';
			echo '<h1>' . esc_html($this->current_page->page_title) . '</h1>';
			$this->current_page->display();
			echo '</div>';
		}
	}

	/**
	 * Set the current page based on the page parameter.
	 */
	protected function set_current_page()
	{
		if( $this->current_page ) return;
		
		$page_slug = isset($_GET['page']) ? $_GET['page'] : '';
		
		foreach( $this->pages as $page_data )
		{
			if( $page_data['page']->name === $page_slug )
			{
				$this->current_page = $page_data['page'];
				break;
			}
		}
	}
}
endif;
