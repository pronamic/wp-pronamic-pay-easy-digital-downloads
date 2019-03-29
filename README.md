# WordPress Pay Extension: Easy Digital Downloads

**Easy Digital Downloads driver for the WordPress payment processing library.**

## Test Easy Digital Downloads Fees

As far as we know there are no free/open-source Easy Digital Downloads fees plugins. With the following must-use WordPress plugin it is possible to test the Easy Digital Downloads fees system.

**`wp-content/mu-plugins/edd-test-fee.php`**

```php
<?php

add_action( 'init', function() {
	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	EDD()->fees->add_fee( 10, 'Test Backwards Compatibility', 'test-compat' );

	EDD()->fees->add_fee( array(
		'amount' => 20,
		'label'  => 'Test',
		'id'     => 'test',
		'no_tax' => false,
		'type'   => 'item',
	) );

	EDD()->fees->add_fee( array(
		'amount' => -5.95,
		'label'  => 'Discount',
		'id'     => 'discount',
		'type'   => 'fee',
	) );

	EDD()->fees->add_fee( array(
		'amount'      => 30.75,
		'label'       => 'Arbitrary Item',
		'download_id' => 8,
		'id'          => 'arbitrary_fee',
		'type'        => 'item',
	) );
} );

```

*	https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.11/tests/tests-cart.php#L506-L528
*	https://github.com/easydigitaldownloads/easy-digital-downloads/blob/2.9.11/includes/class-edd-fees.php

## Links

*	[Easy Digital Downloads](https://easydigitaldownloads.com/)
*	[GitHub Easy Digital Downloads](https://github.com/easydigitaldownloads/Easy-Digital-Downloads/)
