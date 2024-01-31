// phpcs:disable
document.addEventListener('DOMContentLoaded', function () {
	const comments_container = document.querySelector('div.comments-list-container');
	let addCommentFrame = document.querySelector('.commentFormFrame');

	if (comments_container) {
		// Load comments on document ready
		let page = 0;

		if ('URLSearchParams' in window) {
			// Get limitstart value from query to load comments page
			let searchParams = new URLSearchParams(window.location.search),
				paramName = comments_container.dataset.paginationPrefix + 'limitstart';

			if (searchParams.has(paramName) && searchParams.get(paramName)) {
				page = searchParams.get(paramName);
			}
		}

		Jcomments.loadComments(null, '', '', page, true);
		// End
	}

	// Set the initial value of the height due to the fact that the 'iframe-height' script adds a new height to
	// the current one. Current iframe content height + (20px or 60 px).
	const report_iframe = document.querySelector('#reportFormFrame');

	if (report_iframe) {
		const report_loader = document.querySelector('.report-loader');

		document.querySelector('#reportModal').addEventListener('hidden.bs.modal', function () {
			report_iframe.style.height = 0;
			Jcomments.getIframeContent(report_iframe).querySelector('body').remove();
			report_loader.classList.remove('d-none');
		});
		report_iframe.addEventListener('load', function () {
			report_loader.classList.add('d-none');
		});
	}

	if (addCommentFrame) {
		addCommentFrame.addEventListener('load', function () {
			const parentIframe = addCommentFrame,
				formShow = Joomla.getOptions('jcform').form_show;

			// Do iframe resize after iframe load.
			if (parentIframe) {
				let parentIframeContent = Jcomments.getIframeContent(parentIframe);
				const jc_alerts = parentIframeContent.querySelectorAll('.jc-message');

				jc_alerts.forEach(function (el) {
					el.addEventListener('closed.bs.alert', function(e){
						Jcomments.hideEditForm(e.target);
					});
				});

				// Test if form iframe have a button, not an error message or form.
				if (!!parentIframeContent.querySelector('.showform-btn-container')) {
					Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'), '.showform-btn-container', true);
				}
				// Test for error messages. At least one should exist
				else if (!!parentIframeContent.querySelector('.jc-message')) {
					Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'), '.jc-message', true);
				} else {
					if (!!parentIframeContent.querySelector('#editForm')) {
						Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'), 'body', true);
					} else {
						// Something wrong! Hide iframe
						Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'), null, false, 0);
					}
				}
			}

			if (window.parent.location.hash === '#addcomment') {
				Jcomments.scrollToByHash('#addcomment');
			}
		});
	}
});
