(function () {
	'use strict';

	window.Jcomments = window.Jcomments || {};

	(function (document) {
		const refreshImagePreview = function refreshImagePreview(id, parentEl) {
			const value = document.getElementById(id).value,
				img = document.getElementById(id + '_preview');

			if (img) {
				if (value) {
					img.src = parentEl.dataset.url + value;
					document.getElementById(id + '_preview_empty').style.display = 'none';
					document.getElementById(id + '_preview_img').style.display = '';
				} else {
					img.src = '';
					document.getElementById(id + '_preview_empty').style.display = '';
					document.getElementById(id + '_preview_img').style.display = 'none';
				}
			}
		}; // Register events

		document.addEventListener('DOMContentLoaded', function () {
			const dropdown = document.querySelectorAll('.smiley-refresh');

			if (dropdown.length > 0) {
				dropdown[0].addEventListener('change', function (e) {
					refreshImagePreview(e.target.getAttribute('id'), dropdown[0]);

					return false;
				});
			}

			const cfg_upload_input = document.querySelector('#upload_config'),
				msg = '#system-message-container';

			document.querySelector('#upload').addEventListener('click', function (e) {
				if (cfg_upload_input.value === '') {
					Joomla.renderMessages({'error': [Joomla.Text._('JLIB_FORM_FIELD_REQUIRED_VALUE')]}, msg);

					return;
				}

				const form_data = new FormData();
				form_data.append('upload_config', cfg_upload_input.files[0]);

				Joomla.request({
					method: 'POST',
					url: cfg_upload_input.dataset.url,
					data: form_data,
					onSuccess: function (response) {
						let _response;

						try {
							_response = JSON.parse(response);
						} catch (e) {
							Joomla.renderMessages({'error': [e]}, msg);

							return;
						}

						if (_response.success) {
							alert(_response.message);
							document.location.reload();
						} else {
							Joomla.renderMessages({'warning': [_response.message]}, msg);
						}
					},
					onError: function (xhr) {
						let response;

						try {
							response = JSON.parse(xhr.responseText);
						} catch (e) {
							response = e;
						}

						Joomla.renderMessages({'error': [response.message]}, msg);
					}
				});
			});
		});
	})(document, Jcomments);
}());
