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
	private $list_post_type;
	private $list_post_items;

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
			'numberposts' => -1,
			'orderby' => 'name',
			'order' => 'ASC',
			'suppress_filters' => true,
			'post_type' => $post_type,
		] );
	}

	function translate_post_items( $items ) {
		if ( !is_array( $items ) ) { return []; }
		if ( count( $items ) < 1 ) { return []; }
		$r = [];
		foreach( $items as $item ) {
			$r[ $item->name ] = $item->label;
		}
		return $r;
	}

	function read_post_items( $post_type = 'post' ) {
		$r = [];
		$items = $this->get_posts( $post_type );
		if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
			foreach( $items as $item ) {
				$title = trim( $item->post_title );
				if ( '' == $title ) {
					$title = get_permalink( $item->ID );
				}
				$r[ $item->ID ] = $title;
			}
		}
		$this->list_post_items[ $post_type ] = $r;
	}

	function read_post_types() {
		$this->list_post_types = array_merge(
			$this->translate_post_items( get_post_types( [ 'public' => true, '_builtin' => true ], 'objects' ) ),
			$this->translate_post_items( get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' ) )
		);
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
			'redirect_use' => esc_attr( $_POST['phylax_redirect_use'] ),
			'basic' => [
			],
			'advanced' => [
			],
		];
		#echo '<pre>'.print_r($rap,true).'</pre>';
		#echo '<pre>'.print_r($_POST,true).'</pre>';
		#die('OK!');
		#update_post_meta( $post_id, 'phylax_redirect_anything_pro', $rap );
	}

	function get_post_meta( $id ) {
		$rap = get_post_meta( $id, 'phylax_redirect_anything_pro', true );
		if ( !isset( $rap['redirect_use'] ) ) { $rap['redirect_use'] = '0'; }
		if ( !isset( $rap['basic'] ) ) { $rap['basic'] = []; }
		if ( !isset( $rap['advanced'] ) ) { $rap['advanced'] = []; }

		return $rap;
	}

	function redirect_items_html( $slug, $select ) {
		$s = '';
		$s.= '<pre>'.print_r($select,true).'</pre>';
		$s.= '<p>' . __( 'Redirect current item to:', TD ) . '</p>' . PHP_EOL;
		$s.= '<ul id="list_select_' . $slug . '" class="phylax_list_select">' . PHP_EOL;
		foreach( $this->list_post_types as $post_type_slug => $post_type_name ) {
			$s.= '<li><span>' . $post_type_name . '</span>' . PHP_EOL;
			$s.= '<select name="' . $slug . '[' . $post_type_slug . ']">' . PHP_EOL;
			$current = 0;
			if ( isset( $select[ $post_type_slug ] ) ) {
				$current = $select[ $post_type_slug ];
			}
			foreach( $this->list_post_items[ $post_type_slug ] as $item_id => $item_name ) {
				$s.= '<option value="' . $item_id . '"' . ( ( $current == $item_id ) ? ' selected="selected"' : '' ) . '>' . $item_name . '</option>' . PHP_EOL;
			}
			$s.= '</select>' . PHP_EOL;
			$s.= '</li>' . PHP_EOL;
		}
		$s.= '<li><span>' . __( 'External link', TD ) . '</span>' . PHP_EOL;
		$s.= '<input type="text" name="' . $slug . '[phylax_rap_external_link]">';
		$s.= '</li>' . PHP_EOL;
		$s.= '</ul>' . PHP_EOL;
		return $s;
	}

	function rap_view( $post ) {
		$rap = $this->get_post_meta( $post->ID );
		$rap_active = 'false';
		if ( $rap['redirect_use'] == 'basic' ) { $rap_active = 0; }
		if ( $rap['redirect_use'] == 'advanced' ) { $rap_active = 1; }

		wp_nonce_field( $this->nonce_action, $this->nonce );

		$this->read_post_types();
		foreach( $this->list_post_types as $post_type_slug => $post_type_name ) {
			$this->read_post_items( $post_type_slug );
		}
?>
		<p><small><?php _e( 'Here you can make this item redirect anywhere and whenever you want. Please select what you want to do, <span class="phylax_green">green</span> indicator on the left will let you know where you are.', TD ); ?></small></p>
		<script type="text/javascript">
			var phylax_rap_active = <?php echo $rap_active; ?>;
		</script>
		<div id="phylax_create_redirect">
			<h3 data-redirect_use="basic"><?php _e( 'Basic (simple)', TD ); ?></h3>
			<div>
<?php
	echo $this->redirect_items_html( 'basic', $rap['basic'] );
?>
			</div>
			<h3 data-redirect_use="advanced"><?php _e( 'Advanced (conditional)', TD ); ?></h3>
			<div>
				<p><small><?php _e( 'So, I assume you are advanced user of WordPress. Then we give you a few more options but it is a bit trickier than in basic functionality. Of course it is much more powerful and flexible solution.', TD ); ?></small></p>
<?php
	echo $this->redirect_items_html( 'advanced', $rap['advanced'] );
?>
			</div>
		</div>
		<input class="phylax_hide" type="text" name="phylax_redirect_use" id="phylax_redirect_use" value="<?php echo $rap['redirect_use']; ?>">
<?php
	}

}

$redirect_anything_pro = new Plugin();

#EOF