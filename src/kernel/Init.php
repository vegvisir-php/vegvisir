<?php

	namespace Vegvisir\Kernel;

	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;

	// Path relative from current context to an INI-file containing environment variables
	const ENV_INI_PATH = ".env.ini";
	// Path relative from current context to a Composer autoload-file
	const COMPOSER_AUTOLOAD_PATH = "vendor/autoload.php";

	require_once "Env.php";
	require_once "Path.php";
	require_once "Format.php";
	require_once Path::vegvisir(COMPOSER_AUTOLOAD_PATH);

	// Load Vegvisir environment variables from file into namespace
	ENV::parse_ini_file(Path::vegvisir(ENV_INI_PATH));

	// Load Composer dependencies from user root if present
	if (file_exists(Path::root(COMPOSER_AUTOLOAD_PATH))) {
		require_once Path::root(COMPOSER_AUTOLOAD_PATH);
	}

	// Load environment variables from user root if present
	if (file_exists(Path::root(ENV_INI_PATH))) {
		// Merge user variables into $_ENV root while preserving namespaced Vegvisir variables
		$_ENV = array_merge($_ENV, parse_ini_file(Path::root(ENV_INI_PATH), true));
	}
