class HTMLVegvisirShellElement extends HTMLElement {
	isRootShell = false;

	constructor() {
		super();

		// This element is the root shell
		if (this.parentElement === document.body) {
			this.isRootShell = true;
			this.setShellId("/");
		}

		this.setLoading(false);
	}

	/**
	 * 
	 * @param {String} id 
	 */
	setShellId(id = "") {
		this.setAttribute("vv-shell-id", id);
	}

	/**
	 * 
	 * @param {Boolean} state 
	 */
	setLoading(state = true) {
		this.setAttribute("vv-loading", state);
	}
}

window.customElements.define("vv-shell", HTMLVegvisirShellElement);