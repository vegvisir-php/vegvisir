class HTMLVegvisirShellElement extends HTMLElement {
	isRootShell = false;

	constructor() {
		super();

		// This element is the root shell
		if (this.parentElement === document.body) {
			this.isRootShell = true;
			this.#setShellId("/");
		}

		this.setLoading(false);
	}

	/**
	 * 
	 * @param {String} id 
	 */
	#setShellId(id = "") {
		this.setAttribute("vv-shell-id", id);
	}

	/**
	 * 
	 * @param {Boolean} state 
	 */
	setLoading(state = true) {
		this.setAttribute("vv-loading", state);
	}

	/**
	 * 
	 * @param {URL|String|null} url 
	 * @param {vegvisir.Navigation.POSITION} position 
	 * @param {vegvisir.Navigation.MODE} mode 
	 * @returns 
	 */
	async navigate(url, position, mode) {
		return await (new vegvisir.Navigation(url)).navigate(this, position, mode);
	}
}

window.customElements.define("vv-shell", HTMLVegvisirShellElement);