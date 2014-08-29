<?php 
	$fields['name']['default'] = get_the_title( $id );
	unset( $fields['email'] );
	unset( $fields['first_name'] );
	unset( $fields['last_name'] );
	$fields['street']['default'] = $address['street'];
	$fields['city']['default'] = $address['city'];
	$fields['zone']['default'] = $address['zone'];
	$fields['postal_code']['default'] = $address['postal_code'];
	$fields['country']['default'] = $address['country'];
	$fields['website']['default'] = $website; ?>


<div id="client_fields" class="admin_fields clearfix">
	<?php sa_admin_fields( $fields ); ?>
</div>