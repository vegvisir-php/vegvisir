const worker = new Worker(globalThis.vegvisir.WORKER_PATHNAME);

globalThis.vegvisir.Navigation = class Navigation {
	// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#target
	static TARGET = {
		TOP: "_top",
		SELF: "_self",
		BLANK: "_blank",
		PARENT: "_parent"
	};

	// https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentElement#position
	static POSITION = {
		AFTEREND: "afterend",
		BEFOREEND: "beforeend",
		AFTERBEGIN: "afterbegin",
		BEFOREBEGIN: "beforebegin",
	};

	static MODE = {
		REPLACE: "replace",
		INSERT: "insert"
	}

	abort = new AbortController();

	/**
	 * Create a new Vegvisir soft navigation
	 * @param {URL|String|null} href 
	 */
	constructor(href = null) {
		// Create URL object from sources
		switch (href.constructor) {
			case URL:
				this.url = href;
				break;

			case String:
				this.url = new URL(window.location.origin + href);
				break;

			default:
				this.url = window.location;
				break;
		}
	}

	/**
	 * Return an array of id strings for each loaded Vegvisir shell
	 * @returns {Array}
	 */
	#getLoadedShells() {
		return [...document.querySelectorAll("vv-shell")].map(element => element?.getAttribute("vv-shell-id") ?? "root");
	}

	#setTargetHtml(target, html, position , mode) {
		if (mode === Navigation.MODE.REPLACE) {
			target.innerHTML = "";
		}

		target.insertAdjacentHTML(position, html);

		// Rebuild script tags as they don't execute with innerHTML per the HTML spec
		[...target.getElementsByTagName("script")].forEach(script => {
			const tag = document.createElement("script");

			// Assign element attributes
			for (const attribute of script.getAttributeNames()) {
				tag.setAttribute(attribute, script.getAttribute(attribute));
			}
			
			// Scope imported JS by default unless it's an ESM
			tag.innerHTML = script.getAttribute("type") !== "module" ? `{${script.innerText}}` : script.innerHTML;

			script.remove();
			target.appendChild(tag);
		});
	}

	/**
	 * 
	 * @param {HTMLElement} target 
	 * @param {Navigation.POSITION} position 
	 * @param {Navigation.MODE} mode 
	 */
	async #getPage(target, position, mode) {
		// Generate a random id for this request. The worker will return it when a response is ready
		const requestId = new Uint32Array(1);
		window.crypto.getRandomValues(requestId);

		// Register event listener for request
		worker.addEventListener("message", (event) => {
			// Bail out if the requestId does not match, this response is not for us!
			if (event.data.requestId[0] !== requestId[0]) {
				return;
			}

			if (!event.data.responseTarget) {
				return this.#setTargetHtml(target, event.data.responseBody, position, mode);
			}

			return this.#setTargetHtml(
				document.querySelector(event.data.responseTarget),
				event.data.responseBody,
				Navigation.POSITION.SELF,
				Navigation.MODE.REPLACE
			);
		}, { signal: this.abort.signal });

		// Dispatch request to worker
		worker.postMessage({
			requestId: requestId,
			requestUrl: this.url.toString(),
			loadedShells: this.#getLoadedShells()
		});
	}

	/**
	 * Navigate an HTMLElement to the provided URL
	 * @param {HTMLElement|String} target 
	 * @param {Navigation.POSITION} position 
	 * @param {Navigation.MODE} mode 
	 */
	async navigate(target = Navigation.TARGET.TOP, position = Navigation.POSITION.BEFOREEND, mode = Navigation.MODE.REPLACE) {
		if (target === Navigation.TARGET.TOP) {
			target = document.querySelector("[vv-top-page]");
		}

		target.setAttribute("vv-loading", true);

		const page = await this.#getPage(target, position, mode);

		target.setAttribute("vv-loading", false);
	}
}