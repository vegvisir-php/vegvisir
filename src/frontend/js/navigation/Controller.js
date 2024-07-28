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
	options = {
		pushHistory: true
	}

	/**
	 * Create a new Vegvisir soft navigation
	 * @param {URL|String|null} href 
	 * @param {Object} options
	 */
	constructor(href = null, options = {}) {
		// Merge options with defaults
		Object.assign(this.options, options);

		// Create URL object from sources
		switch (href.constructor) {
			case URL:
				this.url = href;
				break;

			case String:
				try {
					this.url = new URL(href);
				} catch {
					this.url = new URL(window.location.origin + href);
				}
				break;

			default:
				this.url = window.location;
				break;
		}
	}

	/**
	 * Return the top most shell element
	 * @returns {HTMLVegvisirShellElement}
	 */
	static get #rootShellElement() {
		return document.querySelector("vv-shell[vv-shell-id='/']");
	}

	/**
	 * Return an array of id strings for each loaded Vegvisir shell
	 * @returns {Array}
	 */
	static get #loadedShells() {
		return [...document.querySelectorAll("vv-shell")].map(element => element?.getAttribute("vv-shell-id"));
	}

	/**
	 * 
	 * @param {MouseEvent} event 
	 */
	static #clickEventHandler(event) {
		const element = event.target.closest(":is(a, [vv])");
		const nav = new Navigation(element.href ?? element.getAttribute("vv"));

		// Bail out if the main mouse button was not pressed or destination is on another origin
		if (event.button !== 0) {
			return;
		}
		
		const target = element.getAttribute("target") ?? Navigation.TARGET.TOP;
		const mode = element.getAttribute("vv-mode") ?? Navigation.MODE.REPLACE;
		const position = element.getAttribute("vv-position") ?? Navigation.POSITION.BEFOREEND;

		event.preventDefault();

		switch (target) {
			// Navigate with clicked anchor tag as target
			case Navigation.TARGET.SELF:
				// Replace anchor tag with page contents if inner DOM is being modified
				if ([Navigation.POSITION.BEFOREEND, Navigation.POSITION.AFTERBEGIN].includes(position)) {
					// Append loaded content after anchor tag
					nav.navigate(element, Navigation.POSITION.AFTEREND, mode);
					// Remove the anchor tag element
					return element.remove();
				}

				nav.navigate(element);
				break;

			// Default browser behavior
			case Navigation.TARGET.BLANK:
				open(element.href);
				break;

			// Navigate with closest HTMLVegvisirShellElement as the target
			case Navigation.TARGET.PARENT:
				nav.navigate(element.closest("vv-shell"));
				break;

			// Navigate with the top most HTMLVegvisirShellElement as the target
			case Navigation.TARGET.TOP:
				nav.navigate(Navigation.#rootShellElement);
				break;

			default:
				nav.navigate(document.querySelector(target));
				break;
		}
	}

	// Bind listeners to unbound anchor tags in the DOM
	static bindElements() {
		[...document.querySelectorAll(":is(a, [vv]):not([vv-bound])")].forEach(element => {
			// Mark this anchor tag as bound
			element.setAttribute("vv-bound", true);

			element.addEventListener("click", (event) => Navigation.#clickEventHandler(event));
		});
	}

	#pushHistory() {
		// Bail out if history push has been disabled
		if (!this.options.pushHistory) {
			return;
		}

		window.history.pushState({
			url: this.url.toString(),
			options: this.options
		}, "", this.url.toString());
	}

	/**
	 * 
	 * @param {HTMLElement} target 
	 * @param {String} html 
	 * @param {Navigation.POSITION} position 
	 * @param {Navigation.MODE} mode 
	 */
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

		// Bind new anchor tags
		Navigation.bindElements();
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
				// Use root shell if target shell id can not be found
				document.querySelector(`vv-shell[vv-shell-id="${event.data.responseTarget}"]`) ?? Navigation.#rootShellElement,
				event.data.responseBody,
				// Replace inner DOM with response body
				Navigation.POSITION.BEFOREEND,
				Navigation.MODE.REPLACE
			);
		}, { signal: this.abort.signal });

		// Dispatch request to worker
		worker.postMessage({
			requestId: requestId,
			requestUrl: this.url.toString(),
			loadedShells: Navigation.#loadedShells
		});
	}

	/**
	 * Navigate an HTMLElement or Navigation.TARGET to the instanced URL
	 * @param {HTMLElement|Navigation.TARGET} target 
	 * @param {Navigation.POSITION} position 
	 * @param {Navigation.MODE} mode 
	 */
	async navigate(target = Navigation.#rootShellElement, position = Navigation.POSITION.BEFOREEND, mode = Navigation.MODE.REPLACE) {
		// Bail out if target is falsy
		if (!target) {
			console.warn("Vegvisir:Navigate:Failed to soft-navigate falsy target");
			return;
		}

		target.setAttribute("vv-loading", true);
		
		await this.#getPage(target, position, mode);
		
		target.setAttribute("vv-loading", false);
		target.setAttribute("vv-page", this.url.pathname);

		if (target === Navigation.#rootShellElement) {
			// Set current top page pathname on body tag if root shell is the target
			document.body.setAttribute("vv-top-page", this.url.pathname);

			this.#pushHistory();
		}
	}

}