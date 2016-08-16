<span id="add_link_item">
	<span class="add_button_wrap button">
		<a href="javascript:void(0)" class="add_predefined add_button add_button_drop" data-dropdown="#type_selection">&nbsp;<?php _e( 'Add', 'sprout-invoices' ) ?></a>
	</span>
	<div id="type_selection" class="dropdown dropdown-tip dropdown-relative">
		<ul class="si-dropdown-menu">
			<?php foreach ( $types as $slug => $type ) : ?>
				<li><a class="item_add_type <?php if ( $slug === $default ) { echo 'default_type'; } ?>" href="javascript:void(0)" data-item-id="<?php echo $slug ?>" data-doc-id="<?php echo get_the_id() ?>"><b><?php echo esc_html( $type ) ?></b></a></li>
			<?php endforeach ?>
		</ul>
	</div>
</span>
