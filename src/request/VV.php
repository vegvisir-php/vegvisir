<?php

	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;
	use Vegvisir\Request\Controller;

	use MatthiasMullie\Minify;

	const VV_SHELL_HASH_OFFSET = -8;
	const VV_SHELL_ID_STRING = "<![CDATA[VV_SHELL:%s]]>";

	class VV extends Path {
		// Set HTTP response code and return error page if enabled
		public static function error(int $code): void {
			http_response_code($code);

			if (!ENV::isset(ENV::SITE_ERROR_PAGE) || !Controller::client_accepts_html()) {
				die();
			}

			die(self::include(ENV::get(ENV::SITE_ERROR_PAGE)));
		}

		// Load and return minified CSS from file
		public static function css(string $file, bool $relative = true): string {
			$file = $relative ? Path::root(Format::str_append_extension($file, ".css")) : $file;

			// Import and minify CSS stylesheet or return empty string if not found
			return is_file($file) ? (new Minify\CSS($file))->minify() : "";
		}

		// Load and return minified JS from file
		public static function js(string $file, bool $relative = true): string {
			$file = $relative ? Path::root(Format::str_append_extension($file, ".js")) : $file;

			// Import and minify JS source or return empty string if not found
			return is_file($file) ? (new Minify\JS($file))->minify() : "";
		}

		// Load and return contents of a file
		public static function embed(string $file, bool $relative = true): string {
			$file = $relative ? Path::root($file) : $file;
			
			return is_file($file) ? file_get_contents($file) : "";
		}

		// Include a PHP file from absolute path or from root of user context
		public static function include(string $path, bool $relative = true) {
			// Load PHP file relative from user context root
			$file = $relative ? parent::root(Format::str_append_extension($path, ".php")) : Format::str_append_extension($path, ".php");

			if (!is_file($file)) {
				return self::error(404);
			}

			// Import and evaluate PHP file
			include $file;
		}

		public static function shell(string $path) {
			// Load shell from file into new buffer
			ob_start();

			// Generate truncated hash of the shell path
			$hash = substr(md5($path), VV_SHELL_HASH_OFFSET);

			// Add CDATA separator between page and shell content with shell hash for Worker to parse
			echo sprintf(VV_SHELL_ID_STRING, $hash);

			// Bail out if shell has already been loaded by the client
			if (in_array($hash, Controller::get_loaded_shells())) {
				return flush();
			}

			self::include($path);

			// Extract generated HTML from shell in buffer and add shell id as an attribute to <vv-shell>
			$content = str_replace("></vv-shell>", " vv-shell-id='{$hash}'></vv-shell>", ob_get_contents());

			// Write modified HTML back into the buffer
			ob_clean();
			echo $content;
			flush();
		}

		// Bundle resources required to load the Vegvisir front-end
		public static function init(): void {
			include Path::vegvisir("src/frontend/Bundle.php");
		}
	}