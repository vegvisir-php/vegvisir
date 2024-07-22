<?php

	namespace Vegvisir\Request;

	const SOFTNAV_ENABLED_HEADER = "HTTP_X_VEGVISIR_NAVIGATION";
	const SOFTNAV_TARGET_RESP_HEADER = "X-Vegvisir-Target";

	class Controller {
		static $is_softnav_target_set = false;

		// Returns true if the client sent an HTTP Accept header which includes text/html
		public static function client_accepts_html(): bool {
			return strpos($_SERVER["HTTP_ACCEPT"] ?? "", "text/html") !== false;
		}

		// Returns true if the client sent a SOFTNAV_ENABLED_HEADER
		public static function is_softnav_enabled(): bool {
			return !empty($_SERVER[SOFTNAV_ENABLED_HEADER]);
		}

		// Parses loaded shell ids from SOFTNAV_ENABLED_HEADER CSV
		public static function get_loaded_shells(): array {
			return self::is_softnav_enabled() ? explode(",", $_SERVER[SOFTNAV_ENABLED_HEADER]) : [];
		}

		// Set a response header with a shell id which will be parsed by the front-end Navigation Worker
		public static function set_softnav_target(string $target): void {
			$is_softnav_target_set = true;
			header(SOFTNAV_TARGET_RESP_HEADER . ": " . $target, true);
		}
	}