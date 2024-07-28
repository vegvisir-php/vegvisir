const VV_SHELL_ID = /<!\[CDATA\[VV_SHELL:(.*?)\]\]>/;
const SOFTNAV_ENABLED_HEADER = "X-Vegvisir-Navigation";

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
		const shell = body.match(VV_SHELL_ID);

		// Return response body directly if it doesn't contain a multipart shell
		if (!shell) {
			return this.#output(response.status, body);
		}
		
		// Expand page and shell HTML into separate variables
		const [pageHtml, shellHtml] = body.split(shell[0], 2);

		// Bail out with shell id as target if there is no shell HTML to parse, this means the client already has this shell loaded
		if (!shellHtml) {
			return this.#output(response.status, body, shell[1]);	
		}

		// Output shell HTML as partial content
		this.#output(206, shellHtml);
		
		// Output page HTML with shell id as target
		return this.#output(response.status, pageHtml, shell[1]);
	}
}

globalThis.onmessage = (event) => new NavigationEvent(event);