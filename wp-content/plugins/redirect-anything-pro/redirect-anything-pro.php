<?php
/*
 Plugin Name: Redirect Anything Pro
  Plugin URI: https://phylax.pl/plugins/redirect-anything-pro/
 Description: Create redirection whenever you want, using conditionals or make it straight.
      Author: Lukasz Nowicki
  Author URI: http://lukasznowicki.info/
     Version: 0.1.0
     License: GPLv2 or later
 License URI: http://www.gnu.org/licenses/gpl-2.0.html
 Text Domain: phylaxrap
 Domain path: /languages
 Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZUE7KNWHW3CJ4
*/
namespace Phylax\WPPlugin\RedirectAnythingPro;

define( __NAMESPACE__ . '\TD', 'phylaxrap' );

class Plugin {

	private $o;
	private $screen;

	function __construct() {
		if ( is_admin() ) {
			$this->dashboard();
		}
	}

	function default_options() {
		return [
			'v' => 1000,	# 0*1000000 + 1*1000 + 0 = 1000, example 5.4.52 4*1000000+2*1000+31= 5004052
			'screen' => [ 'post', 'page' ],
			'pos' => 'side',
			'priority' => 'high',
		];
	}

	function read_options() {
		$this->o = get_option( 'phylax_redirect_anything_pro' );
		if ( false === $this->o ) {
			$this->o = $this->default_options();
		}
	}

	function dashboard() {
		$this->read_options();
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'current_screen', [ $this, 'current_screen' ] );
	}

	function current_screen( $screen ) {
		$this->screen = $screen;
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	function admin_enqueue_scripts() {
		wp_enqueue_style( 'phylax-style-rap', plugins_url( 'assets/css/rap.css', __FILE__ ) );
		wp_enqueue_script( 'phylax-script-rap', plugins_url( 'assets/js/rap.js', __FILE__ ), [ 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ] );
	}

	function add_meta_boxes() {
		add_meta_box(
			'phylax-redirect-anything-pro',
			__( 'Redirect Anything Pro', TD ),
			[ $this, 'rap_view' ],
			$this->o[ 'screen' ],
			$this->o[ 'pos' ],
			$this->o[ 'priority' ]
		);
	}

	function rap_view( $post ) {
		$rap_active = 'false'; #
?>
		<p><?php _e( 'Here you can make this item redirect anywhere and whenever you want. Please select what you want to do, green indicator will let you know where you are.', TD ); ?></p>
		<script type="text/javascript">
			var phylax_rap_active = <?php echo $rap_active; ?>;
		</script>
		<div id="phylax_create_redirect">
			<h3><?php _e( 'Basic (simple)', TD ); ?></h3>
			<div>
				Basic
			</div>
			<h3><?php _e( 'Advanced (conditional)', TD ); ?></h3>
			<div>
				Advanced
			</div>
		</div>
<?php
	}

}

$redirect_anything_pro = new Plugin();

#EOF