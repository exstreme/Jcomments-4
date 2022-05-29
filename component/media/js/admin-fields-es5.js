(function () {
	'use strict';

	window.Jcomments = window.Jcomments || {};

	(function (document) {
		var refreshImagePreview = function refreshImagePreview(id, parentEl) {
			var value = document.getElementById(id).value,
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
			var dropdown = document.querySelectorAll('.smiley-refresh');

			dropdown[0].addEventListener('change', function (e) {
				refreshImagePreview(e.target.getAttribute('id'), dropdown[0]);

				return false;
			});
		});
	})(document, Jcomments);
}());
