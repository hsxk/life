<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<h3><?php _e( 'Request Form', 'kusanagi-be' ); ?></h3>
<form action="<?php echo admin_url( 'admin.php?page=kusanagi-be' ); ?>" method="post" class="postbox">
	<div class="inside">
		<textarea name="kusanagi-request-detail" cols="80" rows="7" placeholder="<?php _e('Please write your request here.', 'kusanagi-be'); ?>"></textarea>
		<p><b><?php _e( 'Note', 'kusanagi-be' ); ?></b>: <?php _e( 'Your request will contain the server information.', 'kusanagi-be' ); ?></p>
		<input type="hidden" name="switch" value="kusanagi-be-request">
		<?php wp_nonce_field( 'kusanagi-be-request', 'kusanagi-be-request-nonce' ); ?>
		<p><input type="submit" class="button button-primary" value="<?php _e( 'Send', 'kusanagi-be' ); ?>"></p>
	</div>
</form>

<?php
	$paged = ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] ) ) ? $_GET['paged'] : '1';
	$status = ( isset( $_GET['req-status'] ) && is_numeric( $_GET['req-status'] ) ) ? $_GET['req-status'] : '';
	$requests = KUSANAGI_BE_get_requests( $paged, $status );
?>
<h3><?php _e( 'Requests Status', 'kusanagi-be' ); ?></h3>
<form action="<?php echo admin_url( 'admin.php?page=kusanagi-be' ); ?>" method="get" class="postbox">
	<h4><?php _e( 'Requests List', 'kusanagi-be' ); ?></h4>
	<div class="inside">

		<select id="req-status" name="req-status">
			<?php $status_list = KUSANAGI_BE_get_req_status_list(); ?>
			<?php foreach ( $status_list as $value => $status ) : ?>
			<option<?php echo ( isset( $_GET['req-status'] ) && $_GET['req-status'] === (string)$value ) ? " selected" : ""; ?> value="<?php echo esc_attr( $value ); ?>"><?php _e( $status, 'kusanagi-be' ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="submit" class="button button-information" value="<?php _e( 'Filter', 'kusanagi-be' ); ?>">

		<?php if ( is_string( $requests ) ) : ?>
		<p><?php echo esc_html( $requests ); ?></p>

		<?php elseif ( ! empty( $requests ) && is_array( $requests ) ) : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th width="15%"><?php _e( 'Request ID', 'kusanagi-be' ); ?></th>
					<th width="50%"><?php _e( 'Request Detail', 'kusanagi-be' ); ?></th>
					<th width="10%"><?php _e( 'Status', 'kusanagi-be' ); ?></th>
					<th width="10%"><?php _e( 'Latest', 'kusanagi-be' ); ?></th>
					<th width="15%"><?php _e( 'Date', 'kusanagi-be' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $requests as $request ) : ?>
			<?php
					if ( $request['status'] === '0' ) {
						$status = __( 'Open', 'kusanagi-be');
						if ( $request['last_sender'] === '0' ) {
							$last_sender = __( 'Waiting', 'kusanagi-be' );
						} else {
							$last_sender = __( 'Replied', 'kusanagi-be' );
						}
					} else {
						$status = __( 'Closed', 'kusanagi-be' );
						$last_sender = __( 'Done', 'kusanagi-be' );
					}
			?>
				<tr>
					<td><?php echo esc_html( $request['req_id'] ); ?></td>
					<td><a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=kusanagi-be&req-id=' . esc_attr( $request['req_id'] ) ), 'kusanagi-be-request', 'kusanagi-be-request-nonce' ); ?>"><?php echo mb_strimwidth( esc_html( $request['request'] ), 0, 250, '...' ); ?></a></td>
					<td><?php echo esc_html( $status ); ?></td>
					<td><?php echo esc_html( $last_sender ); ?></td>


					<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', $request['added'] + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
		<p><?php _e( 'NONE', 'kusanagi-be' ); ?></p>
		
		<?php endif; ?>

		<div class="pagination">
			<?php if ( $paged !== '1' ) : ?>
			<button type="submit" name="paged" value="<?php echo esc_attr( $paged - 1 ); ?>" class="button button-information">&laquo; <?php _e( 'Previous Page', 'kusanagi-be' ); ?></button>
			<?php endif; ?>
			<?php if ( is_array( $requests ) && count( $requests ) === 10 ) : ?>
			<button type="submit" name="paged" value="<?php echo esc_attr( $paged + 1 ); ?>" class="button button-information"><?php _e( 'Next Page', 'kusanagi-be' ); ?> &raquo;</button>
			<?php endif; ?>
		</div>

		<input type="hidden" name="page" value="kusanagi-be">

	</div>
	<?php wp_nonce_field( 'kusanagi-be-requests', 'kusanagi-be-requests-nonce' ); ?>
</form>
