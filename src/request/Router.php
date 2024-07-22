<?php

	namespace Vegvisir\Request;

	use VV;
	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;
	use Vegvisir\Kernel\Format;
	use Vegvisir\Request\Controller;

	use MatthiasMullie\Minify;

	require_once Path::vegvisir("src/request/VV.php");
	require_once Path::vegvisir("src/request/Controller.php");

	class Router extends VV {
		private string $pathname;

		public function __construct() {
			// Set pathname from request URI
			$this->pathname = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

			// Parse JSON from request body into PHP $_POST superglobal
			if (array_key_exists("HTTP_CONTENT_TYPE", $_SERVER) and $_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
				$_POST = json_decode(file_get_contents("php://input"), true) ?? [];
			}

			$this->route();
		}

		// Return the outer most shell required to initialize the Vegvisir front-end
		private function resp_top_shell() {
			// Bail out if the request is not for an HTML document
			if (!Controller::client_accepts_html()) {
				return parent::error(404);
			}

			parent::include(ENV::get(ENV::SITE_SHELL_PATH));
		}

		private function resp_page(string $path) {
			include $path;
		}

		private function resp_worker() {
			header("Content-Type: text/javascript");
			exit((new Minify\JS(Path::vegvisir("src/frontend/js/navigation/Worker.js")))->minify());
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
			// Return JavaScript for Vegvisir navigation Worker
			if ($this->pathname === ENV::get(ENV::WORKER_PATHNAME)) {
				return $this->resp_worker();
			}

			if (!Controller::is_softnav_enabled()) {
				return $this->resp_top_shell();
			}

			// Check if a PHP file exists in user context public directory
			// It's very important for security that this check comes before the direct file extension check
			if ($this->pathname and is_file(Path::public($this->pathname) . ".php")) {
				return $this->resp_page(Path::public($this->pathname) . ".php");
			}

			if ($this->pathname and is_file(Path::public($this->pathname) . "/index.php")) {
				return $this->resp_page(Path::public($this->pathname) . "/index.php");
			}

			// Check file extension directly for public static content
			if ($this->pathname and is_file(Path::public($this->pathname))) {
				return $this->resp_asset();
			}

			parent::error(404);
		}
	}