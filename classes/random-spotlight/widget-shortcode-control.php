<?php


if( !class_exists('WidgetShortcodeControl') ):
class WidgetShortcodeControl extends WP_Widget
{
	
	private $index = 0;
	
	
	/**
	 *
	 */
	public function __construct( $id_base, $name, $widget_ops = null, $control_ops = null )
	{
		parent::__construct( $id_base, $name, $widget_ops, $control_ops );

		$this->args = array(
			'before_widget'	=> '<div id="%1$s" class="widget %2$s">',
			'after_widget'	=> "</div>\n",
			'before_title'	=> '<h2 class="title">',
			'after_title'	=> "</h2>\n",
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}
	
	
	/**
	 * 
	 */
	public static function register_widget()
	{
		add_action(
			'widgets_init',
			create_function('', 'return register_widget("'.get_called_class().'");')
		);
	}
	
	
	/**
	 * 
	 */
	public static function register_shortcode()
	{
		add_filter( 'the_content', array(new static(), 'process_content_shortcode'), 1 );
	}


	/**
	 *
	 */
	public function admin_enqueue_scripts()
	{
	}
	
	
	/**
	 *
	 */
	public function enqueue_scripts()
	{
	}
	
	
	/**
	 *
	 */
	public function widget( $args, $options )
	{
		$this->print_control( $options, $args );
	}
	
	
	/**
	 *
	 */
	public function form( $options )
	{
		$this->print_widget_form( $options );
	}
	
	
	/**
	 *
	 */
	public function update( $new_options, $old_options )
	{
		return $new_options;
	}
	
	
	/**
	 *
	 */
	public function print_widget_form( $options )
	{
		parent::form( $options );
	}
	
	
	/**
	 *
	 */
	public function process_content_shortcode( $content )
	{
		$matches = NULL;
		$num_matches = preg_match_all( "/\[".$this->id_base."(.*)\]/", $content, $matches, PREG_SET_ORDER );

		if( ($num_matches !== FALSE) && ($num_matches > 0) )
		{
			for( $i = 0; $i < $num_matches; $i++ )
			{
				$this->index = $i;
				$content = str_replace($matches[$i][0], $this->convert_shortcode( $matches[$i][0] ), $content);
			}
		}
		
		return $content;
	}
	
	
	/**
	 *
	 */
	public function convert_shortcode( $shortcode )
	{
		$options = array();
		
		$matches = NULL;
		$num_matches = preg_match_all( "/([A-Za-z0-9\-_]+)=\"([^\"]+)\"/", $shortcode, $matches, PREG_SET_ORDER );

		if( ($num_matches !== FALSE) && ($num_matches > 0) )
		{
			for( $i = 0; $i < $num_matches; $i++ )
			{
				$options[$matches[$i][1]] = $matches[$i][2];
			}
		}
		
		$options = $this->process_shortcode_options( $options );

		ob_start();
		$this->print_control( $options );
		$buffer = ob_get_contents();
		ob_end_clean();
		
		return $buffer;
	}
	
	
	/**
	 *
	 */
	public function process_shortcode_options( $options )
	{
		return $options;
	}
	
	
	/**
	 *
	 */
	public function get_default_options()
	{
		return array();
	}
	
	
	/**
	 *
	 */
	public function get_args()
	{
		$args = $this->args;
		
		foreach( $args as $k => &$v )
		{
			$v = sprintf(
				$v,
				$this->id_base.'-s'.$this->index,
				$this->id_base
			);
		}
		
		return $args;
	}
	
	
	/**
	 *
	 */
	public function merge_options( $options )
	{
		return array_merge( $this->get_default_options(), $options );
	}
	
	
	/**
	 *
	 */
	public function print_control( $options, $args = null )
	{
		$options = $this->merge_options( $options );
		if( !$args ) $args = $this->get_args();
		
		echo 'options';
		var_dump($options);
		echo 'args';
		var_dump($args);
	}
	
}
endif;

