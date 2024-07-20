<?php 

	/*
		This file generates a minified bundle of all resources required to load the Vegvisir front-end.
		It is usually invoked by calling VV::init() from the ENV::SITE_SHELL_PATH page.
	*/

	namespace Vegvisir\Frontend;

	use VV;
	use Vegvisir\Kernel\ENV;
	use Vegvisir\Kernel\Path;

	// Licence headers for LibreJS etc.
	const LICENSE_HEADER_START = "// @license magnet:?xt=urn:btih:3877d6d54b3accd4bc32f8a48bf32ebc0901502a&dn=mpl-2.0.txt MPL-2.0" . PHP_EOL;
	const LICENSE_HEADER_END = PHP_EOL . "// @license-end";

	// Export environment variables for use in JavaScript
	$EXPORT = [
		ENV::WORKER_PATHNAME->name => ENV::get(ENV::WORKER_PATHNAME)
	];

?>

<script>
<?= LICENSE_HEADER_START ?>
<?php // Initialize Vegvisir global object with exported environment variables ?>
globalThis.vegvisir = <?= json_encode((object) $EXPORT) ?>;

<?php // Load front-end modules ?>
<?= VV::js(Path::vegvisir("src/frontend/js/navigation/Controller.js"), false) ?>;
<?= VV::js(Path::vegvisir("src/frontend/js/navigation/Element.js"), false) ?>;

<?php // Finally, load the front-end initializer ?>
<?= VV::js(Path::vegvisir("src/frontend/js/Vegvisir.js"), false) ?>
<?= LICENSE_HEADER_END ?>
</script>