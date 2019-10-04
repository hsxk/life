<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php $detail = KUSANAGI_BE_get_request( $_GET['req-id'] ); ?>

<h3><?php _e( 'Request Detail', 'kusanagi-be' ); ?></h3>

<?php if ( is_string( $detail ) ) : ?>
<p><?php echo esc_html( $detail ); ?></p>

<?php elseif ( ! empty( $detail ) && is_array( $detail ) ) : ?>
<?php foreach ( $detail['detail'] as $message ) : ?>
<?php
	$added_time = date_i18n( 'Y-m-d H:i:s', $message['added'] + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
?>
<table class="wp-list-table widefat fixed striped req-color-<?php echo esc_attr( $message['sender'] ); ?> request-table">
	<thead>
		<tr>
			<?php if ( $message['sender'] === '0' ) : ?>
			<th><?php echo esc_html( $detail['req_id'] ); ?> <time datetime="<?php echo esc_attr( $added_time ); ?>"><?php _e( 'Date', 'kusanagi-be' ); ?> <?php echo esc_html( $added_time ); ?></time></th>
			<?php else : ?>
			<th>KUSANAGI Support <time datetime="<?php echo esc_attr( $added_time ); ?>"><?php _e( 'Date', 'kusanagi-be' ); ?> <?php echo esc_html( $added_time ); ?></time></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo nl2br( esc_html( $message['request_detail'] ) ); ?></td>
		</tr>
	</tbody>
</table>
<?php endforeach; ?>

<?php if ( $detail['status'] === '0' ) : ?>
<form action="<?php echo admin_url( 'admin.php?page=kusanagi-be&req-id=' . (int)$_GET['req-id'] ); ?>" method="post" class="postbox">
	<h4><?php _e( 'Reply', 'kusanagi-be' ); ?></h4>
	<div class="inside">
		<textarea name="kusanagi-request-detail" cols="80" rows="7" placeholder="<?php _e( 'Please write your message.', 'kusanagi-be' ); ?>"></textarea>
		<input type="hidden" name="switch" value="kusanagi-be-submit-reply">
		<input type="hidden" name="req-id" value="<?php echo esc_attr( $_GET['req-id'] ); ?>">
		<p><input type="submit" class="button button-primary" value="<?php _e( 'Send', 'kusanagi-be' ); ?>"></p>
	</div>
	<?php wp_nonce_field( 'kusanagi-be-request-detail', 'kusanagi-be-request-detail-nonce' ); ?>
</form>
<?php else : ?>
<p><?php _e( 'Ticket has been closed.', 'kusanagi-be' ); ?></p>
<?php endif; ?>

<?php else : ?>
<p><?php _e( 'NONE', 'kusanagi-be' ); ?></p>

<?php endif; ?>
