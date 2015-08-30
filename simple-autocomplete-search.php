<?php

/*
  Plugin Name: Simple Autocomplete Search
  Plugin URI:
  Description: An autocomplete plugin for WordPress using Jquery
  Author: Don Price
  Version: 1.0
  Author URI: http://dlp3.me
 */

add_action( 'wp_enqueue_scripts', 'load_jquery' );

function load_jquery() {
wp_enqueue_script( 'jquery' );
wp_enqueue_script( 'jquery-ui-core' );
wp_enqueue_script( 'jquery-ui-autocomplete' );
}

add_action('wp_footer', 'autocomplete_search');

function autocomplete_search() { ?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#searchbt').autocomplete({
					source : '<?php echo plugins_url("search.php", __FILE__) ?>',
					minLength : 3,
					});
		});
	</script>
	<?php
}

// Function to create new database table
function create_table( $prefix ) {
	// Prepare SQL query to create database table
	// using received table prefix
	$creation_query =
		'CREATE TABLE ' . $prefix . 'population (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`location` varchar(150) NOT NULL,
			`slug` varchar(150) NOT NULL,
			`population` int(10) unsigned NOT NULL,
			PRIMARY KEY (`id`),
			KEY `state_slug` (`slug`)
			);';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $creation_query );
}

// Register function to be called when admin menu is constructed
add_action( 'admin_menu', 'settings_menu' );

// Add new menu item under Settings menu for Bug Tracker
function settings_menu() {
	add_options_page( 'City Population Data Management',
		'City Population', 'manage_options',
		'jquery-autocomplete-search',
		'city_population_config_page' );
}

// Function to render plugin admin page
function city_population_config_page() {
	global $wpdb;
	?>
	<!-- Top-level menu -->
	<div id="city-general" class="wrap">
	<h2>City Population</h2>

	<!-- Form to upload new bugs in csv format -->
	<form method="post"
          action="<?php echo admin_url( 'admin-post.php' ); ?>"
          enctype="multipart/form-data">

	<input type="hidden" name="action" value="import_city_population" />

	<!-- Adding security through hidden referrer field -->
	<?php wp_nonce_field( 'city_import' ); ?>

	<h3>Import Data</h3>
	Import Data from CSV File
	(<a href="<?php echo plugins_url( 'data.csv', __FILE__ ); ?>">Template</a>)
	<input name="importcitydata" type="file" /> <br /><br />

	<input type="submit" value="Import" class="button-primary"/>

	</form>

	<?php } ?>
	</div>
<?php

// Register function to be called when administration pages init takes place
add_action( 'admin_init', 'autocomplete_admin_init' );

// Register functions to be called when bugs are saved
function autocomplete_admin_init() {
	add_action('admin_post_import_city_population',
		'import_city_population');
}


// Function to be called when importing bugs
function import_city_population() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present
	check_admin_referer( 'city_import' );

	// Check if file has been upladed
	if( array_key_exists( 'importcitydata', $_FILES ) ) {
		// If file exists, open it in read mode
		$handle = fopen( $_FILES['importcitydata']['tmp_name'], 'r' );

		// If file is successfully open, extract a row of csv data
		// based on comma separator, and store in $data array
		if ( $handle ) {
			while ( ( $data = fgetcsv( $handle, 5000, ',' ) ) !== FALSE ) {
				$row += 1;

				// If row count is accurate and row is not header row
				// Create array and insert in database
				if ( count( $data ) == 3 && $row != 1 ) {
					$new_bug = array( 'location' => $data[0],
							'slug' => $data[1],
							'population' => $data[2]);

					global $wpdb;

					$wpdb->insert( $wpdb->get_blog_prefix() . "population", $new_bug );
				}
			}
		}
	}

	// Redirect the page to the admin page
	wp_redirect( add_query_arg( 'page', 'jquery-autocomplete-search', admin_url( 'options-general.php' ) ) );
	exit;
}

// Define new shortcode and specify function to be called when found
add_shortcode( 'city-pop-search', 'city_shortcode_search' );

// Shortcode implementation function
function city_shortcode_search() {
	global $wpdb;

	// Check if search string is in address
	if ( !empty( $_GET['searchbt'] ) ) {
		$search_string = $_GET['searchbt'];
		$search_mode = true;
	} else {
		$search_string = 'Search...';
		$search_mode = false;
	}



// Add search string in query if present
if ( $search_mode ) {
	$search_term = '%' . $search_string . '%';
} else {
			$search_term = '';
	}

$search_query = $wpdb->get_results( $wpdb->prepare( 'SELECT location, id, slug, population FROM wp_population
WHERE location LIKE "%s" ORDER BY population LIMIT 0,10', $search_term ), ARRAY_A );

	// Prepare output to be returned to replace shortcode
	$output = '';

	$output .= '<form method="get" id="city_population_search">';
	$output .= '<div>Search A City ';
	$output .= '<input type="text" onfocus="this.value=\'\'" ';
	$output .= 'value="' . esc_attr( $search_string ) . '" id="searchbt" name="searchbt" />';
	$output .= '<input type="submit" value="Search" />';
	$output .= '</div>';
	$output .= '</form><br />';

	$output .= '<div class="city_listing">';
	$output .= '<table>';

	// Check if any bugs were found
	if ( $search_query ) {
		$output .= '<tr><th style="width: 80px">ID</th>';
		$output .= '<th style="width: 300px">Location</th>';
		$output .= '<th>slug</th>';
		$output .= '<th>population</th></tr>';

		// Create row in table for each bug
		foreach ( $search_query as $query ) {
			$output .= '<tr style="background: #FFF">';
			$output .= '<td>' . $query['id'] . '</td>';
			$output .= '<td>' . $query['location'] . '</td>';
			$output .= '<td>' . $query['slug'] . '</td>';
			$output .= '<td>' . $query['population'] . '</td></tr>';
		}
	} else {
		// Message displayed if no bugs are found
		$output .= '<tr style="background: #FFF">';
	}

	$output .= '</table></div><br />';

	// Return data prepared to replace shortcode on page/post
	return $output;
}

?>
