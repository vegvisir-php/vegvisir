<?php

	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;
	use Vegvisir\Request\Controller;

	use MatthiasMullie\Minify;

	const VV_SHELL_HASH_OFFSET = -8;
	const VV_SHELL_MULTIPART_BOUNDARY = "<![CDATA[VV_SHELL:%s]]>";

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
		public static function css(string $pathname, bool $relative = true): string {
			$pathname = $relative ? Path::root(Format::str_append_extension($pathname, ".css")) : $pathname;

			// Import and minify CSS stylesheet or return empty string if not found
			return is_file($pathname) ? (new Minify\CSS($pathname))->minify() : "";
		}

		// Load and return minified JS from file
		public static function js(string $pathname, bool $relative = true): string {
			$pathname = $relative ? Path::root(Format::str_append_extension($pathname, ".js")) : $pathname;

			// Import and minify JS source or return empty string if not found
			return is_file($pathname) ? (new Minify\JS($pathname))->minify() : "";
		}

		// Load and return contents of a file
		public static function embed(string $pathname, bool $relative = true): string {
			$pathname = $relative ? Path::root($pathname) : $pathname;
			
			return is_file($pathname) ? file_get_contents($pathname) : "";
		}

		// Include a PHP file from absolute path or from root of user context
		public static function include(string $pathname, bool $relative = true) {
			// Load PHP file relative from user context root
			$pathname = $relative ? parent::root(Format::str_append_extension($pathname, ".php")) : Format::str_append_extension($pathname, ".php");

			if (!is_file($pathname)) {
				return self::error(404);
			}

			// Import and evaluate PHP file
			include $pathname;
		}

		public static function shell(string $pathname) {
			// Load shell from file into new buffer
			ob_start();

			// Generate truncated hash of the shell path
			$hash = substr(md5($pathname), VV_SHELL_HASH_OFFSET);

			// Add CDATA separator between page and shell content with shell hash for Worker to parse
			echo sprintf(VV_SHELL_MULTIPART_BOUNDARY, $hash);

			// Bail out if shell has already been loaded by the client
			if (in_array($hash, Controller::get_loaded_shells())) {
				return flush();
			}

			self::include($pathname);

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