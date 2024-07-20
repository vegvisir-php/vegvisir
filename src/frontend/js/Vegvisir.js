(new vegvisir.Navigation(window.location.pathname)).navigate();


// Handle browser back/forward buttons
/*window.addEventListener("popstate", (event) => {
	event.preventDefault();

	// This event does not have any state data. Ignore it
	if (event.state === null) {
		return;
	}

	// Force pushHistory to false as we don't want this navigation on the stack
	event.state.options.pushHistory = false;
	
	if ("url" in event.state) {
		return new globalThis.vv.Navigation(event.state.url, event.state.options).navigate();
	}
});*/