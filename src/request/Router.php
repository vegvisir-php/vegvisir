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
		private readonly string $pathname;

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

			// Return 404 response code on top shell if landingpage is not found to prevent a soft 404
			if (!$this->try_files()) {
				http_response_code(404);
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

		// Locate a page and return its absolute path if found
		private function try_files(): ?string {
			if (!$this->pathname) {
				return null;
			}

			if (is_file(Path::public($this->pathname) . ".php")) {
				return Path::public($this->pathname) . ".php";
			}

			if (is_file(Path::public($this->pathname) . "/index.php")) {
				return Path::public($this->pathname) . "/index.php";
			}

			return null;
		}

		private function route() {
			// Check absolute match against magic pathname for the Vegvisir Navigation Worker
			if ($this->pathname === ENV::get(ENV::WORKER_PATHNAME)) {
				return $this->resp_worker();
			}

			// Return the top shell on initial load to enable soft navigation
			if (!Controller::is_softnav_enabled()) {
				return $this->resp_top_shell();
			}

			// Check for an absolute match against an asset in the user context public folder
			if ($this->pathname and is_file(Path::public($this->pathname))) {
				return $this->resp_asset();
			}

			// Try to locate a page using various patterns or return 404 if no match
			$page = $this->try_files();
			return !empty($page) 
				? $this->resp_page($page) 
				: parent::error(404);
		}
	}