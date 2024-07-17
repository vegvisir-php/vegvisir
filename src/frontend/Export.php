<?php

	namespace Vegvisir\Frontend;

	use Vegvisir\Kernel\ENV;

	class Export {
		private static $variables = [
			ENV::WORKER_PATHNAME
		];

		// Return assoc array of exported environment variables
		private static function get_env(): array {
			return array_map(fn(ENV $env): array => [$env->name => ENV::get($env)], self::$variables);
		}

		public static function generate(): string {
			return "globalThis.vegvisir = " . json_encode((object) self::get_env()) . ";";
		}
	}