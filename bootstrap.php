<?php

Autoloader::add_core_namespace('Auth');

Autoloader::add_classes(array(
	'Auth\\Auth'           => __DIR__.'/classes/auth.php',
	'Auth\\AuthException'  => __DIR__.'/classes/auth.php',
));


/* End of file bootstrap.php */