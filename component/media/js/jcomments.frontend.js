// phpcs:disable
jQuery(document).ready(function ($) {
	const comments_container = $('div.comments-list-container');

	if (comments_container.data('load') === 'dynamic') {
		let page = 0;

		if ('URLSearchParams' in window) {
			// Get limitstart value from query to load comments page
			let searchParams = new URLSearchParams(window.location.search),
				paramName = comments_container.data('nav-prefix') + 'limitstart';

			if (searchParams.has(paramName) && searchParams.get(paramName)) {
				page = searchParams.get(paramName);
			}
		}

		Jcomments.loadComments('', '', page, true);

		comments_container.on('click', 'a.page-link', function (e) {
			e.preventDefault();

			if (this.classList.contains('hasPages')) {
				Jcomments.loadComments('', '', parseInt(this.dataset.page, 10));
				Jcomments.scrollToByHash('comments');
			}
		});
	} else {
		// Keep location hash in pagination links for scroll to comments after page loads.
		if (window.location.hash) { // Check if # present in url.
			const hash = window.location.href.split('#')[1];

			if (hash === 'comments') {
				$('.comments .pagination a').each(function () {
					// Hash not found in link
					if (typeof $(this).attr('href').split('#')[1] === 'undefined') {
						$(this).attr('href', $(this).attr('href') + '#' + hash);
					}
				});
			}
		}
		// End
	}

	/** @method JcommentsParentlink() */
	comments_container.on('click', '.parent-comment-link a', function (e) {
		e.preventDefault();

		const url = $(this).attr('href'),
			hash = url.split('#')[1];

		if ($('div#' + hash).length > 0) {
			Jcomments.scrollToByHash(hash);
		} else {
			if (comments_container.data('template') === 'list' && comments_container.data('load') === 'dynamic') {
				// TODO Получить значение page в контролере
				Jcomments.loadComments('', '', -1);
			} else {
				window.location.href = url;
			}
		}
	});

	/** @method JcommentsVote() */
	comments_container.on('click', '.toolbar-button-vote', function (e) {
		e.preventDefault();

		const $this = $(this),
			comment = $this.closest('div.comment');

		Joomla.request({
			method: 'POST',
			url: $this.data('url') + '&format=json',
			data: '&id=' + comment.data('id'),
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					comment.find('.vote-up, .vote-down').remove();

					comment.find('.vote-result span').replaceWith(_response.data);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + comment.data('id'));
			}
		});
	});

	/** @method JcommentsPublish() */
	/** @method JcommentsUnpublish() */
	comments_container.on('click', '.toolbar-button-state', function (e) {
		e.preventDefault();

		const $this = $(this),
			comment = $this.closest('div.comment'),
			$msg = '#comment-item-' + comment.data('id');

		$this.addClass('pe-none');

		Joomla.request({
			method: 'POST',
			url: $this.data('url') + '&format=json',
			data: '&id=' + comment.data('id'),
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					Joomla.renderMessages({'message': [_response.message]}, $msg);

					$this.attr('title', _response.data.title);
					$this.removeData('url'); // Remove from internal cache
					$this.data('url', _response.data.url);

					if (_response.data.current_state === 0) {
						$this.find('span').removeClass('icon-unpublish link-secondary').addClass('icon-publish link-success');
						comment.addClass('bg-light text-muted');
						comment.find('.user-panel a').addClass('pe-none').prop('aria-disabled', 'true');
					} else {
						$this.find('span').removeClass('icon-publish link-success').addClass('icon-unpublish link-secondary');
						comment.removeClass('bg-light text-muted');
						comment.find('.user-panel a').removeClass('pe-none').removeProp('aria-disabled');
					}
				}

				$this.removeClass('pe-none');
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
				$this.removeClass('pe-none');
			}
		});
	});

	/** @method JcommentsDelete() */
	comments_container.on('click', '.toolbar-button-delete', function (e) {
		e.preventDefault();

		if (!confirm(Joomla.Text._('BUTTON_DELETE_CONIRM'))) {
			return false;
		}

		const $this = $(this),
			comment = $this.closest('div.comment'),
			$msg = '#comment-item-' + comment.data('id');

		Joomla.request({
			method: 'POST',
			url: $this.data('url') + '&format=json',
			data: '&id=' + comment.data('id'),
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					Joomla.renderMessages({'message': [_response.message]}, $msg);

					const totalSpan = $('.total-comments'),
						total = parseInt(totalSpan.text(), 10);

					if (total >= 0) {
						totalSpan.text(total - 1);
					}

					$('#comment-' + comment.data('id')).replaceWith(_response.data);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
			}
		});
	});

	/** @method JcommentsBan() */
	comments_container.on('click', '.toolbar-button-ban', function (e) {
		e.preventDefault();

		const $this = $(this),
			comment = $this.closest('div.comment'),
			$msg = '#comment-item-' + comment.data('id');

		Joomla.request({
			method: 'POST',
			url: $this.data('url') + '&format=json',
			data: '&id=' + comment.data('id'),
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					Joomla.renderMessages({'message': [_response.message]}, $msg);
					$this.prev('.toolbar-button-ip').css('text-decoration', 'line-through').addClass('link-secondary');
					$this.remove();
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
			}
		});
	});

	// We set the initial value of the height due to the fact that the 'iframe-height' script adds a new height to
	// the current one. Current iframe content height + (20px or 60 px).
	comments_container.on('hidden.bs.modal', '#reportModal', function () {
		$('#reportFormFrame').css('height', 0);
	});

	/** @method JcommentsReport() */
	comments_container.on('click', '.toolbar-button-report', function (e) {
		e.preventDefault();

		const reportModalEl = $('#reportModal');

		if (reportModalEl)
		{
			const reportModal = new bootstrap.Modal('#reportModal');

			$('#reportFormFrame').prop('src', this.dataset.url + '&_=' + Date.now());
			reportModal.show(reportModal);
		}
	});

	comments_container.on('click', '.toolbar-button-child-toggle',function (e) {
		e.preventDefault();

		const _this = this, span = _this.querySelector(':scope > span'),
			list = Jcomments.next(_this.closest('.comment-container'), '.comments-list-child');

		if (list === null) {
			return;
		}

		if (!(list.offsetWidth || list.offsetHeight || list.getClientRects().length)) {
			Jcomments.slideDown(list, 400, function () {
				_this.setAttribute('title', _this.dataset.titleHide);
				span.classList.replace('icon-chevron-down', 'icon-chevron-up');
			});
		} else {
			Jcomments.slideUp(list, 400, function () {
				_this.setAttribute('title', _this.dataset.titleShow);
				span.classList.replace('icon-chevron-up', 'icon-chevron-down');
			});
		}
	});

	/** @method JcommentsSubscribe() */
	/** @method JcommentsUnsubscribe() */
	$('.cmd-subscribe').on('click',function (e) {
		e.preventDefault();

		const _this = this,
			_msg = '.comments-list-footer';

		Joomla.request({
			url: _this.getAttribute('href') + '&format=json',
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					_this.setAttribute('href', _response.data.href);
					_this.setAttribute('title', _response.data.title);
					_this.innerHTML = '<span aria-hidden="true" class="fa icon-mail me-1"></span>' + _response.data.title;

					Joomla.renderMessages({'message': [_response.message]}, _msg);
				} else {
					Jcomments.showError(null, _msg);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, _msg);
			}
		});
	});
});

document.addEventListener('DOMContentLoaded', function () {
	const addCommentFrame = document.querySelector('.commentFormFrame');

	if (addCommentFrame) {
		addCommentFrame.addEventListener('load', function () {
			Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'), '.showform-btn-container', true);

			if (window.parent.location.hash === '#addcomment') {
				Jcomments.scrollToByHash('#addcomment');
			}
		});
	}
});
