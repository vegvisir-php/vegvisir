class HTMLVegvisirShellElement extends HTMLElement {
	isTopShell = false;

	constructor() {
		super();

		if (this.parentElement === document.body) {
			this.isTopShell = true;
			this.setAttribute("vv-top-page", window.location.pathname);
		}
	}
}

window.customElements.define("vv-shell", HTMLVegvisirShellElement);