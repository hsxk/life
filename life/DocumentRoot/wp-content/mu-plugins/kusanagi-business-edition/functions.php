<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
/**
 * @link              https://www.prime-strategy.co.jp/
 * @since             1.0.0
 *
 * @package           KUSANAGI_Business_Edition
 */

/**
 * Get user detailed information from their server
 * @since    1.0.0
 */
function KUSANAGI_BE_get_instance_identity() {
	if ( false === ( $instance_identity = get_transient( 'KUSANAGI_BE_instance_identity' ) ) ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, KUSANAGI_BE_INSTANCE_URL );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );
		curl_close( $ch );
		
		$result = json_decode( $response, true );

		$products = array();
		foreach( $result['marketplaceProductCodes'] as $value ) {
			$products[] = $value;
		}

		$profile = '';
		if ( preg_match( '=[^/home/kusanagi/]{1}[^/]*=', KUSANAGI_BE_PLUGIN_DIR, $m ) ) {
			$profile = $m[0];
		}

		$instance_identity = array(
					'kusanagi-license-code' => $result['accountId'],
					'kusanagi-instance-id'  => $result['instanceId'],
					'kusanagi-region'       => $result['region'],
					'kusanagi-profile'      => $profile,
					'domain'                => $_SERVER['HTTP_HOST'],
					'product-code'          => $products,
					'language'              => get_locale(),
					'marketplace'           => 'aws',
				);
		KUSANAGI_BE_set_transient( 'instance_identity', $instance_identity, 4 * HOUR_IN_SECONDS );
	}

	return $instance_identity;
}



/**
 * Post to PS server
 * @since    1.0.0
 */
function KUSANAGI_BE_post_to_ps( $data ) {
	$data = (array)$data;
	$data['kusanagi-validate'] = 'kusanagi-be-API';

	$data = KUSANAGI_BE_mold_data( $data );

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, KUSANAGI_BE_PS_URL );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	$response = curl_exec( $ch );
	curl_close( $ch );
	
	$result = json_decode( $response, true );
	
	return $result;
}



/**
 * The data to be transmitted into a form that can be sent
 * @since    1.0.0
 */
function KUSANAGI_BE_mold_data( $data ) {
	$data = http_build_query( $data, 'flags_' );
	$data = base64_encode( $data );

	return (array)$data;
}



/**
 * Set a function to operate on posted request
 * @since    1.0.0
 */
function KUSANAGI_BE_check_func_switcher() {
	$switch_list = array( 'kusanagi-be-agreement', 'kusanagi-be-request', 'kusanagi-be-submit-reply' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission.', 'kusanagi-be' ) );
	}

	if ( ! get_option( 'KUSANAGI_BE_agreement' ) && KUSANAGI_BE_is_req_detail() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	if ( isset( $_GET['req-status'] ) ) {
		check_admin_referer( 'kusanagi-be-requests', 'kusanagi-be-requests-nonce' );
	}

	$_POST = stripslashes_deep( $_POST );

	if ( isset( $_POST['switch'] ) && in_array( $_POST['switch'], $switch_list, true ) ) {

		if ( isset( $_COOKIE['KUSANAGI_BE_posted'] ) ) {
			update_option( 'KUSANAGI_BE_error', __( 'Please try again after few minutes.', 'kusanagi-be' ), false );
			wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
			exit;
		}

		switch ( $_POST['switch'] ) {
			case 'kusanagi-be-agreement' :
				check_admin_referer( 'kusanagi-be-agreement', 'kusanagi-be-agreement-nonce' );
				KUSANAGI_BE_save_agreement();
				break;
			case 'kusanagi-be-request' :
				check_admin_referer( 'kusanagi-be-request', 'kusanagi-be-request-nonce' );
				KUSANAGI_BE_save_request();
				break;
			case 'kusanagi-be-submit-reply' :
				check_admin_referer( 'kusanagi-be-request-detail', 'kusanagi-be-request-detail-nonce' );
				KUSANAGI_BE_save_submit_reply();
				break;
		}
	}
}



/**
 * Notify PS that the user agree to the terms
 * @since    1.0.0
 */
function KUSANAGI_BE_save_agreement() {
	if ( $_POST['kusanagi-be-agreement'] !== 'open' ) {
		update_option( 'KUSANAGI_BE_error', __( 'Please select the checkbox.', 'kusanagi-be' ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	$post = KUSANAGI_BE_get_instance_identity();
	$post['kusanagi-request'] = 'save_agreement';
	
	$result = KUSANAGI_BE_post_to_ps( $post );

	if ( KUSANAGI_BE_is_error( $result ) ) {
		update_option( 'KUSANAGI_BE_error', esc_html( $result ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	setcookie( 'KUSANAGI_BE_posted', true, time() + 3 );
	update_option( 'KUSANAGI_BE_agreement', $result );
	update_option( 'KUSANAGI_BE_message', __( 'Success!', 'kusanagi-be' ), false );
}



/**
 * Send request to PS
 * @since    1.0.0
 */
function KUSANAGI_BE_save_request() {
	$request_detail = esc_html( trim( $_POST['kusanagi-request-detail'] ) );

	if ( empty( $request_detail ) ) {
		update_option( 'KUSANAGI_BE_error', __( 'Please write your request.', 'kusanagi-be' ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	$post = KUSANAGI_BE_get_instance_identity();
	$post['kusanagi-request'] = 'save_request';
	$post['kusanagi-request-detail'] = $request_detail;
	$post['kusanagi-agreement'] = get_option( 'KUSANAGI_BE_agreement' );
	
	$result = KUSANAGI_BE_post_to_ps( $post );

	if ( KUSANAGI_BE_is_error( $result ) ) {
		update_option( 'KUSANAGI_BE_error', esc_html( $result ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	if ( is_array( $result ) ) {
		setcookie( 'KUSANAGI_BE_posted', true, time() + 3 );
		KUSANAGI_BE_delete_transient( 'requests', true );
		KUSANAGI_BE_set_transient( 'requests_p1_s', $result );
		update_option( 'KUSANAGI_BE_message', __( 'Your request has been sent successfully.', 'kusanagi-be' ), false );
	} else {
		update_option( 'KUSANAGI_BE_error', __( 'An unexpected error occurred. Please try again after few minutes.', 'kusanagi-be' ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}
}



/**
 * Send reply to PS
 * @since    1.0.0
 */
function KUSANAGI_BE_save_submit_reply() {
	$req_id = (int)$_POST['req-id'];
	$request_detail = esc_html( trim( $_POST['kusanagi-request-detail'] ) );

	if ( empty( $request_detail ) ) {
		update_option( 'KUSANAGI_BE_error', __( 'Please write your message.', 'kusanagi-be' ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be&req-id=' . $req_id ) );
		exit;
	}

	$post = KUSANAGI_BE_get_instance_identity();
	$post['kusanagi-request'] = 'save_submit_reply';
	$post['kusanagi-request-id'] = $req_id;
	$post['kusanagi-request-detail'] = $request_detail;
	$post['kusanagi-agreement'] = get_option( 'KUSANAGI_BE_agreement' );
	
	$result = KUSANAGI_BE_post_to_ps( $post );

	if ( KUSANAGI_BE_is_error( $result ) ) {
		update_option( 'KUSANAGI_BE_error', esc_html( $result ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be' ) );
		exit;
	}

	if ( is_array( $result ) ) {
		setcookie( 'KUSANAGI_BE_posted', true, time() + 3 );
		KUSANAGI_BE_delete_transient( 'request_id' . $req_id );
		KUSANAGI_BE_set_transient( 'request_id' . $req_id, $result );
		update_option( 'KUSANAGI_BE_message', __( 'Success!', 'kusanagi-be' ), false );
	} else {
		update_option( 'KUSANAGI_BE_error', __( 'An unexpected error occurred. Please try again after few minutes.', 'kusanagi-be' ), false );
		wp_safe_redirect( admin_url( 'admin.php?page=kusanagi-be&req-id=' . $req_id ) );
		exit;
	}
}



/**
 * Get request list by PS
 * @since    1.0.0
 */
function KUSANAGI_BE_get_requests( $paged = 1, $status = '' ) {
	if ( ! is_numeric( $paged ) ) {
		return __( 'Invalid page number.', 'kusanagi-be' );
	}

	if ( ! empty( $status ) && ! is_numeric( $status ) ) {
		return __( 'Invalid status.', 'kusanagi-be' );
	}

	$post = KUSANAGI_BE_get_instance_identity();
	$post['kusanagi-request'] = 'get_requests';
	$post['kusanagi-paged'] = $paged;
	$post['kusanagi-status'] = $status;
	$post['kusanagi-agreement'] = get_option( 'KUSANAGI_BE_agreement' );
	
	if ( false === ( $result = get_transient( 'KUSANAGI_BE_requests_p' . $paged . '_s' . $status ) ) ) {
		$result = KUSANAGI_BE_post_to_ps( $post );

		if ( KUSANAGI_BE_is_error( $result ) ) {
			update_option( 'KUSANAGI_BE_error', esc_html( $result ), false );
			return __( 'An unexpected error. Please reload the page.', 'kusanagi-be' );
		}

		KUSANAGI_BE_set_transient( 'requests_p' . $paged . '_s' . $status, $result );
	}

	if ( is_array( $result ) ) {
		return $result;
	} else {
		return __( 'An unexpected error occurred. Please try again after few minutes.', 'kusanagi-be' );
	}
}



/**
 * Get the request by PS
 * @since    1.0.0
 */
function KUSANAGI_BE_get_request( $req_id ) {
	if ( ! is_numeric( $req_id ) ) {
		return __( 'Invalid request ID.', 'kusanagi-be' );
	}

	$post = KUSANAGI_BE_get_instance_identity();
	$post['kusanagi-request'] = 'get_request';
	$post['kusanagi-request-id'] = $req_id;
	$post['kusanagi-agreement'] = get_option( 'KUSANAGI_BE_agreement' );
	
	if ( false === ( $result = get_transient( 'KUSANAGI_BE_request_id' . $req_id ) ) ) {
		$result = KUSANAGI_BE_post_to_ps( $post );

		if ( KUSANAGI_BE_is_error( $result ) ) {
			update_option( 'KUSANAGI_BE_error', esc_html( $result ), false );
			return __( 'An unexpected error. Please back to the Business Edition top page.', 'kusanagi-be' );
		}

		KUSANAGI_BE_set_transient( 'request_id' . $req_id, $result );
	}

	if ( is_array( $result ) ) {
		return $result;
	} else {
		return __( 'An unexpected error occurred. Please try again after few minutes.', 'kusanagi-be' );
	}
}



/**
 * Set transient
 * @since    1.0.0
 */
function KUSANAGI_BE_set_transient( $name, $data, $time = 600 ) {
	set_transient( 'KUSANAGI_BE_' . $name, $data, $time );
}



/**
 * Delete transient
 * @since    1.0.0
 */
function KUSANAGI_BE_delete_transient( $name, $all = false ) {
	global $wpdb;

	if ( $all ) {
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_KUSANAGI_BE_' . $name . '%' ) );
		foreach ( $results as $result ) {
			$result = preg_replace( '/^_transient_/', '', $result );
			delete_transient( $result );
		}
	} else {
		delete_transient( 'KUSANAGI_BE_' . $name );
	}
}



/**
 * Determines whether the query is for a request detail
 * @since    1.0.0
 */
function KUSANAGI_BE_is_req_detail() {
	if ( isset( $_GET['req-id'] ) && is_numeric( $_GET['req-id'] ) ) :
		return true;
	else :
		return false;
	endif;
}



/**
 * Determines whether the response is error
 * @since    1.0.0
 */
function KUSANAGI_BE_is_error( $message ) {
	if ( is_string( $message ) && preg_match( '/^[0-9]{3}\s/', $message, $m ) ) :
		if ( $m[0] === '503 ' ) {
			delete_option( 'KUSANAGI_BE_agreement' );
		}
		return true;
	else :
		return false;
	endif;
}



/**
 * Return request status array
 * @since    1.0.0
 */
function KUSANAGI_BE_get_req_status_list() {
	$list = array(
				'all' => 'All',
				'0'   => 'Open',
				'1'   => 'Closed',
			);
	return $list;
}
