<?php

	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;

	use MatthiasMullie\Minify;

	const VV_SHELL_HASH_OFFSET = -8;
	const VV_SHELL_ID_HEADER = "X-Vegvisir-Target";
	const VV_SHELL_SEPARATOR_STRING = "<!-- VEGVISIR MULTIPART SHELL END -->";

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

		public static function shell(string $path) {
			ob_start();
			self::include($path);

			// Generate truncated hash of the shell path
			$hash = substr(md5($path), VV_SHELL_HASH_OFFSET);

			header(implode(" ", [VV_SHELL_ID_HEADER, $hash]));

			// Add shell id attribute to VV_SHELL_TAGNAME while preserving any existing attributes
			$content = str_replace("></vv-shell>", " vv-shell-id='{$hash}'></vv-shell>", ob_get_contents());

			ob_clean();
			echo $content;
			echo VV_SHELL_SEPARATOR_STRING;
			flush();
		}

		// Bundle resources required to load the Vegvisir front-end
		public static function init(): void {
			include Path::vegvisir("src/frontend/Bundle.php");
		}
	}