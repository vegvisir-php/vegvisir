<?php 

	/*
		This file generates a minified bundle of all resources required to load the Vegvisir front-end.
		It is usually invoked by calling VV::init() from the ENV::SITE_SHELL_PATH page.
	*/

	namespace Vegvisir\Frontend;

	use VV;
	use Vegvisir\Kernel\Path;

	require_once Path::vegvisir("src/frontend/Export.php");

	// Licence headers for LibreJS etc.
	const LICENSE_HEADER_START = "// @license magnet:?xt=urn:btih:3877d6d54b3accd4bc32f8a48bf32ebc0901502a&dn=mpl-2.0.txt MPL-2.0" . PHP_EOL;
	const LICENSE_HEADER_END = PHP_EOL . "// @license-end";

?>

<script><?= LICENSE_HEADER_START ?><?= (new Export())->generate() ?><?= LICENSE_HEADER_END ?></script>
<script><?= LICENSE_HEADER_START ?><?= VV::js(Path::vegvisir("src/frontend/js/navigation/Controller.js"), false) ?><?= LICENSE_HEADER_END ?></script>
<script><?= LICENSE_HEADER_START ?><?= VV::js(Path::vegvisir("src/frontend/js/Vegvisir.js"), false) ?><?= LICENSE_HEADER_END ?></script>