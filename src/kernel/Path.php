<?php

	namespace Vegvisir\Kernel;

	use Vegvisir\Kernel\Format;

	class Path {
		public static function make_pathname(string ...$pieces): string {
			return implode("/", $pieces);
		}

		// Returns an absolute path to the root folder of the Vegvisir context
		public static function vegvisir(string $crumbs = ""): string {
			return self::make_pathname(dirname(__DIR__, 2), $crumbs);
		}

		// Returns an absolute path to the root folder of the user context
		public static function root(string $crumbs = ""): string {
			return self::make_pathname(
				Format::str_strip_char_tailing(ENV::get(ENV::SITE_ROOT_PATH), "/"),
				Format::str_strip_char_leading($crumbs, "/")
			);
		}

		// Returns an absolute path to the public content of the user context
		public static function public(string $crumbs = ""): string {
			return self::make_pathname(
				Format::str_strip_char_tailing(ENV::get(ENV::SITE_ROOT_PATH), "/"),
				Format::str_strip_char_tailing(ENV::get(ENV::SITE_PUBLIC_PATH), "/"),
				Format::str_strip_char_leading($crumbs, "/")
			);
		}
	}
