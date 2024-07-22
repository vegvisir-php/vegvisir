// Bind anchor tags in root shell on load
vegvisir.Navigation.bindAnchorElementListeners();

(new vegvisir.Navigation(window.location.pathname)).navigate();

window.addEventListener("popstate", (event) => {
	event.preventDefault();

	// Bail out if the event doesn't contain state data, its probably another site entirely
	if (event.state === null) {
		return;
	}

	// Turn off history pushing forcefully, we don't want backtracking on the history stack
	event.state.options.pushHistory = false;
	
	// Perform a Vegvisir softnav to url if state has a pathname key
	if ("url" in event.state) {
		return (new vegvisir.Navigation(new URL(event.state.url), event.state.options)).navigate();
	}
});