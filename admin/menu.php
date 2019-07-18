<?php

// This class will create HTML page in wp_admin and insert all category name and slugs entered in fields.

class gcc_menu {
	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return	A single instance of this class.
	 */
	public static function get_instance() {
		static $instance = null;
		if($instance === null){
			$instance = new self;
		}
		return $instance;
	}
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}
	
	public function register_menu() {
		add_menu_page(
			__( 'Group category creator', 'gcc' ),
			__( 'Group category creator', 'gcc' ),
			'manage_options',
			'gcc',
			array( $this, 'screen' )
		);
	}
	
	// Create gcc page
	public function screen() {
		$error = false;
		
		// Tab filters
		$tabs = array();
		
		$tabs[]		= __( "WordPress" );
		if( class_exists( 'Easy_Digital_Downloads' ) ) {
			$tabs[]	= __( "Easy Digital Downloads", 'gcc' );
		}
		if( class_exists( 'WooCommerce' ) ) {
			$tabs[]	= __( "WooCommerce", 'gcc' );
		}
		$tabs = apply_filters( 'gcc_tabs_name', $tabs );
		
		$tab_slug = array();
		foreach( $tabs as $tab_name ) {
			if( $tab_name == __( "WordPress" ) ) {
				$tab_slug[]	= "wordpress";
			} elseif( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$tab_slug[]	= "edd";
			} elseif( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$tab_slug[]	= "woo";
			}
			$tab_slug = apply_filters( 'gcc_tabs_slug', $tab_slug, $tab_name );
		}
		
		// Default active tab
		$active_tab = 'wordpress';
		$active_tab = apply_filters( 'gcc_default_active_tab', $active_tab );
		
		if ( isset( $_GET['tab'] ) ) {
			$active_tab = $_GET['tab'];
		}
		
		$taxonomies = array();
		foreach( $tabs as $tab_name ) {
			if( $tab_name == __( "WordPress" ) ) {
				$taxonomies[]	= "category";
			} elseif( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$taxonomies[]	= "download_category";
			} elseif( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$taxonomies[]	= "product_cat";
			}
		}
		$taxonomies = apply_filters( 'gcc_taxonomies', $taxonomies );
		
		$taxonomy = '';
		foreach( $tab_slug as $index => $slug ) {
			if ( $active_tab == $slug ) {
				$taxonomy = $taxonomies[$index];
				break;
			}
		}
		
		// Check if data send to insert
		if ( !empty( $_POST['gcc_names'] ) ) {
			// Convert to array
			$names = explode( PHP_EOL, $_POST['gcc_names'] );
			$nicnames = explode( PHP_EOL, $_POST['gcc_nicnames'] );
			
			$parent = $_POST['gcc_parent'];
			
			// Create a sorted array for categories & category slugs will be added
			$cats = array();
			$index = 0;
			foreach( $names as $name ) {
				if ( !$nicnames[$index] ) {
					$nicnames[$index] = $name;
				}
				$cats[$name] = $nicnames[$index];
				$index++;
			}
			
			$cats = apply_filters( 'gcc_cats', $cats );
			
			// Start insert categories
			foreach( $cats as $name => $nicname ) {
				$id = wp_insert_term( $name, $taxonomy, array( 'slug'=>$nicname, 'parent'=>$parent ) );
				if ( is_wp_error($id) ) {
					$error = true;
					?>
					<div class="error notice is-dismissible">
						<p><?php $id->get_error_message(); ?></p>
					</div>
					<?php
					break;
				}
			}
			
			if ( !$error ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e( 'All categories added.', 'gcc' ); ?></p>
				</div>
				<?php
			}
		}
		
		// Get all categories
		$categories = get_terms( $taxonomy, array('hide_empty' => 0) );
		
		// Create HTML page
		?>
		<div class="wrap">
			<h1><?php _e( 'Add categories', 'gcc' ); ?></h1>
			<?php settings_errors(); ?>
			<?php do_action( 'gcc_before_tabs' ); ?>
			<h2 class="nav-tab-wrapper">
				<?php
				foreach( $tabs as $index => $tab_name ) {
					?>
					<a href="admin.php?page=gcc&tab=<?php echo $tab_slug[$index]; ?>" class="nav-tab <?php echo $active_tab == $tab_slug[$index] ? 'nav-tab-active' : ''; ?>"><?php echo $tab_name; ?></a>
					<?php
				}
				?>
			</h2>
			<?php do_action( 'gcc_before_form' ); ?>
			<form method="post" class="form-wrap" action="admin.php?page=gcc&tab=<?php echo $active_tab; ?>">
				<?php do_action( 'gcc_start_form' ); ?>
				<table class="form-table" style="width: 100%;">
					<tbody>
						<?php do_action( 'gcc_form' ); ?>
						<tr>
							<td>
								<label for="gcc_names"><?php _e( 'Names', 'gcc' ); ?></label>
								<textarea name="gcc_names" style="width: 100%" rows="20"></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
							</td>
							
							<td>
								<label for="gcc_nicnames"><?php _e( 'Slugs', 'gcc' ); ?></label>
								<textarea name="gcc_nicnames" style="width: 100%;" rows="20"></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
								<p><?php _e( 'If you do not enter for any item, slug will be the name', 'gcc' ); ?></p>
							</td>
						</tr>
						
						<?php do_action( 'gcc_before_parent' ); ?>
						
						<tr>
							<td>
								<label style="display: inline;" for="gcc_parent"><?php _e( 'Parent' ); ?>:</label>
								<?php
								// Create dropdown list of all categories
								$dropdown_args = array(
									'hide_empty'       => 0,
									'hide_if_empty'    => false,
									'name'             => 'gcc_parent',
									'taxonomy'         => $taxonomy,
									'orderby'          => 'name',
									'hierarchical'     => true,
									'show_option_none' => __( 'None' ),
								);

								$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy, 'new' );

								wp_dropdown_categories( $dropdown_args );
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<?php do_action( 'gcc_end_form' ); ?>
				<?php submit_button(); ?>
			</form>
			<?php do_action( 'gcc_after_form' ); ?>
		</div>
		<?php
	}
}
// Engage class
gcc_menu::get_instance();