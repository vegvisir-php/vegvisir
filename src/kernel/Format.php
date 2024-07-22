<?php

	namespace Vegvisir\Kernel;

	class Format {
		// Strip a leading char from string if present and return result
		public static function str_strip_char_leading(string $string, string $char): string {
			return substr($string, 0, 1) !== $char ? $string : substr($string, 1);
		}

		// Strip a tailing char from string if present and return result
		public static function str_strip_char_tailing(string $string, string $char): string {
			return substr($string, -1, 1) !== $char ? $string : substr($string, 0, strlen($string) - 1);
		}

		// Append an extension string to another string if omitted
		public static function str_append_extension(string $string, string $ext): string {
			return substr($string, strlen($ext) * -1) === $ext ? $string : $string . $ext;
		}
	}