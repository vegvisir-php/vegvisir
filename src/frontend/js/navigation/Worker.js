const VV_SHELL_ID_HEADER = "X-Vegvisir-Target";
const SOFTNAV_ENABLED_HEADER = "X-Vegvisir-Navigation";
const VV_SHELL_SEPARATOR_STRING = "<!-- VEGVISIR MULTIPART SHELL END -->";

class NavigationEvent {
	fetchOptions = {
		headers: {}
	};

	/**
	 * Create a new Vegvisir soft navigation
	 * @param {MessageEvent} event 
	 */
	constructor(event) {
		this.id = event.data.requestId;
		this.url = event.data.requestUrl;
		this.shells = event.data.loadedShells;

		// Send loaded shells under the Vegvisir softnav header as CSV
		this.fetchOptions.headers[SOFTNAV_ENABLED_HEADER] = event.data.loadedShells.join(",");

		this.#navigate();
	}

	#output(status, body = null, target = null) {
		globalThis.postMessage({
			requestId: this.id,
			responseStatus: status,
			responseBody: body,
			responseTarget: target
		});
	}

	/**
	 * Dispatch fetch with options
	 * @returns {Response}
	 */
	async #fetch() {
		return await fetch(new Request(this.url, this.fetchOptions));
	}

	async #navigate() {
		const response = await this.#fetch();
		const body = await response.text();

		if (body.includes(VV_SHELL_SEPARATOR_STRING)) {
			const parts = body.split(VV_SHELL_SEPARATOR_STRING, 1);
			this.#output(206, parts[0]);
			this.#output(response.status, parts[0], response.headers.get(VV_SHELL_ID_HEADER));
		}

		return this.#output(response.status, body);
	}
}

globalThis.onmessage = (event) => new NavigationEvent(event);