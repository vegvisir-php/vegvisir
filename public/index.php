<?php

	use Vegvisir\Kernel\Path;
	use Vegvisir\Request\Router;

	require_once "../src/kernel/Init.php";

	// Start Vegvisir request processing
	require_once Path::vegvisir("src/request/Router.php");
	(new Router());