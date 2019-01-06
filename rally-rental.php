<?php

/*
  Plugin Name: Rally Rental
  Plugin URI: rallyrentalph.com
  Description: Plugin for Rally Rental Website
  Version: 1.0
  Author: John Oblenda
  Author URI: https://joblenda.com/
  License: GPLv2 or later
 */


 /**
 *  Abort if file is called directly
 */
defined( 'ABSPATH' ) or die( 'Disabled access' );


/**
 *  Constants
 */
define( 'PLUGIN_DIR_PATH_RR', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_URL_RR', plugins_url() );
define( 'PLUGIN_VERSION_RR', '1.0' );


/**
 *  Includes
 */
include_once PLUGIN_DIR_PATH_RR.'/rallyrental-listtable.php';
date_default_timezone_set('Asia/Manila');


/**
 *  Plugin Activation
 */
register_activation_hook( __FILE__ , 'activate_rallyrental_plugin' );
function activate_rallyrental_plugin() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'rallyrental';
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		start_date date NOT NULL,
		end_date date NOT NULL,
		time time DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name varchar(169) NOT NULL,
		email varchar(169) NOT NULL,
		phone varchar(169) NOT NULL,
		pick_up varchar(169) NOT NULL,
		drop_off varchar(169) NOT NULL,
		trip_type varchar(169) NOT NULL,
		car int(10) NOT NULL,
		invoice varchar(169) DEFAULT 'Not Paid' NOT NULL,
		payment varchar(169) NOT NULL,
		status int(10) DEFAULT '0' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	flush_rewrite_rules();
}


/**
 *  Plugin Deactivation
 */
register_deactivation_hook( __FILE__ , 'deactivate_rallyrental_plugin' );
function deactivate_rallyrental_plugin() {
	flush_rewrite_rules();
}


/**
 *  Uninstall plugin
 */
register_uninstall_hook( __FILE__, 'rallyrental_uninstall' );
function rallyrental_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rallyrental';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    flush_rewrite_rules();
}


/**
 *  Enqueue Admin Scripts
 */
add_action( 'admin_enqueue_scripts', 'admin_enqueue_scripts');
function admin_enqueue_scripts() {
	if(get_current_screen()->base == 'toplevel_page_rallyrental-rentals') {
		wp_enqueue_style( 'rallyrental-fontawesome', 'https://use.fontawesome.com/releases/v5.0.6/css/all.css');
		wp_enqueue_style( 'rallyrental-bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css');
		wp_enqueue_style( 'rallyrental-calendar', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css');
		wp_enqueue_style( 'rallyrental-calendarPrint', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.print.css', array(), false, 'print');
		wp_enqueue_style( 'rallyrental-styles', plugins_url() . '/rally-rental/css/styles.css'); 
		wp_enqueue_script('rallyrental-popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js', array('jquery'), '', true);  
		wp_enqueue_script('rallyrental-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js', array('jquery'), '', true);
		wp_enqueue_script('rallyrental-moment', plugins_url() . '/rally-rental/js/moment.min.js', array('jquery'), '', true);  
		wp_enqueue_script('rallyrental-calendar', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js', array('jquery'), '', true); 
		wp_enqueue_script('rallyrental-admin-script', plugins_url() . '/rally-rental/js/admin-script.js', array('jquery'), '', true); 
		wp_localize_script( 'rallyrental-admin-script', 'rallyrental_ajax', array( 'ajax_url' => admin_url('admin-ajax.php' ) ) );
	}
}


/**
 *  Menu & Sub-menu
 */
add_action( 'admin_menu', 'add_rallyrental_menu' );
function add_rallyrental_menu() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rallyrental';
	$row_count = $wpdb->get_var( "
		SELECT COUNT(*) FROM $table_name
		WHERE status = 0 " );
	add_menu_page('Rally Rentals',  $row_count ? sprintf('Rally Rentals <span class="update-plugins">%d</span>', $row_count) : 'Rally Rentals', 'manage_options', 'rallyrental-rentals', '');
	add_submenu_page( 'rallyrental-rentals', 'Rally Rental Rentals', 'Rentals', 'manage_options','rallyrental-rentals', 'rallyrental_rentals' );
	add_submenu_page( 'rallyrental-rentals', 'Rally Rental Cars', 'Cars', 'manage_options', 'edit.php?post_type=car', NULL);
	//add_submenu_page( 'rallyrental-rentals', 'Rally Rental Settings', 'Settings', 'manage_options','rallyrental-settings', 'rallyrental_settings' );
}


/**
 *  Calendar Events
 */
function load_calendar_events($status) {
	global $wpdb;
    $table_name = $wpdb->prefix . 'rallyrental';
    $results = $wpdb->get_results( "SELECT * FROM `$table_name` WHERE `status` = $status" );
    //var_dump($results);die();
    $file = ($status == 0) ? PLUGIN_DIR_PATH_RR . '/entries/processing-entries.json' : PLUGIN_DIR_PATH_RR . '/entries/approved-entries.json';
    $entries = [];
    foreach ($results as $result) {
    	$ent = [];
    	$post_car = get_post( $result->car );
    	$ent['title'] = $post_car->post_title;
    	$ent['start'] = $result->start_date.'T'.$result->time;
    	$ent['end'] = $result->end_date.'T23:59:00';
    	$ent['allDay'] = false;
    	array_push($entries, $ent);
    }
    $json_data = json_encode($entries, JSON_PRETTY_PRINT);
	$file_w = fopen($file,"w");
	fwrite($file_w, $json_data);
	fclose($file_w);
}


/**
 *  Call Events on admin load
 */
add_action( 'admin_init', 'rallyrental_data_calendar');
function rallyrental_data_calendar() {
	ob_start();
	$labels = array(
		'name'               => _x( 'Cars', 'post type general name', 'your-plugin-textdomain' ),
		'singular_name'      => _x( 'Car', 'post type singular name', 'your-plugin-textdomain' ),
		'menu_name'          => _x( 'Cars', 'admin menu', 'your-plugin-textdomain' ),
		'name_admin_bar'     => _x( 'Car', 'add new on admin bar', 'your-plugin-textdomain' ),
		'add_new'            => _x( 'Add New', 'car', 'your-plugin-textdomain' ),
		'add_new_item'       => __( 'Add New Car', 'your-plugin-textdomain' ),
		'new_item'           => __( 'New Car', 'your-plugin-textdomain' ),
		'edit_item'          => __( 'Edit Car', 'your-plugin-textdomain' ),
		'view_item'          => __( 'View Car', 'your-plugin-textdomain' ),
		'all_items'          => __( 'Cars', 'your-plugin-textdomain' ),
		'search_items'       => __( 'Search Cars', 'your-plugin-textdomain' ),
		'parent_item_colon'  => __( 'Parent Cars:', 'your-plugin-textdomain' ),
		'not_found'          => __( 'No cars found.', 'your-plugin-textdomain' ),
		'not_found_in_trash' => __( 'No cars found in Trash.', 'your-plugin-textdomain' )
	);
	$args = array(
		'labels'             => $labels,
        'description'        => __( 'Description.', 'your-plugin-textdomain' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'car' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'register_meta_box_cb' => 'rallyrental_car_specs_metaboxes',
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	);
	register_post_type( 'car', $args );
	load_calendar_events(0);
	load_calendar_events(1);
}


/**
 * Adds a metabox for car post type
 */
function rallyrental_car_specs_metaboxes() {
	add_meta_box(
		'rallyrental_car_specs',
		'Specifications',
		'rallyrental_car_specs',
		'car',
		'side',
		'default'
	);
}
function rallyrental_car_specs( $post ) {
	global $post;
	wp_nonce_field( basename( __FILE__ ), 'car_specs_nonce' );
    $car_specs_stored_meta = get_post_meta( $post->ID ); 
    // Output the field ?>
    <div class="form-group">
        <label for="car_model"><?php _e( 'Yr Model', 'rally-rental' )?></label><br>
        <input type="text" name="car_model" id="car_model" value="<?php if ( isset ( $car_specs_stored_meta['car_model'] ) ) echo $car_specs_stored_meta['car_model'][0]; ?>" />
    </div>
    <div class="form-group">
		<label for="car_type">Type</label><br>
		<select name="car_type" id="car_type" class="form-control">
	        <option value="Sedan" <?php if ( isset ( $car_specs_stored_meta['car_type'] ) ) selected( $car_specs_stored_meta['car_type'][0], 'Sedan' ); ?>>Sedan</option>
	        <option value="SUV" <?php if ( isset ( $car_specs_stored_meta['car_type'] ) ) selected( $car_specs_stored_meta['car_type'][0], 'SUV' ); ?>>SUV</option>
	        <option value="Mini-van" <?php if ( isset ( $car_specs_stored_meta['car_type'] ) ) selected( $car_specs_stored_meta['car_type'][0], 'Mini-van' ); ?>>Mini Van</option>
	    </select>
	</div>
	<div class="form-group">
		<label for="car_transmission">Transmission</label><br>
		<select name="car_transmission" id="car_transmission" class="form-control">
	        <option value="Manual" <?php if ( isset ( $car_specs_stored_meta['car_transmission'] ) ) selected( $car_specs_stored_meta['car_transmission'][0], 'Manual' ); ?>>Manual</option>
	        <option value="Automatic" <?php if ( isset ( $car_specs_stored_meta['car_transmission'] ) ) selected( $car_specs_stored_meta['car_transmission'][0], 'Automatic' ); ?>>Automatic</option>
	    </select>
	</div>
	<div class="form-group">
        <label for="car_seats"><?php _e( 'No. of Seats', 'rally-rental' )?></label><br>
        <input type="text" name="car_seats" id="car_seats" value="<?php if ( isset ( $car_specs_stored_meta['car_seats'] ) ) echo $car_specs_stored_meta['car_seats'][0]; ?>" />
    </div>
<?php 
}


/**
 * Saves the custom meta input
 */
add_action( 'save_post', 'rallyrental_carSpecs_save' );
function rallyrental_carSpecs_save( $post_id ) {
    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'car_specs_nonce' ] ) && wp_verify_nonce( $_POST[ 'car_specs_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }	
    // Checks for input and sanitizes/saves if needed
    if( isset( $_POST[ 'car_model' ] ) ) {
        update_post_meta( $post_id, 'car_model', sanitize_text_field( $_POST[ 'car_model' ] ) );
    }
 	if( isset( $_POST[ 'car_type' ] ) ) {
        update_post_meta( $post_id, 'car_type', sanitize_text_field( $_POST[ 'car_type' ] ) );
    }
    if( isset( $_POST[ 'car_transmission' ] ) ) {
        update_post_meta( $post_id, 'car_transmission', sanitize_text_field( $_POST[ 'car_transmission' ] ) );
    }
    if( isset( $_POST[ 'car_seats' ] ) ) {
        update_post_meta( $post_id, 'car_seats', sanitize_text_field( $_POST[ 'car_seats' ] ) );
    }
}


/**
 *  Rentals List Table
 */
add_filter( 'wp_mail_content_type', 'set_html_content_type' );
function set_html_content_type() {
	return 'text/html';
}

function rallyrental_rentals() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'rallyrental';
	$query = new WP_Query(array(
	    'post_type' => 'car',
	    'post_status' => 'publish',
	    'posts_per_page' => -1,
	));
	$cars = $query->posts;
	// notifications via sessions - for delete with wp_safe_redirect :(
	session_start();
	if (isset($_SESSION['message'])){ ?>
		<div class="container">
			<div class="row">
				<div class="col-md-12 col-xs-12">
	   				<div class="notifications"><p><?php echo $_SESSION['message']; ?></p></div>
	   			</div>
	   		</div>
	   	</div>
	  	<?php unset($_SESSION['message']); // delete the message
	}
	?>
	<div id="data_Modal" class="modal fade">  
      	<div class="modal-dialog modal-dialog-centered" role="document">>  
           	<div class="modal-content">  
                <div class="modal-body">  
                	<div id="notification"></div>
                    <form name="rallyrental" method="post" id="rallyrental-form">
                    	<?php wp_nonce_field(); ?>
						<input type="hidden" name="rental_id">
						<div class="row">
							<div class="col-md-6 col-xs-12">
								<div class="form-group">
									<label for="name">Full Name:</label>
									<input type="text" name="name" id="name" class="form-control"/>
								</div>
								<div class="form-group">
									<label for="name">Email Address:</label>
									<input type="email" name="email" class="form-control"/>
								</div>
								<div class="form-group">
									<label for="phone">Phone Number:</label>
									<input type="text" name="phone" class="form-control"/>
								</div>
								<div class="form-group">
									<label for="trip_type"/>Trip Type:</label><br>
									<input type="radio" name="trip_type" id="trip_type1" value="one_way"> One Way<br>
									<input type="radio" name="trip_type" id="trip_type2" value="roundtrip"> Round Trip<br> 
								</div>
								<div class="form-group">
									<label for="pick_up">Pick Up:</label>
									<select name="pick_up" id="pick-up" class="form-control">
										<option value="">Select Pick up city..</option>
										<option value="iligan">Iligan</option>
										<option value="cagayandeoro">Cagayan De Oro</option>
									</select>
								</div>
								<div class="form-group">
									<label for="drop_off">Drop Off:</label>
									<select name="drop_off" id="drop-off" class="form-control">
										<option value="">Select Drop off city..</option>
										<option value="iligan" >Iligan</option>
										<option value="cagayandeoro">Cagayan De Oro</option>
										<option value="pagadian">Pagadian City</option>
										<option value="davao">Davao City</option>
										<option value="ozamis">Ozamis City</option>
										<option value="dipolog">Dipolog City</option>
										<option value="surigao">Surigao City</option>
									</select>
								</div>
							</div>
							<div class="col-md-6 col-xs-12">
								<div class="form-group">
									<label for="date">Date:</label>
									<input type="date" name="date" class="form-control" />
								</div>
								<div class="form-group end_date">
									<label for="date">End Date:</label>
									<input type="date" name="end_date" class="form-control"/>
								</div>
								<div class="form-group">
									<label for="time">Time:</label>
									<input type="time" name="time" class="form-control"/>
								</div>
								<div class="form-group">
									<label for="car">Car:</label>
									<select name="car" id="car" class="form-control">
										<?php foreach($cars as $car) : ?>
											<option value="<?php echo $car->ID; ?>"><?php echo $car->post_title; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-group">
									<label for="payment"/>Payment:</label><br>
									<input type="radio" name="payment" id="payment1" value="COS" required> Cash On Service<br>
									<input type="radio" name="payment" id="payment2" value="bank_transfer" required> Bank Transfer<br> 
								</div>
								<div class="form-group">
									<label for="invoice">Invoice:</label>
									<textarea name="invoice" class="form-control"></textarea>
								</div>
								<div class="form-group">
									<label for="status">Status:</label>
									<select name="status" id="status" class="form-control">
										<option value="0">Processing</option>
										<option value="1">Approved</option>
										<option value="2">Completed</option>
									</select>
								</div>
								<div class="form-group">
									<input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Submit">
								</div>
							</div>
						</div>
					</form> 
                </div>  
	            <div class="modal-footer">  
	                 <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>  
	            </div>  
	           	</div>  
	    </div>  
	</div>
	<div class="container">
	    <div class="row">
		    <div class="col-md-12 col-xs-12">
			<?php 
			// add & updated
			if( isset( $_POST['submit'] ) && isset( $_POST['_wpnonce'] ) ) {
		        $name = $_POST['name'];
		        $email = $_POST['email'];
		        $phone = $_POST['phone'];
		        $trip_type = $_POST['trip_type'];
		        $pick_up = $_POST['pick_up'];
		    	$drop_off = $_POST['drop_off'];
		        $start_date = $_POST['date'];
		    	$end_date = ($_POST['end_date']=="") ? $start_date : $_POST['end_date'];
		    	$time = $_POST['time'];
		    	$car_id = $_POST['car'];    	 	
		    	$payment = $_POST['payment'];
		    	$invoice = $_POST['invoice'];
		    	$status = $_POST['status'];
		    	if( isset( $_POST['rental_id'] ) && $_POST['rental_id']  != null ) {
		    		$id = $_POST['rental_id'];
		    		if( $trip_type=='one_way' ) $end_date = $start_date;
		    		$result = $wpdb->query(
						$wpdb->prepare("UPDATE `$table_name` SET 
							`name` = '$name',
							`email` = '$email',
							`phone` = '$phone',
							`trip_type` = '$trip_type',
							`pick_up` = '$pick_up',
							`drop_off` = '$drop_off',
							`start_date` = '$start_date',
							`end_date` = '$end_date',
							`time` = '$time',
							`car` = '$car_id',
							`payment` = '$payment',
							`invoice` = '$invoice',
							`status` = '$status' 
							WHERE `$table_name`.`id` = %d", $id)
						);
		    		if( $result ) { 
		    			//php mailer variables
						//$to = get_option('admin_email');
						if( $status == 1 ) {
							$subject = "Rally Rental Reservation Confirmation #".$_POST['rental_id'];
							$headers = 'From: '. get_option('admin_email') . "\r\n" . 'Reply-To: ' . get_option('admin_email') . "\r\n";
							$message = "<html><head></head><body>";
							$message .= "<p style='font-size:16px;'>Greetings! ".$name."</p>";
							$message .= "<p style='font-size:14px;'>Thank you for trusting Rally Rental. This is to confirm your trip for ".$drop_off." this coming ".$start_date.". Please be guided we will arrive 30 mins before ".$time." in your address. If you have any questions/concerns please don't hesitate to contact me directly. See you soon.</p>";
							$message .= "<p>Ralph Oblenda<br>Owner, Rally Rental<br>tel: +2945465198<br>email: ralphoblenda@me.com</p>";
							$message .= "<img src='".site_url()."/wp-content/uploads/2019/01/rally2.png' alt='Rally Rental' />";
							$message .= "</body></html>";
							$sent = wp_mail($email, $subject, $message, $headers);
						}
						$_SESSION['message'] = "Updated.."; 
		    		} 
		    	}
		    	if( $_POST['rental_id']  == "" ) {
		    		$data = array(
		    			'name' => $name,
		    			'email' => $email,
				        'phone' => $phone,
				        'trip_type' => $trip_type,
				        'pick_up' => $pick_up,
				    	'drop_off' => $drop_off,
				    	'start_date' => $start_date,
				    	'end_date' => $end_date,
				    	'time' => $time,
				    	'car' => $car_id,
				    	'payment' => $payment,
				    	'invoice' => $invoice,
				    	'status' => $status
				    );
				    $format = array(
				    	'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
				    );
				    $result = $wpdb->insert( $table_name, $data, $format);
				    if( $result ) {
				    	$_SESSION['message'] = "Added.";
				    } 
		    	}
		    	rallyrental_data_calendar();
		    	wp_safe_redirect( admin_url() . 'admin.php?page=rallyrental-rentals');
			}
			// delete
			if( isset( $_GET['action'] ) && $_GET['action'] == 'delete' &&  isset( $_GET['rental'] ) && $_GET['rental']  != null ) {
				$ids = $_GET['rental'];
				if ( is_array($ids) ) $ids = implode(',', $ids);
					if ( !empty($ids) ) {
						$result = $wpdb->query( "DELETE FROM `$table_name` WHERE id IN($ids)" );
						if( $result ) {
						$_SESSION['message'] = "Deleted..";
						rallyrental_data_calendar();
				    	wp_safe_redirect( admin_url() . 'admin.php?page=rallyrental-rentals');
				    } 
				}
			}
			?>
			</div>
		</div>
	</div>
	<?php
	// call list table
	$rallyrentalListTable = new RallyRental_List_Table();
	$rallyrentalListTable->prepare_items();
	?>
	<div class="container">
	    <div class="wrap row">
	    	<div class="col-md-5 col-xs-12">
		        <h1 class="wp-heading-inline">Rentals</h1>
		        <span id="split-page-title-action" class="split-page-title-action">
		        	<a href="#" class="add-new page-title-action">Add New</a>
		        </span>
		        <form id="rentals-filter" method="get">
		            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		            <?php $rallyrentalListTable->display(); ?>
		        </form>
		    </div>
			<div class="col-md-7 col-xs-12">
				<div id='calendar'></div>
				<p style="margin-top: 20px;"><img src='<?php echo site_url(); ?>/wp-content/uploads/2019/01/rally2.png' alt='Rally Rental' /></p>
		    </div>
	    </div>
	</div>
	<?php
}


/**
 *  List Table Ajax Call
 */
add_action( 'wp_ajax_rallyrental_form', 'rallyrental_form' );
function rallyrental_form(){ 
	if(isset($_POST["rental_id"]))  {  
		global $wpdb;
		$table_name = $wpdb->prefix . 'rallyrental';
		$id = $_POST["rental_id"];
      	$result = $wpdb->get_results( "SELECT * FROM `$table_name` WHERE `id` = $id");   
      	echo json_encode($result);  
	    die(); 
	}
}


/**
 *  Cars
 */
add_shortcode( 'car_slider', 'rallyrental_cars' );
function rallyrental_cars() {
	$query = new WP_Query(array(
	    'post_type' => 'car',
	    'post_status' => 'publish',
	    'posts_per_page' => -1,
	));
	$content = '';
	$cars = $query->posts;
	$content .= '<div class="row">';
		foreach( $cars as $car ) : 
		$image = get_the_post_thumbnail( $car->ID );
		$content .= '
			<div class="col-md-4 col-xs-12">
				<div class="car">
					<div class="row">
						<div class="featured-image">'.$image.'</div>
						<div class="col-md-6 col-xs-12">
							<h3>'.$car->post_title.'</h3>
						</div>
						<div class="col-md-6 col-xs-12">
							<p><i class="fas fa-calendar-alt"></i>'.$car->car_model.' Model</p>
							<p><i class="fas fa-car-side"></i>'.$car->car_type.' Type</p>
							<p><i class="fas fa-tools"></i>'.$car->car_transmission.'</p>
							<p><i class="fas fa-users"></i>'.$car->car_seats.' Seats</p>
						</div>
					</div>
				</div>
			</div>
		';
		endforeach;
	$content .= '</div>';
	wp_reset_query();
	return $content;
}


/**
 *  Settings Page ====== SOON
 */
// function rallyrental_settings() {
// 	echo 'SETTINGS PAGE';
// }