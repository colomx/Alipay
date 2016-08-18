<?php
return [

	// Security code, 32 characters in length
	'key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',

	// Sign Type
	'sign_type' => 'RSA',

	// Merchant Private Key
	'private_key_path' => __DIR__ . '/key/private_key.pem',

	// Public Key
	'public_key_path' => __DIR__ . '/key/public_key.pem',

	// Asynchronous Notify URL
	'notify_url' => 'http://xxx'
];
