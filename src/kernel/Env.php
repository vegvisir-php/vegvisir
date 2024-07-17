<?php

	namespace Vegvisir\Kernel;

	// Array key in $_ENV which will contain an array of key value Vegvisir environment variables
	const ENV_SUPERGLOB_NS = "_vv";

	enum ENV: string {
		// Site configuration variables
		case SITE_ROOT_PATH      = "root_path";
		case SITE_SHELL_PATH     = "shell_path";
		case SITE_PUBLIC_PATH    = "public_path";
		case SITE_INDEX_FILENAME = "index_file_name";

		// Core variables
		case WORKER_PATHNAME  = "worker_magic_pathname";

		public function get_default(): string {
			return match ($this) {
				// Site configuration variables
				ENV::SITE_ROOT_PATH      => "",
				ENV::SITE_SHELL_PATH     => "public/shell.php",
				ENV::SITE_PUBLIC_PATH    => "public/",
				ENV::SITE_INDEX_FILENAME => "index.php",
				
				// Core variables
				ENV::WORKER_PATHNAME => "_vvnavwrkr"
			};
		}

		// Returns true if a namespaced environment variable is set
		public static function isset(self $key): bool {
			return in_array($key->value, array_keys($_ENV[ENV_SUPERGLOB_NS])) && !empty($_ENV[ENV_SUPERGLOB_NS][$key->value]);
		}

		// Get namespaced environment variable by key
		public static function get(self $key): mixed {
			return self::isset($key) ? $_ENV[ENV_SUPERGLOB_NS][$key->value] : $key->get_default();
		}

		// Set namespaced environment variable key value pair
		public static function set(self $key, mixed $value = null) {
			$_ENV[ENV_SUPERGLOB_NS][$key->value] = $value;
		}

		// Parse (and overwrite) environment variables from an INI-file into namespace
		public static function parse_ini_file(string $path) {
			$_ENV[ENV_SUPERGLOB_NS] = parse_ini_file($path);
		}
	}