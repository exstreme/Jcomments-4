// phpcs:disable
/**
 * A JavaScript equivalent of PHP's empty. See https://github.com/locutusjs/locutus/blob/master/src/php/var/empty.js
 *
 * @param   {string}  mixedVar  Value to test.
 * @return  {boolean}
 */
function empty(mixedVar) {
	let undef,
		key,
		i,
		len;
	const emptyValues = [undef, null, false, 0, '', '0'];

	for (i = 0, len = emptyValues.length; i < len; i++) {
		if (mixedVar === emptyValues[i]) {
			return true
		}
	}

	if (typeof mixedVar === 'object') {
		for (key in mixedVar) {
			if (mixedVar.hasOwnProperty(key)) {
				return false
			}
		}

		return true
	}

	return false
}

Jcomments = window.Jcomments || {};

(function (Jcomments, document) {
	'use strict';

	/**
	 * Open new browser window.
	 *
	 * @param   {string}  url       URL to open.
	 * @param   {string}  errorMsg  Error text.
	 *
	 * @return  {void}
	 */
	Jcomments.openWindow = function (url, errorMsg) {
		const handler = window.open(url);

		if (!handler) {
			Joomla.renderMessages({'notice': [empty(errorMsg) ? Joomla.Text._('ERROR') : errorMsg]}, '#system-message-container');
		}
	};

	/**
	 * Resize iframe to iframe content height
	 *
	 * @param   {object}  iframe  Iframe element
	 *
	 * @return  {void}
	 */
	Jcomments.iFrameHeight = function (iframe) {
		const doc = 'contentDocument' in iframe ? iframe.contentDocument : iframe.contentWindow.document;

		iframe.style.height = parseInt(doc.body.scrollHeight, 10) + 'px';
	};

	/**
	 * Load comments for object
	 *
	 * @param   {string}  object_id     Object ID.
	 * @param   {string}  object_group  Object group. E.g. com_content
	 * @param   {number}  page          Limitstart value.
	 *
	 * @return  {void}
	 */
	Jcomments.loadComments = function (object_id, object_group, page) {
		page = parseInt(page, 10);

		const comments_container = document.querySelector('.comments-list-container'),
			id = empty(object_id) ? comments_container.dataset.objectId : object_id,
			group = empty(object_group) ? comments_container.dataset.objectGroup : object_group,
			_page = empty(page) ? '' : '&' + comments_container.dataset.navPrefix + 'limitstart=' + page;

		Jcomments.createLoader(0);

		Joomla.request({
			url: comments_container.dataset.listUrl + '&object_id=' + id + '&object_group=' + group + _page + '&format=raw',
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					comments_container.innerHTML = _response.data.html;
					document.querySelector('.total-comments').textContent = _response.data.total;
					Jcomments.createLoader(100);

					// Handle limitstart value from query string
					if (comments_container.dataset.navPrefix !== '') {
						if ('URLSearchParams' in window) {
							const searchParams = new URLSearchParams(comments_container.dataset.objectUrl);

							if (page > 0) {
								searchParams.set(comments_container.dataset.navPrefix + 'limitstart', page);
							}

							const params = searchParams.toString();
							let newRelativePathQuery;

							if (window.location.pathname.indexOf(decodeURIComponent(params))) {
								newRelativePathQuery = searchParams.toString() + window.location.hash;
							} else {
								newRelativePathQuery = window.location.pathname + '?' + searchParams.toString() + window.location.hash;
							}

							history.pushState(null, '', decodeURIComponent(newRelativePathQuery));
						}
					}

					// Scroll page to an element if hash is present in URL
					Jcomments.scrollToByHash();
				} else {
					Jcomments.showError(null, '.comments-list-header');
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
			}
		});
	}

	/**
	 * Parse json string and catch error
	 *
	 * @param   {string}  response  Response from server
	 *
	 * @return  {string|boolean}   Json object on success, false otherwise.
	 */
	Jcomments.parseJson = function (response) {
		try {
			response = JSON.parse(response);

			return response;
		} catch (e) {
			return false;
		}
	}

	Jcomments.showError = function (response, selector) {
		const _response = Jcomments.parseJson(response);

		Joomla.renderMessages(
			{
				'warning': [empty(_response) ? Joomla.Text._('ERROR') : (empty(_response.message) ? _response : _response.message)]
			},
			selector
		);
	}

	Jcomments.createLoader = function (value) {
		const loaderEl = document.querySelector('.jc-loader');

		value = parseInt(value, 10);

		if (loaderEl) {
			loaderEl.style.display = '';
			const loaderBar = document.querySelector('.jc-loader .progress-bar');
			loaderBar.style.width = value + '%';
			loaderBar.setAttribute('aria-valuenow', value);

			setTimeout(function () { loaderEl.style.display = 'none'; }, 1000);
		} else {
			document.querySelector('body').insertAdjacentHTML(
				'beforeend',
				'<div class="jc-loader progress rounded-0" style="height: 3px; top: 0; position: fixed; width: 100%; left: 0;"><div class="progress-bar bg-secondary" role="progressbar" aria-label="Comments loading" style="width: ' + value + '%;" aria-valuenow="' + value + '" aria-valuemin="0" aria-valuemax="100"></div></div>'
			);
		}
	}

	/**
	 * Set the current vertical position of the scroll bar for each of the set of matched elements.
	 *
	 * @param   {Window|Node}  el     HTML element
	 * @param   {int}          value  A number indicating the new position to set the scroll bar to.
	 *
	 * @return  {int|void}
	 */
	Jcomments.scrollTop = function (el, value) {
		if (value === undefined) {
			return el.pageYOffset;
		} else {
			// See https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
			if (el === window || el.nodeType === 9) {
				el.scrollTo(el.pageXOffset, value);
			} else {
				el.pageYOffset = value;
			}
		}
	}

	/**
	 * Get the current coordinates of the first element in the set of matched elements, relative to the document.
	 *
	 * @param   {HTMLElement}  el  HTML element
	 *
	 * @return  {object}  An object containing the properties top and left.
	 */
	Jcomments.offset = function (el) {
		const box = el.getBoundingClientRect();
		const docElem = document.documentElement;

		return {
			top: box.top + window.pageYOffset - docElem.clientTop,
			left: box.left + window.pageXOffset - docElem.clientLeft
		};
	}

	Jcomments.scrollToByHash = function (url) {
		let elComment = null;

		if (empty(url)) {
			if (window.location.hash) { // Check if # present in url.
				elComment = document.querySelector(window.location.hash); // Search element with id="{window.location.hash}"
			}
		} else {
			elComment = document.querySelector('#' + url.split('#')[1]);
		}

		if (elComment) {
			Jcomments.scrollTop(window, Jcomments.offset(elComment).top);
		}
	}
}(Jcomments, document));

jQuery(document).ready(function ($) {
	const comments_container = $('div.comments-list-container');
	const formAddIframe = $('iframe.commentsFormFrame');
	let iframeAddDom;

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

		Jcomments.loadComments('', '', page);

		comments_container.on('click', 'a.page-link', function (e) {
			e.preventDefault();

			if ($(this).hasClass('hasPages')) {
				Jcomments.loadComments('', '', parseInt(this.dataset.page, 10));
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
			Jcomments.scrollToByHash(url);
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

	/** @method JcommentsEdit() */
	comments_container.on('click', '.toolbar-button-edit', function (e) {
		e.preventDefault();

		Jcomments.createLoader(0);

		const iframes = document.querySelectorAll('.commentsEditFormFrame');
		const formAddLayout = $('.form-layout', iframeAddDom);

		// Remove all 'Edit form' iframes
		if (iframes.length > 0) {
			for (const el of iframes) {
				el.remove();
			}
		}

		const randomArr = new Uint32Array(1);
		const iframeHtml = document.createElement('iframe');
		const parentDiv = this.closest('div.comment');

		self.crypto.getRandomValues(randomArr);

		iframeHtml.setAttribute('width', '100%');
		iframeHtml.setAttribute('onload', 'Jcomments.iFrameHeight(this);');
		iframeHtml.setAttribute('style', 'overflow: hidden; height: 0;');
		iframeHtml.setAttribute('scrolling', 'no');
		iframeHtml.setAttribute('class', 'commentsEditFormFrame');
		iframeHtml.setAttribute('id', 'editcomments');
		iframeHtml.setAttribute('name', randomArr[0].toString());
		iframeHtml.setAttribute('src', this.dataset.editUrl + '&_=' + new Date().getTime());

		// Insert iframe after the comment but inside the cotnainer and scroll to comment header.
		parentDiv.parentNode.insertBefore(iframeHtml, parentDiv.nextSibling);

		$(iframeHtml).on('load', function () {
			const iframeEditDom = $(this).contents();

			if ($('form', iframeEditDom).length > 0) {
				// Hide 'Add new comment' form iframe
				if (!formAddLayout.hasClass('d-none')) {
					formAddLayout.addClass('d-none');
					$('.showform-btn-container', iframeAddDom).removeClass('d-none');

					if (typeof iframeAddDom !== 'undefined') {
						formAddIframe.css('height', $('.showform-btn-container', iframeAddDom).outerHeight(true));
					}
				}
			}

			Jcomments.createLoader(100);
			Jcomments.scrollTop(window, Jcomments.offset(iframeHtml).top);

			/** @method JcommentsFormEditSubmit() */
			iframeEditDom.on('click', '#comments-form-send', function () {
				e.preventDefault();

				alert('Edit');
				return false;
			});

			/** @method JcommentsFormEditCancel() */
			iframeEditDom.on('click', '#comments-form-cancel', function () {
				// 'Edit comment' form
				$('.commentsEditFormFrame').slideUp(400, function () {
					$(this).remove();
					Jcomments.scrollTop(window, Jcomments.offset(parentDiv).top);
				});

				// If 'Add new comment' form is hidden by default, leave it hidden after cancel edit.
				if (!formAddLayout.hasClass('d-none') || formAddLayout.hasClass('show')) {
					formAddLayout.removeClass('d-none');
					$('.showform-btn-container', iframeAddDom).addClass('d-none');
				}
			});
		});
	});

	/** @method JcommentsPublish() */
	/** @method JcommentsUnpublish() */
	comments_container.on('click', '.toolbar-button-state', function (e) {
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

					$this.attr('title', _response.data.title);
					$this.removeData('url'); // Remove from internal cache
					$this.data('url', _response.data.url);

					if (_response.data.current_state === 0) {
						$this.find('span').removeClass('icon-unpublish link-secondary').addClass('icon-publish link-success');
						comment.addClass('bg-secondary bg-opacity-10 text-muted');
						comment.find('.user-panel a').addClass('pe-none').prop('aria-disabled', 'true');
					} else {
						$this.find('span').removeClass('icon-publish link-success').addClass('icon-unpublish link-secondary');
						comment.removeClass('bg-secondary bg-opacity-10 text-muted');
						comment.find('.user-panel a').removeClass('pe-none').removeProp('aria-disabled');
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
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

			$('#reportFormFrame').prop('src', $(this).data('url') + '&_=' + Date.now());
			reportModal.show(reportModal);
		}
	});

	comments_container.on('click', '.toolbar-button-child-toggle',function (e) {
		e.preventDefault();

		const list = $(this).closest('div.comment-container').next('.comments-list-child');

		if (list.filter(':hidden').length > 0) {
			$(this).attr('title', $(this).data('title-hide'));
			$('span', this).removeClass('icon-chevron-down').addClass('icon-chevron-up');
			list.slideDown();
		} else {
			$(this).attr('title', $(this).data('title-show'));
			$('span', this).removeClass('icon-chevron-up').addClass('icon-chevron-down');
			list.slideUp();
		}
	});

	/** @method JcommentsQuote() */
	comments_container.on('click', '.toolbar-button-quote', function (e) {
		e.preventDefault();

		const $this = $(this),
			comment = $this.closest('div.comment'),
			$msg = '#comment-item-' + comment.data('id');

		// TODO Not implemented
		alert('Not implemented.');
	});

	/** @method JcommentsSubscribe() */
	/** @method JcommentsUnsubscribe() */
	$('.cmd-subscribe').on('click',function (e) {
		e.preventDefault();

		let $this = $(this),
			$msg = '.comments-list-footer';

		Joomla.request({
			url: $this.attr('href') + '&format=json',
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response);

				if (_response !== false && _response.success) {
					$this.attr({
						href: _response.data.href,
						title: _response.data.title
					});
					$this.html('<span aria-hidden="true" class="icon-mail icon-fw me-1"></span>' + _response.data.title);

					Joomla.renderMessages({'message': [_response.message]}, $msg);
				} else {
					Jcomments.showError(null, $msg);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, $msg);
			}
		});
	});

	// Bind events on elements in iframe. If iframe document loading directly in browser window the events will not binded.
	formAddIframe.on('load', function () {
		iframeAddDom = formAddIframe.contents();

		// NOTE! We need this here because iframe loaded after the main document and the page did not scrolled to a proper element position.
		Jcomments.scrollToByHash();

		if (window.parent.location.hash === '#addcomments') {
			$(window.parent).scrollTop($(parent.document).find('#addcomments').offset().top);
		}

		if ($('.cmd-showform', iframeAddDom).hasClass('show')) {
			formAddIframe.css('height', parseInt(iframeAddDom.find('.showform-btn-container').outerHeight(), 10) + 10 + 'px');
			$('.form-layout', iframeAddDom).addClass('d-none');
		} else {
			formAddIframe.css('height', parseInt(iframeAddDom.find('body').outerHeight() - iframeAddDom.find('.showform-btn-container').outerHeight(), 10) + 'px');
			$('.showform-btn-container', iframeAddDom).addClass('d-none');
			$('.form-layout', iframeAddDom).removeClass('d-none');
		}

		/** @method JcommentsFormAddShow() */
		iframeAddDom.on('click', '.cmd-showform', function (e) {
			e.preventDefault();

			$('.commentsEditFormFrame').remove();
			$('.form-layout', iframeAddDom).removeClass('d-none');
			$('.showform-btn-container', iframeAddDom).addClass('d-none');

			formAddIframe.css('height', parseInt(iframeAddDom.find('body').outerHeight(), 10) + 10 + 'px');

			const iframeWrapper = $(parent.document).find('.commentsFormWrapper');

			if (iframeWrapper.length > 0) {
				$(window.parent).scrollTop(iframeWrapper.offset().top);
			} else {
				// Form inside the second iframe. Unexpected behavior
				$(document).scrollTop($(document).find('.commentsFormWrapper').offset().top);
			}
		});

		/** @method JcommentsFormAddSubmit() */
		iframeAddDom.on('click', '#comments-form-send', function (e) {
			e.preventDefault();

			alert('Add');
			return false;
		});

		/** @method JcommentsFormAddCancel() */
		iframeAddDom.on('click', '#comments-form-cancel', function () {
			// 'Add new comment' form Cancel button.
			if (empty($('#jform_comment_id', iframeAddDom).val()) && !$('#editForm', iframeAddDom).hasClass('show')) {
				$('.form-layout', iframeAddDom).addClass('d-none');
				$('.showform-btn-container', iframeAddDom).removeClass('d-none');
				formAddIframe.css('height', parseInt(iframeAddDom.find('.showform-btn-container').outerHeight(), 10) + 10 + 'px');
			}
		});
	});
});
