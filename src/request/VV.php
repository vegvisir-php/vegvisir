<?php

	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;

	use MatthiasMullie\Minify;

	class VV extends Path {
		// Set HTTP response code and return error page if enabled
		public static function error(int $code): void {
			http_response_code($code);

			exit();

			// Bail out here if we got an HTTP code from the 200-range or no custom error page is defined
			/*if (($code >= 200 && $code < 300) || !ENV::isset(ENV::ERROR_PAGE)) {
				exit();
			}*/

			include Path::root(self::append_extension(ENV::get(ENV::ERROR_PAGE), ".php"));
		}

		// Load and return minified CSS file from absolute path or CSS assets folder
		public static function css(string $file, bool $relative = true): string {
			$file = $relative ? Path::root("assets/css/" . self::append_extension($file, ".css")) : $file;

			// Import and minify CSS stylesheet or return empty string if not found
			return is_file($file) ? (new Minify\CSS($file))->minify() : "";
		}

		// Load and return minified JS file from absolute path or JS assets folder
		public static function js(string $file, bool $relative = true): string {
			$file = $relative ? Path::root("assets/js/" . self::append_extension($file, ".js")) : $file;

			// Import and minify JS source or return empty string if not found
			return is_file($file) ? (new Minify\JS($file))->minify() : "";
		}

		// Load and return contents of a file from absolute path or media assets folder
		public static function media(string $file, bool $relative = true): string {
			$file = $relative ? Path::root("assets/media/" . $file) : $file;
			
			return is_file($file) ? file_get_contents($file) : "";
		}

		// Include a PHP file from 
		public static function include(string $path) {
			// Load PHP file relative from user context root
			$file = parent::root(Format::str_append_extension($path, ".php"));

			if (!is_file($file)) {
				return self::error(404);
			}

			// Import and evaluate PHP file
			include $file;
		}

		// Bundle resources required to load the Vegvisir front-end
		public static function init(): void {
			include Path::vegvisir("src/frontend/Bundle.php");
		}
	}