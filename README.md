# gcc

Some sites need to add so many categories. If you want to do this with category section of wordpress, Your time will waste.
Group category creator(GCC) is a tools for create so many categories in a short time. GCC support WordPress, WooCommerce and 'Easy Digital Downloads' categories.
When you want to create some categories, You can choose the parent for them and with one click, Your categories will be added.
GCC is a modular plugin. You can use filter and actions to improve it for your need.

#### One more thing about GCC

GCC uses i18n, So you can translate it for your language.

## Plugin APIs

### Actions

#### gcc_before_tabs - Show content above tabs
```php
<?php
add_action( 'gcc_before_tabs', 'before_tabs_function' );
function before_tabs_function() {
	echo 'Please select your post type';
}

```

#### gcc_before_form - This action work before form tag
```php
<?php
add_action( 'gcc_before_form', 'before_form_function' );
function before_form_function() {
	echo 'Please write your categories';
}

```

#### gcc_start_form - Work right after form tag
```php
<?php
add_action( 'gcc_start_form', 'start_form_function' );
function start_form_function() {
	echo 'Everything you need!';
}

```

#### gcc_form - This action is in form tag > table > tbody and right before first tr tag
For this action you need table tags( tr & td )
```php
<?php
add_action( 'gcc_form', 'form_function' );
function form_function() {
	?>
	<tr>
		<td>
			 <!-- Your inputs or other things --> 
			</td>
	</tr>
	<?php
}

```

#### gcc_before_parent - Work Above parent dropdown list
For this action you need table tags( tr & td )
```php
<?php
add_action( 'gcc_before_parent', 'before_parent_function' );
function before_parent_function() {
	?>
	<tr>
		<td>
			 <!-- Your inputs or other things --> 
			</td>
	</tr>
}

```

#### gcc_end_form - Work after table tag and before end form tag
```php
<?php
add_action( 'gcc_end_form', 'end_form_function' );
function end_form_function() {
	echo "<p>Now you can click on 'Save Changes' to create this categories</p>";
}
```

#### gcc_after_form - Work after form tag
```php
<?php
add_action( 'gcc_after_form', 'after_form_function' );
function after_form_function() {
	echo "<p>Made by: Mohammad Jafar Khajeh</p>";
}
```

### Filters

#### gcc_tabs_name - An array for tabs list
With this filter you can add or remove a tab.
##### After you add the tab you need to add tab slug by 'gcc_tabs_slugs' filter
```php
<?php
add_filter( 'gcc_tabs_name', 'add_custom_tab' );
function add_custom_tab( $tabs ) {
	$tabs[] = 'My Tab';
	return $tabs;
}
```

#### gcc_tabs_slug - An array of tabs slug
Before use this filter you should add tab with 'gcc_tabs_name'
```php
add_filter( 'gcc_tabs_slug', 'add_custom_tab_slug', 10, 2 );
function ( $tab_slug, $tab_name ) {
	if( $tab_name == 'My Tab' ) {
		$tab_slug[] = 'my-slug';
	}
	return $tab_slug;
}
```

#### gcc_default_active_tab - Choose default active tab
```php
<?php
add_filter( 'gcc_default_active_tab', 'default_tab' );
function default_tab( $active_tab ) {
	$active_tab = 'my-slug'; // Place the slug name
	return $active_tab;
}
```

#### gcc_taxonomies - An array of taxonomies
```php
<?php
add_filter( 'gcc_taxonomies', 'taxonomies', 10, 2 );
function taxonomies( $taxonomies, $tab_name ) {
	if( $tab_name == 'My Tab' ) {
		$taxonomies[] = 'my_tax';
	}
	return $taxonomies;
}
```

#### taxonomy_parent_dropdown_args - This filter is a wordpress core filters.
You can find it how you can use it.
