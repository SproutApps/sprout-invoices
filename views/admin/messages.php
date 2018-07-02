<?php if ( ! empty( $messages ) ) : ?>
	<span id="si_message_icon" class="si_tooltip" aria-label="<?php _e( 'You have some messages, click to view.', 'sprout-invoices' ) ?>" @click="toggleNotifications()"><?php printf( _n( '%s Message', '%s Messages', count( $messages ), 'sprout-invoices' ), count( $messages ) ) ?></span>

	<div id="si_messages" v-if='viewNotifications == true'>	
		<?php foreach ( $messages as $key => $message ) :  ?>
			<span class="si_message <?php echo $message['type'] ?>"><?php echo $message['content'] ?></span>
		<?php endforeach ?>
	</div><!-- #si_messages -->
<?php endif ?>
