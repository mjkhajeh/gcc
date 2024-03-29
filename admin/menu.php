<?php
namespace mjgcc\Backend;
// This class will create HTML page in wp_admin and insert all category name and slugs entered in fields.

class gcc_menu {
	public static function get_instance() {
		static $instance = null;
		if( $instance === null ) {
			$instance = new self;
		}
		return $instance;
	}
	
	private function __construct() {
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
		
		$tabs[]		= __( "WordPress", 'gcc' );
		if( class_exists( 'Easy_Digital_Downloads' ) ) {
			$tabs[]	= __( "Easy Digital Downloads", 'gcc' );
		}
		if( class_exists( 'WooCommerce' ) ) {
			$tabs[]	= __( "WooCommerce", 'gcc' );
		}
		$tabs[] = __( "Custom taxonomy", 'gcc' );
		$tabs = apply_filters( 'gcc_tabs_name', $tabs );
		
		$tab_slug = array();
		foreach( $tabs as $tab_name ) {
			if( $tab_name == __( "WordPress", 'gcc' ) ) {
				$tab_slug[]	= "wordpress";
			} else if( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$tab_slug[]	= "edd";
			} else if( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$tab_slug[]	= "woo";
			} else if( $tab_name == __( "Custom taxonomy", 'gcc' ) ) {
				$tab_slug[] = 'custom';
			}
		}
		$tab_slug = apply_filters( 'gcc_tabs_slug', $tab_slug, $tab_name );
		
		// Default active tab
		$active_tab = 'wordpress';
		$active_tab = apply_filters( 'gcc_default_active_tab', $active_tab );
		if ( isset( $_GET['tab'] ) ) {
			$active_tab = $_GET['tab'];
		}

		if( $active_tab == 'custom' ) {
			wp_enqueue_script( 'gcc-scripts' );
		}
		
		$taxonomies = array();
		foreach( $tabs as $tab_name ) {
			if( $tab_name == __( "WordPress", 'gcc' ) ) {
				$taxonomies[]	= "category";
			} else if( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$taxonomies[]	= "download_category";
			} else if( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$taxonomies[]	= "product_cat";
			}
		}
		$taxonomies = apply_filters( 'gcc_taxonomies', $taxonomies, $tab_name );
		
		// Detect selected taxonomy
		$taxonomy = '';
		if( $active_tab != 'custom' ) {
			foreach( $tab_slug as $index => $slug ) {
				if ( $active_tab == $slug ) {
					$taxonomy = $taxonomies[$index];
					break;
				}
			}
		} else {
			if( !empty( $_POST['gcc_taxonomy'] ) || !empty( $_GET['taxonomy'] ) ) {
				if( !empty( $_POST['gcc_taxonomy'] ) ) {
					$taxonomy = sanitize_text_field( $_POST['gcc_taxonomy'] );	
				} else {
					$taxonomy = sanitize_text_field( $_GET['taxonomy'] );	
				}
				if( !taxonomy_exists( $taxonomy ) ) {
					$error = true;
					?>
					<div class="error notice is-dismissible">
						<p><?php _e( "Selected taxonomy is not exists", 'gcc' ) ?></p>
					</div>
					<?php
				}
			}
		}
		
		// Check if data send to insert
		if ( !empty( $_POST['gcc_names'] ) ) {
			// Convert to array
			$names = explode( PHP_EOL, $_POST['gcc_names'] );
			$nicknames = explode( PHP_EOL, $_POST['gcc_nicknames'] );
			
			$parent = $_POST['gcc_parent'];
			
			// Create a sorted array for categories & category slugs will be added
			$cats = array();
			foreach( $names as  $index => $name ) {
				if ( !$nicknames[$index] ) {
					$nicknames[$index] = $name;
				}
				$cats[$name] = $nicknames[$index];
			}
			
			// Start insert categories
			if( !$error ) {
				foreach( $cats as $name => $nickname ) {
					$id = wp_insert_term( $name, $taxonomy, array( 'slug'=>$nickname, 'parent'=>$parent ) );
					if ( is_wp_error( $id ) ) {
						$error = true;
						?>
						<div class="error notice is-dismissible">
							<p><?php $id->get_error_message(); ?></p>
						</div>
						<?php
						break;
					}
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
						<?php
						if( $active_tab == 'custom' ) {
							$wp_taxonomies = get_taxonomies( array(), "objects" );
							?>
							<tr>
								<td colspan="2">
									<label for="gcc_taxonomy"><?php _e( "Taxonomy", 'gcc' ) ?></label>
									<select name="gcc_taxonomy" id="gcc_taxonomy" class="postform regular-text">
										<option value="" disabled selected><?php _e( "Select taxonomy", 'gcc' ) ?></option>
										<?php foreach( $wp_taxonomies as $wp_taxonomy ) { ?>
											<option value="<?php echo $wp_taxonomy->name ?>" <?php selected( $wp_taxonomy->name, $taxonomy ) ?>><?php echo $wp_taxonomy->label ?></option>
										<?php } ?>
									</select>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td>
								<label for="gcc_names"><?php _e( 'Names', 'gcc' ); ?></label>
								<textarea name="gcc_names" style="width: 100%" rows="20" <?php echo empty( $taxonomy ) ? 'disabled' : '' ?>></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
							</td>
							
							<td>
								<label for="gcc_nicknames"><?php _e( 'Slugs', 'gcc' ); ?></label>
								<textarea name="gcc_nicknames" style="width: 100%;" rows="20" <?php echo empty( $taxonomy ) ? 'disabled' : '' ?>></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
								<p><?php _e( 'If you do not enter for any item, slug will be the name', 'gcc' ); ?></p>
							</td>
						</tr>
						
						<?php do_action( 'gcc_before_parent' ); ?>
						<?php if( !empty( $taxonomy ) && get_taxonomy( $taxonomy )->hierarchical ) { ?>
							<tr>
								<td>
									<label style="display: inline;" for="gcc_parent"><?php _e( 'Parent', 'gcc' ); ?>:</label>
									<?php
									// Create dropdown list of all categories
									$dropdown_args = array(
										'hide_empty'       => 0,
										'hide_if_empty'    => false,
										'name'             => 'gcc_parent',
										'taxonomy'         => $taxonomy,
										'orderby'          => 'name',
										'hierarchical'     => true,
										'show_option_none' => __( 'None', 'gcc' ),
									);

									$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy, 'new' );

									wp_dropdown_categories( $dropdown_args );
									?>
								</td>
							</tr>
						<?php } ?>
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
gcc_menu::get_instance();