<?php

	namespace Vegvisir\Request;

	const SOFTNAV_ENABLED_HEADER = "HTTP_X_VEGVISIR_NAVIGATION";

	class Controller {
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
	}