// Bind anchor tags in root shell on load
vegvisir.Navigation.bindElements();

(new vegvisir.Navigation(window.location.pathname)).navigate();

window.addEventListener("popstate", (event) => {
	event.preventDefault();

	// Step back if event doesn't contain data. This entry was probably a [non root shell] soft-navigation from an anchor tag
	if (event.state === null) {
		return window.history.back();
	}

	// Turn off history pushing forcefully, we don't want backtracking on the history stack
	event.state.options.pushHistory = false;
	
	// Perform a Vegvisir softnav to url if state has a pathname key
	if ("url" in event.state) {
		return (new vegvisir.Navigation(new URL(event.state.url), event.state.options)).navigate();
	}
});