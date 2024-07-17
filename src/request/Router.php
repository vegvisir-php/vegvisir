<?php

	namespace Vegvisir\Request;

	use VV;
	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;

	use MatthiasMullie\Minify;

	require_once Path::vegvisir("src/request/VV.php");

	const SOFTNAV_ENABLED_HEADER = "HTTP_X_VEGVISIR_NAVIGATION";

	class Router extends VV {
		private string $pathname;

		public function __construct() {
			// Set pathname from request URI
			$this->pathname = Format::str_strip_char_leading(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");

			// Parse JSON from request body into PHP $_POST superglobal
			if (array_key_exists("HTTP_CONTENT_TYPE", $_SERVER) and $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
				$_POST = json_decode(file_get_contents("php://input"), true) ?? [];
			}

			$this->route();
		}

		private function resp_shell() {
			// Bail out if the request is not for an HTML document
			if (strpos($_SERVER["HTTP_ACCEPT"] ?? "", "text/html") === false) {
				return parent::error(204);
			}

			parent::include(ENV::get(ENV::SITE_SHELL_PATH));
		}

		private function resp_page() {
			parent::include(Path::make_pathname(
				ENV::get(ENV::PUBLIC_PATH),
				$this->pathname
			));
		}

		private function resp_worker() {
			header("Content-Type: text/javascript");
			exit(file_get_contents(Path::vegvisir("src/frontend/js/navigation/Worker.js")));
		}

		private function resp_asset() {
			$file = Path::public($this->pathname);

			// Get MIME-type for file or default to plaintext
			$type = mime_content_type($file);
			if (empty($type) || $type === "application/x-empty") {
				$type = "text/plain";
			}

			header("Content-Type: " . $type);

			exit(file_get_contents($file));
		}

		private function route() {
			if (empty($_SERVER[SOFTNAV_ENABLED_HEADER])) {
				return $this->resp_shell();
			}

			// Return JavaScript for Vegvisir navigation Worker
			if ($this->pathname === ENV::get(ENV::WORKER_PATHNAME)) {
				return $this->resp_worker();
			}

			// Check if a PHP file exists in user context public directory
			// It's very important for security that this check comes before the direct file extension check
			if (file_exists(Path::public($this->pathname) . ".php")) {
				return $this->return_page();
			}

			// Check file extension directly for public static content
			if (file_exists(Path::public($this->filename))) {
				return $this->return_asset();
			}

			return $this->return_error();
		}
	}