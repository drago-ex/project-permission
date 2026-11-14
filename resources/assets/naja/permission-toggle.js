export default class PermissionToggle {
	initialize(naja) {
		const init = (doc) => {
			if (!doc) {
				return;
			}

			doc.querySelectorAll('.js-permission-toggle').forEach((input) => {
				if (input.dataset.permissionToggleInitialized === 'true') {
					return;
				}

				input.dataset.permissionToggleInitialized = 'true';
				input.addEventListener('change', () => {
					naja.makeRequest(
						'POST',
						input.dataset.url,
						{
							allowed: input.checked ? 1 : 0,
						},
						{
							history: input.dataset.najaHistory !== 'off',
						},
						// the list is drawn again with the new state, so the focus goes back to the same switch
					).then(() => document.getElementById(input.id)?.focus({ preventScroll: true }), () => {});
				});
			});
		};

		init(document);
		naja.snippetHandler.addEventListener('afterUpdate', (event) => {
			init(event.detail.snippet);
		});
	}
}
