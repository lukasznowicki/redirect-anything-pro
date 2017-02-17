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
	private $nonce;
	private $nonce_action;

	function __construct() {
		if ( is_admin() ) {
			$this->nonce = 'phylax_rap_nonce';
			$this->nonce_action = 'phylax_rap_nonce_action';
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
		add_action( 'save_post', [ $this, 'save_post' ] );
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

	function get_posts( $post_type = 'post' ) {
		return get_posts( [
			'posts_per_page' => 0,
			'orderby' => 'name',
			'order' => 'ASC',
			'post_type' => $post_type,
		] );
	}

	function get_posts_option( $post_type = 'post', $current = 0 ) {
		$html = '';
		$items = $this->get_posts( $post_type );
		if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
			foreach( $items as $item ) {
				$title = trim( $item->post_title );
				if ( '' == $title ) {
					$title = get_permalink( $item->ID );
				}
				$html.= '<option value="' . $item->ID . '">' . $title . '</option>' . PHP_EOL;
			}
		}
		return $html;
	}

	function post_types_array( $items ) {
		if ( !is_array( $items ) ) { return []; }
		if ( count( $items ) < 1 ) { return []; }
		$r = [];
		foreach( $items as $item ) {
			$r[ $item->name ] = $item->label;
		}
		return $r;
	}

	function get_post_types() {
		$built_in = $this->post_types_array( get_post_types( [ 'public' => true, '_builtin' => true ], 'objects' ) );
		$others = $this->post_types_array(get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' ) );
		return array_merge( $built_in, $others );
	}

	function save_post( $post_id ) {
		if ( !isset( $_POST[ $this->nonce ] ) ) { return; }
		if ( !wp_verify_nonce( $_POST[ $this->nonce ], $this->nonce_action ) ) { return; }
		if ( !post_type_exists( $_POST['post_type'] ) ) { return; }
		if ( !current_user_can( 'edit_post', $post_id ) ) { return; }
		$cpid = [];
		foreach( $_POST as $key => $value ) {
			if ( ( substr( $key, 0, 4 ) == 'pbs_' ) && ( substr( $key, -3 ) == '_id' ) ) {
				$cpid[ $key ] = (int)$value;
			}
		}
		$rap = [
			'use' => esc_attr( $_POST['pbrap_in_use'] ),
			'basic' => [
				'selector' => esc_attr( $_POST['pbrapsel_select'] ),
				'direct' => esc_attr( $_POST['pbs_phylax_direct'] ),
				'custom_posts' => $cpid,
			],
		];
		#echo '<pre>'.print_r($rap,true).'</pre>';
		#echo '<pre>'.print_r($_POST,true).'</pre>';
		#die('OK!');
		update_post_meta( $post_id, 'phylax_redirect_anything_pro', $rap );
	}

	function rap_view( $post ) {
		$rap = get_post_meta( $post->ID, 'phylax_redirect_anything_pro', true );
		if ( !isset( $rap['use'] ) ) { $rap['use'] = '0'; }
		$rap_active = 'false'; #
		if ( $rap['use'] == 'basic' ) { $rap_active = 0; }
		if ( $rap['use'] == 'advanced' ) { $rap_active = 1; }
		if ( !isset( $rap['basic'] ) ) { $rap['basic'] = []; }
		if ( !isset( $rap['basic']['selector'] ) ) { $rap['basic']['selector'] = ''; }
		if ( !isset( $rap['basic']['direct'] ) ) { $rap['basic']['direct'] = ''; }

		echo '<pre>'.print_r($rap,true).'</pre>';

		wp_nonce_field( $this->nonce_action, $this->nonce );
?>
		<p><small><?php _e( 'Here you can make this item redirect anywhere and whenever you want. Please select what you want to do, green indicator will let you know where you are.', TD ); ?></small></p>
		<script type="text/javascript">
			var phylax_rap_active = <?php echo $rap_active; ?>;
		</script>
		<input class="phylax_rap_hide" type="text" name="pbrap_in_use" id="pbrap_in_use" value="<?php echo $rap['use']; ?>">
		<div id="phylax_create_redirect">
			<h3 data-rapinuse="basic"><?php _e( 'Basic (simple)', TD ); ?></h3>
			<div>
				<p><?php _e( 'Redirect this page to:', TD ); ?></p>
				<ul id="rap_list_select">
<?php
	$post_types = $this->get_post_types();
	if ( is_array( $post_types ) && ( count( $post_types ) > 0 ) ) {
		foreach( $post_types as $post_type => $post_name ) {
			$options = $this->get_posts_option( $post_type );
			if ( '' != $options ) {
?>
					<li><label for="pbrap_<?php echo $post_type; ?>">
						<div class="pbrapsel" id="pbs_<?php echo $post_type; ?>"><?php echo $post_name; ?></div>
						<select name="pbs_<?php echo $post_type; ?>_id">
<?php
	echo $options;
?>
						</select>
					</label></li>
<?php
			}
		}
	}
?>
					<li><label for="pbrap_phylax_direct">
						<div class="pbrapsel" id="pbs_phylax_direct"><?php _e( 'Direct link', TD ); ?></div>
						<input type="text" name="pbs_phylax_direct" value="<?php echo $rap['basic']['direct']; ?>" placeholder="<?php _e( 'https://example.com/', TD ); ?>">
					</label></li>
				</ul>
				<input class="phylax_rap_hide" id="pbrapsel_select" type="text" name="pbrapsel_select" value="<?php echo $rap['basic']['selector']; ?>">
			</div>
			<h3 data-rapinuse="advanced"><?php _e( 'Advanced (conditional)', TD ); ?></h3>
			<div>
				<p><small><?php _e( 'So, I assume you are advanced user of WordPress. Then we give you a few more options but it is a bit trickier than in basic functionality. Of course it is much more powerful and flexible solution.', TD ); ?></small></p>
			</div>
		</div>
<?php
	}

}

$redirect_anything_pro = new Plugin();

#EOF