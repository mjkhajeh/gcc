<?php
namespace mjgcc\Backend;

// This class will create HTML page in wp_admin and insert all category name and slugs entered in fields.
class Menu {
	PRIVATE $SUCCESS = array();
	PRIVATE $ERROR = array();
	PRIVATE $TABS = array();
	PRIVATE $TABS_SLUG = array();
	PRIVATE $ACTIVE_TAB = "";
	PRIVATE $TAXONOMIES = array();
	PRIVATE $CURRENT_TAXONOMY = "";
	PRIVATE $MAIN_PARENT = 0;

	public static function get_instance() {
		static $instance = null;
		if( $instance === null ) {
			$instance = new self;
		}
		return $instance;
	}
	
	private function __construct() {
		add_action( 'admin_init', array( $this, 'before_page_load' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	private function prepare_categories( $categories, $category = array(), $parent_slug = "" ) {
		if( $parent_slug !== "" && $parent_slug !== NULL ) {
			$categories['childs'][] = $category;
			if( strpos( $category['name'], "-" ) === 0 ) {
				$category_index = array_search( $category, $categories['childs'] );
				unset( $categories['childs'][$category_index] );
				$categories['childs'] = array_values( $categories['childs'] );
				$category['name'] = trim( substr( $category['name'], 1 ) );
				foreach( $categories['childs'] as $index => $child ) {
					if( strpos( $category['name'], "-" ) !== 0 ) {
						break;
					}
				}
				$categories['childs'][$index] = $this->prepare_categories( $categories['childs'][$index], $category, $index );
			}
		} else {
			$last_main_category_slug = "";
			foreach( $categories as $slug => $category ) {
				if( strpos( $category['name'], "-" ) === 0 ) {
					$category['name'] = trim( substr( $category['name'], 1 ) );
					unset( $categories[$slug] );
					$categories[$last_main_category_slug] = $this->prepare_categories( $categories[$last_main_category_slug], $category, $last_main_category_slug );
				} else {
					$last_main_category_slug = $slug;
				}
			}
		}
		return $categories;
	}

	private function term_creator( $category, $parent_id = 0 ) {
		if( $parent_id === 0 ) {
			$parent_id = $this->MAIN_PARENT;
		}
		$term = wp_insert_term( $category['name'], $this->CURRENT_TAXONOMY, array( 'slug' => $category['slug'], 'parent' => $parent_id ) );
		if( is_wp_error( $term ) ) {
			$this->ERROR[] = "{$name} : " . $term->get_error_message();
			return $term;
		}
		if( !empty( $category['childs'] ) ) {
			foreach( $category['childs'] as $child ) {
				$this->term_creator( $child, $term['term_id'] );
			}
		}
		return $term;
	}

	public function before_page_load() {
		if( empty( $_GET['page'] ) || $_GET['page'] != "gcc" ) return;

		// Tabs list
		$tabs = array();
		$tabs[]		= __( "WordPress", 'gcc' );
		if( class_exists( 'Easy_Digital_Downloads' ) ) {
			$tabs[]	= __( "Easy Digital Downloads", 'gcc' );
		}
		if( class_exists( 'WooCommerce' ) ) {
			$tabs[]	= __( "WooCommerce", 'gcc' );
		}
		$tabs[]		= __( "Custom taxonomy", 'gcc' );
		$this->TABS	= apply_filters( 'gcc_tabs_name', $tabs );
		
		$tabs_slug = array();
		foreach( $this->TABS as $tab_name ) {
			if( $tab_name == __( "WordPress", 'gcc' ) ) {
				$tabs_slug[]	= "wordpress";
			} else if( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$tabs_slug[]	= "edd";
			} else if( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$tabs_slug[]	= "woo";
			} else if( $tab_name == __( "Custom taxonomy", 'gcc' ) ) {
				$tabs_slug[] = 'custom';
			}
			$tabs_slug = apply_filters( 'gcc_tabs_slug', $tabs_slug, $tab_name );
		}
		$this->TABS_SLUG = $tabs_slug;

		// Default active tab
		$active_tab = 'wordpress';
		$active_tab = apply_filters( 'gcc_default_active_tab', $active_tab );
		if ( isset( $_GET['tab'] ) ) {
			$active_tab = sanitize_text_field( $_GET['tab'] );
		}
		$this->ACTIVE_TAB = $active_tab;

		// Get active taxonomies
		$taxonomies = array();
		foreach( $this->TABS as $tab_name ) {
			if( $tab_name == __( "WordPress", 'gcc' ) ) {
				$taxonomies[]	= "category";
			} else if( $tab_name == __( "Easy Digital Downloads", 'gcc' ) ) {
				$taxonomies[]	= "download_category";
			} else if( $tab_name == __( "WooCommerce", 'gcc' ) ) {
				$taxonomies[]	= "product_cat";
			}
			$taxonomies = apply_filters( 'gcc_taxonomies', $taxonomies, $tab_name );
		}
		$this->TAXONOMIES = $taxonomies;

		// Detect selected taxonomy
		$current_taxonomy = '';
		if( $this->ACTIVE_TAB != 'custom' ) {
			foreach( $this->TABS_SLUG as $index => $slug ) {
				if ( $this->ACTIVE_TAB == $slug ) {
					$current_taxonomy = $this->TAXONOMIES[$index];
					break;
				}
			}
		} else {
			if( !empty( $_POST['gcc_taxonomy'] ) || !empty( $_GET['taxonomy'] ) ) {
				if( !empty( $_POST['gcc_taxonomy'] ) ) {
					$current_taxonomy = sanitize_text_field( $_POST['gcc_taxonomy'] );
				} else {
					$current_taxonomy = sanitize_text_field( $_GET['taxonomy'] );	
				}
				if( !taxonomy_exists( $current_taxonomy ) ) {
					$this->ERROR[] = __( "Selected taxonomy is not exists", 'gcc' );
					return;
				}
			}
		}
		$this->CURRENT_TAXONOMY = $current_taxonomy;

		// Insert categories
		if( empty( $_POST['gcc_names'] ) ) return;

		// Convert to array
		$names = explode( PHP_EOL, $_POST['gcc_names'] );
		$nicknames = explode( PHP_EOL, $_POST['gcc_nicknames'] );
		
		$this->MAIN_PARENT = sanitize_text_field( $_POST['gcc_parent'] );
		
		// A sorted array for categories & slugs will be created
		$categories = array();
		foreach( $names as $index => $name ) {
			$name = sanitize_text_field( $name );
			if( empty( $name ) || !$name ) continue;

			// Create nickname from name
			if( empty( $nicknames[$index] ) || !$nicknames[$index] ) {
				$nicknames[$index] = $name;
			}
			$nicknames[$index] = sanitize_title( $nicknames[$index] );

			// Store result
			$categories[] = array(
				'name'	=> $name,
				'slug'	=> $nicknames[$index],
			);
		}
		$categories = $this->prepare_categories( $categories );
		$categories = array_values( $categories );

		// Start insert categories
		foreach( $categories as $category ) {
			if( is_wp_error( $this->term_creator( $category ) ) ) {
				return;
			}
		}

		if( empty( $this->ERROR ) ) {
			$this->SUCCESS[] = __( 'All categories added.', 'gcc' );
		}
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
		// Convert object vars to local vars
		$tabs				= $this->TABS;
		$tabs_slug			= $this->TABS_SLUG;
		$active_tab			= $this->ACTIVE_TAB;
		$taxonomies			= $this->TAXONOMIES;
		$current_taxonomy	= $this->CURRENT_TAXONOMY;

		if( $active_tab == 'custom' ) {
			wp_enqueue_script( 'gcc' );
		}
		wp_enqueue_style( 'gcc' );
		
		// Get all categories
		$categories = get_terms( $current_taxonomy, array( 'hide_empty' => 0 ) );
		
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
					<a href="admin.php?page=gcc&tab=<?php echo $tabs_slug[$index]; ?>" class="nav-tab <?php echo $active_tab == $tabs_slug[$index] ? 'nav-tab-active' : ''; ?>"><?php echo $tab_name; ?></a>
					<?php
				}
				?>
			</h2>
			<?php do_action( 'gcc_before_form' ); ?>
			<form method="post" class="form-wrap" action="admin.php?page=gcc&tab=<?php echo $active_tab; ?>">
				<?php do_action( 'gcc_start_form' ); ?>
				<table class="form-table" style="width:100%" id="gcc_table">
					<tbody>
						<?php do_action( 'gcc_form' ); ?>
						<?php
						if( $active_tab == 'custom' ) {
							$wp_taxonomies = get_taxonomies( array(), "objects" );
							$ignored_taxonomies = array( 'nav_menu', 'link_category', 'post_format', 'wp_theme' );
							$ignored_taxonomies = apply_filters( 'gcc_ignored_custom_taxonomies', $ignored_taxonomies );
							$ignored_taxonomies = array_flip( $ignored_taxonomies );
							$wp_taxonomies = array_diff_key( $wp_taxonomies, $ignored_taxonomies );
							?>
							<tr>
								<td colspan="2">
									<label for="gcc_taxonomy"><?php _e( "Taxonomy", 'gcc' ) ?></label>
									<select name="gcc_taxonomy" id="gcc_taxonomy" class="postform regular-text">
										<option value="" disabled selected><?php _e( "Select taxonomy", 'gcc' ) ?></option>
										<?php foreach( $wp_taxonomies as $wp_taxonomy ) { ?>
											<option value="<?php echo $wp_taxonomy->name ?>" <?php selected( $wp_taxonomy->name, $current_taxonomy ) ?>><?php echo $wp_taxonomy->label ?></option>
										<?php } ?>
									</select>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td>
								<label for="gcc_names"><?php _e( 'Names', 'gcc' ); ?></label>
								<textarea name="gcc_names" style="width: 100%" rows="20" <?php echo empty( $current_taxonomy ) ? 'disabled' : '' ?>></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
							</td>
							
							<td>
								<label for="gcc_nicknames"><?php _e( 'Slugs', 'gcc' ); ?></label>
								<textarea name="gcc_nicknames" style="width: 100%;" rows="20" <?php echo empty( $current_taxonomy ) ? 'disabled' : '' ?>></textarea>
								<p><?php _e( 'Enter in each line for a category.', 'gcc' ); ?></p>
								<p><?php _e( 'If you do not enter for any item, slug will be the name', 'gcc' ); ?></p>
							</td>
						</tr>
						
						<?php do_action( 'gcc_before_parent' ); ?>
						<?php if( !empty( $current_taxonomy ) && get_taxonomy( $current_taxonomy )->hierarchical ) { ?>
							<tr>
								<td>
									<label style="display: inline;" for="gcc_parent"><?php _e( 'Parent', 'gcc' ); ?>:</label>
									<?php
									// Create dropdown list of all categories
									$dropdown_args = array(
										'hide_empty'       => 0,
										'hide_if_empty'    => false,
										'name'             => 'gcc_parent',
										'taxonomy'         => $current_taxonomy,
										'orderby'          => 'name',
										'hierarchical'     => true,
										'show_option_none' => __( 'None', 'gcc' ),
									);

									$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $current_taxonomy, 'new' );

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

	public function notices() {
		$screen = get_current_screen();
		if( $screen->parent_file != "gcc" ) return;

		if( empty( $this->SUCCESS ) && empty( $this->ERROR ) ) return;
		
		if( !empty( $this->SUCCESS ) ) {
			$success = implode( "<br>", $this->SUCCESS );
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $success ?></p>
			</div>
			<?php
		}

		if( !empty( $this->ERROR ) ) {
			$error = implode( "<br>", $this->ERROR );
			?>
			<div class="notice notice-error">
				<p><?php echo $error ?></p>
			</div>
			<?php
		}
	}
}
Menu::get_instance();