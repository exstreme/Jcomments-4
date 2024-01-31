// phpcs:disable
Jcomments = window.Jcomments || {};

(function (Jcomments, document) {
	'use strict';

	/**
	 * Open new browser window.
	 *
	 * @param   {string}  url       URL to open.
	 * @param   {string}  errorMsg  Error text.
	 *
	 * @method  Jcomments.openWindow
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
	 * @param   {HTMLIFrameElement}  iframe         Iframe element
	 * @param   {string|null}        el             Selector for an element. If set, iframe will be resized to this
	 *                                              element height.
	 * @param   {boolean}            margins        Add top and bottom margins from element.
	 * @param   {string|int}         defaultHeight  Default iframe height.
	 *
	 * @method  Jcomments.iframeHeight
	 * @return  {int}  Current calculated height in pixels or default value on error.
	 */
	Jcomments.iframeHeight = function (iframe, el, margins, defaultHeight) {
		const doc = Jcomments.getIframeContent(iframe);
		let height;

		if (empty(doc) || el === null) {
			if (defaultHeight !== '') {
				defaultHeight = parseInt(defaultHeight, 10);
				height = !isNaN(defaultHeight) ? defaultHeight : 250;
			} else {
				height = parseInt(iframe.body.scrollHeight, 10);
			}

			iframe.style.height = height + 'px';

			return height;
		}

		if (!empty(el)) {
			const _el = doc.querySelector(el);

			if (_el !== null) {
				const style = window.getComputedStyle(_el);

				height = parseInt(_el.scrollHeight, 10);

				if (margins === true) {
					height = height + parseInt(style.getPropertyValue('margin-top'), 10) + parseInt(style.getPropertyValue('margin-bottom'), 10);
				}
			}
		} else {
			height = parseInt(doc.body.scrollHeight, 10);
		}

		iframe.style.height = height + 'px';

		return height;
	};

	/**
	 * Load comments for object
	 *
	 * @param   {HTMLElement|null}  el            DOM element. If null when default 'refresh-list' will be use.
	 * @param   {string}            object_id     Object ID.
	 * @param   {string}            object_group  Object group. E.g. com_content
	 * @param   {string|number}     page          Limitstart value.
	 * @param   {boolean}           scroll        Scroll the page to the comments if the user came by the link
	 * 									          from 'Readmore block' or `scrollTo` not an empty string. Default: false.
	 * @param   {string}   scrollTo               Scroll page to element with this ID. E.g. #comments
	 *
	 * @method  Jcomments.loadComments
	 * @return  {void}
	 */
	Jcomments.loadComments = function (el, object_id, object_group, page, scroll, scrollTo) {
		page = parseInt(page, 10);

		const comments_container = document.querySelector('.comments-list-container'),
			id = empty(object_id) ? comments_container.dataset.objectId : object_id,
			group = empty(object_group) ? comments_container.dataset.objectGroup : object_group,
			limitstart_varname = comments_container.dataset.paginationPrefix + 'limitstart',
			_page = empty(page) ? '' : '&' + limitstart_varname + '=' + page;

		Joomla.request({
			url: comments_container.dataset.listUrl + '&object_id=' + id + '&object_group=' + group + _page + '&format=raw' + '&_=' + new Date().getTime(),
			onBefore: function () {
				// Do not show loader on first run
				if (comments_container.querySelectorAll('div.comments-list-parent').length === 1) {
					Jcomments.loader(1);
				}

				if (el === null) {
					document.querySelectorAll('.refresh-list').forEach(function (el) {
						el.classList.add('pe-none');
						el.querySelector('span.icon').classList.add('refresh-list-spin');
					});
				} else {
					el.classList.add('pe-none');
					el.querySelector('span.icon').classList.add('refresh-list-spin');
				}
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					comments_container.dataset.template = response.data.type;
					comments_container.innerHTML = response.data.html;
					document.querySelector('.total-comments').textContent = response.data.total;

					// Handle limitstart value from query string
					const searchParams = Jcomments.queryString.init(comments_container.dataset.objectUrl);

					if (searchParams) {
						// Change limitstart value in URL
						if (page > 0) {
							searchParams.set(limitstart_varname, page);
						} else if (page === 0 || page === '') {
							// Remove empty limitstart from query string
							searchParams.delete(limitstart_varname);
						}

						const params = searchParams.toString();
						let newRelativePathQuery;

						newRelativePathQuery = window.location.pathname + '?' + searchParams.toString() + window.location.hash;
						history.pushState(null, '', decodeURIComponent(newRelativePathQuery));
					}

					if (scroll) {
						if (window.location.hash === '#comments' || scrollTo === '#comments') {
							Jcomments.scrollToByHash('#comments');
						}
					}
				} else {
					Jcomments.showError(null, '.comments-list-header');
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '.comments-list-header');
			},
			onComplete: function () {
				Jcomments.loader();
				document.querySelectorAll('.refresh-list').forEach(function (el) {
					el.classList.remove('pe-none');
					el.querySelector('span.icon').classList.remove('refresh-list-spin');
				});
			}
		});
	}

	/**
	 * Preview comment
	 *
	 * @param   {HTMLButtonElement}  el       Button.
	 * @param   {boolean}            preview  Preview comment if false, save otherwise.
	 *
	 * @method  Jcomments.saveComment
	 * @return  {void}
	 * @todo Refactor
	 */
	Jcomments.saveComment = function (el, preview) {
		const jce_config = Joomla.getOptions('jceditor'),
			doc = Jcomments.isIframe() ? parent.document : document,
			form = document.getElementById('adminForm');
		let form_data = new FormData(form),
			queryString = '';

		if (preview === true) {
			form_data.set('task', 'comment.preview');
		}

		const editor = sceditor.instance(document.getElementById(jce_config.field)),
			length = parseInt(editor.val().length, 10),
			comment_id = document.getElementById('jform_comment_id') ? parseInt(document.getElementById('jform_comment_id').value, 10) : 0,
			parent_id = document.getElementById('jform_parent') ? parseInt(document.getElementById('jform_parent').value, 10) : 0,
			iframeName = comment_id > 0 ? '.commentEditFormFrame' : '.commentFormFrame',
			minlength = parseInt(jce_config.minlength, 10), maxlength = parseInt(jce_config.maxlength, 10);

		if (minlength > 0 && maxlength > 0) {
			if (!(length >= minlength)) {
				Jcomments.showError({'message': Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_SHORT')}, '#system-message-container');
				Jcomments.iframeHeight(doc.querySelector(iframeName));

				return;
			}

			if (!(length <= maxlength)) {
				Jcomments.showError({'message': Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_LONG')}, '#system-message-container');
				Jcomments.iframeHeight(doc.querySelector(iframeName));

				return;
			}
		}

		if ('URLSearchParams' in window) {
			queryString = new URLSearchParams(form_data).toString();
		}

		Joomla.request({
			url: form.getAttribute('action') + '&format=json',
			method: 'POST',
			data: queryString,
			onBefore: function () {
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				const previewDiv = document.querySelector('.comment-preview');

				response = Jcomments.parseJson(response);

				if (previewDiv) {
					previewDiv.remove();
				}

				if (preview === true) {
					if (!response) {
						Joomla.renderMessages({'error': [Joomla.Text._('ERROR')]}, '#system-message-container');

						return;
					}

					if (Jcomments.isIframe()) {
						el.closest('#adminForm').insertAdjacentHTML('afterbegin', response.data);

						if (comment_id > 0 || parent_id > 0) {
							Jcomments.iframeHeight(doc.querySelector('.commentEditFormFrame'));
						} else {
							Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'));
						}

						Jcomments.scrollToByHash('comment-item-preview', 'frame');
					} else {
						doc.querySelector('#adminForm').insertAdjacentHTML('afterbegin', response.data);
						Jcomments.scrollToByHash('comment-item-preview');
					}
				} else {
					if (response.success) {
						Joomla.renderMessages({'message': [response.message]}, '#system-message-container');
					} else {
						Jcomments.showError(xhr.response, '#system-message-container');
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#system-message-container');
			},
			onComplete: function () {
				Jcomments.loader();
			}
		});
	}

	/**
	 * Reply to comment or reply with quote.
	 *
	 * @param   {HTMLElement}  el  This element.
	 *
	 * @method  Jcomments.showEditForm
	 * @return  {void}
	 */
	Jcomments.showEditForm = function (el) {
		const commentId = parseInt(el.closest('.comment').dataset.id, 10);

		Jcomments.loader(1);

		const iframes = document.querySelectorAll('.commentEditFormFrame');

		if (iframes.length > 0) {
			for (const el of iframes) {
				// TODO Unbind iframe events

				el.remove();
			}
		}

		const iframeHtml = Jcomments.createIframe(el),
			parentDiv = el.closest('div.comment');

		// Insert iframe after the comment but inside the comment cotnainer.
		parentDiv.parentNode.insertBefore(iframeHtml, parentDiv.nextSibling);
		Jcomments.hideAddForm();

		iframeHtml.addEventListener('load', function () {
			const iframeDom = Jcomments.getIframeContent(document.querySelector('.commentEditFormFrame')),
				editForm = iframeDom.querySelector('#editForm');

			if (editForm) {
				editForm.classList.remove('d-none');
			}

			const parentDoc = parent.document,
				parentIframe = parentDoc.querySelector('.commentEditFormFrame');

			// Resize an iframe before scrolling to element inside iframe.
			parentIframe.style.height = Jcomments.iframeHeight(parentIframe) + 'px';

			Jcomments.scrollTop(
				parent.window,
				parentDoc.querySelector('.commentEditFormFrame').getBoundingClientRect().top + parent.window.pageYOffset - parentDoc.documentElement.clientTop
			);

			Jcomments.loader();
		});
	}

	/**
	 * Navigate to parent comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 *
	 * @method  Jcomments.parentComment
	 * @return  {void}
	 * @todo Refactor
	 */
	Jcomments.parentComment = function (el) {
		const url = el.href,
			hash = url.split('#')[1];

		if (document.querySelector('#' + hash).length > 0) {
			Jcomments.scrollToByHash(hash);
		} else {
			// TODO Показать одиночный комментарий если список или найти в дереве
			/*if (commentsContainer.data('template') === 'list') {
				// TODO Получить значение page в контролере
				Jcomments.loadComments('', '', -1);
			} else {
				window.location.href = url;
			}*/
		}
	}

	/**
	 * Parse json string and catch error
	 *
	 * @param   {string}  response  Response from server
	 *
	 * @method  Jcomments.parseJson
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

	/**
	 * Pin/unpin comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 * @param   {int}                id  Comment ID
	 *
	 * @method  Jcomments.pin
	 * @return  {void}
	 */
	Jcomments.pin = function (el, id) {
		const comment = el.closest('div.comment');

		Joomla.request({
			url: el.href + '&' + Joomla.getOptions('csrf.token') + '=1&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					el.title = response.data.title;
					el.href = response.data.url;

					const icon = el.querySelectorAll(':scope span')[0],
						pinned_header = comment.querySelectorAll(':scope .comment-pinned');

					if (response.data.current_state === 0) {
						icon.classList.remove('link-secondary');
						icon.classList.add('link-success');

						if (pinned_header.length) {
							pinned_header[0].style.display = 'none';
						}
					} else {
						icon.classList.add('link-secondary');
						icon.classList.remove('link-success');

						if (pinned_header.length) {
							pinned_header[0].style.display = '';
						} else {
							comment.insertAdjacentHTML(
								'afterbegin',
								response.data.header
							);
						}
					}

					// Find a comment in pinned list
					const pinned_comment = document.querySelector('#comment-p-' + id);

					if (pinned_comment) {
						const pl_comment_container = pinned_comment.closest('div.comment');

						if (response.data.current_state === 0) {
							pl_comment_container.style.display = 'none';
						} else {
							pl_comment_container.style.display = '';
						}
					}

					Joomla.renderMessages({'message': [response.message]}, '#comment-item-' + id);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
				Jcomments.loader();
			}
		});
	}

	/**
	 * Get the immediately preceding sibling of each element in the set of matched elements.
	 *
	 * @param   {HTMLElement}  el        DOM element
	 * @param   {string}       selector  Callback function
	 *
	 * @method  Jcomments.prev
	 * @return  {HTMLElement|null}
	 */
	Jcomments.prev = function (el, selector) {
		const prevEl = el.previousElementSibling;

		if (!selector || (prevEl && prevEl.matches(selector))) {
			return prevEl;
		}

		return null;
	}

	/**
	 * Show hidden 'Add comment' form
	 *
	 * @param   {boolean}  findIframe  Find iframe with form. Used for 'Reply' link.
	 *
	 * @method  Jcomments.showAddForm
	 * @return  {void}
	 */
	Jcomments.showAddForm = function (findIframe) {
		let btn, form;
		const doc = Jcomments.isIframe()
			? Jcomments.getIframeContent(parent.document.querySelector('.commentFormFrame'))
			: findIframe ? Jcomments.getIframeContent(document.querySelector('.commentFormFrame')) : document;

		btn = doc.querySelectorAll('.showform-btn-container');
		form = doc.querySelectorAll('#editForm')[0];

		if (btn.length > 0) {
			if (btn[0].classList.contains('d-none') === false) {
				btn[0].classList.add('d-none');
				form.classList.remove('d-none');

				if (Jcomments.isIframe()) {
					const parentDoc = parent.document,
						parentIframe = parentDoc.querySelector('.commentFormFrame');

					// Resize an iframe before scrolling to an element inside iframe.
					parentIframe.style.height = Jcomments.iframeHeight(parentIframe) + 'px';

					Jcomments.scrollTop(
						parent.window,
						parentDoc.querySelector('.commentFormFrame').getBoundingClientRect().top + parent.window.pageYOffset - parentDoc.documentElement.clientTop
					);
				} else {
					if (findIframe) {
						const parentIframe = document.querySelector('.commentFormFrame');

						// Resize an iframe before scrolling to an element inside iframe.
						parentIframe.style.height = Jcomments.iframeHeight(parentIframe) + 'px';

						Jcomments.scrollTop(
							window,
							document.querySelector('.commentFormFrame').getBoundingClientRect().top + window.pageYOffset - document.documentElement.clientTop);
					}
				}
			} else {
				Jcomments.scrollToByHash('addcomment');
			}
		} else {
			Jcomments.scrollToByHash('addcomment');
		}
	}

	/**
	 * Hide 'Add comment' form
	 *
	 * @param   {Element}  el     Button
	 * @param   {boolean}  close  Just try to remove iframe.
	 *
	 * @method  Jcomments.hideAddForm
	 * @return  {void}
	 */
	Jcomments.hideAddForm = function (el, close) {
		const btn = document.querySelector('.showform-btn-container'),
			form = document.querySelector('#editForm');

		if (btn) {
			if (btn.classList.contains('d-none')) {
				form.classList.add('d-none');
				btn.classList.remove('d-none');

				if (Jcomments.isIframe()) {
					const frame = parent.document.querySelector('#addcomment');

					frame.style.height = 0;
					Jcomments.iframeHeight(frame);

					Jcomments.scrollTop(
						parent.window,
						frame.getBoundingClientRect().top + parent.window.pageYOffset - parent.document.documentElement.clientTop
					);
				}
			}
		} else {
			if (close === true) {
				Jcomments.hideEditForm(el);
			}
		}
	}

	/**
	 * Hide 'Edit comment' form
	 *
	 * @param   {Element}  el  Button
	 *
	 * @method  Jcomments.hideEditForm
	 * @return  {void}
	 */
	Jcomments.hideEditForm = function (el) {
		if (el.nodeType !== 1) {
			console.log('Something wrong with edit form!');

			return;
		}

		const form = el.closest('#adminForm');

		// Form not found - an error occured.
		if (!form) {
			if (Jcomments.isIframe()) {
				const frame = parent.document.querySelector('#editcomment');

				if (frame) {
					frame.remove();
				} else {
					// Maybe we in 'add comment' frame?
					const frame = parent.document.querySelector('#addcomment');

					if (frame) {
						frame.remove();
					}
				}
			} else {
				const frame = parent.document.querySelector('#editcomment');

				if (frame) {
					frame.remove();
				} else {
					// Maybe we in 'add comment' frame?
					const frame = parent.document.querySelector('#addcomment');

					if (frame) {
						frame.remove();
					}
				}
			}

			return;
		}

		let queryString = '',
			commentId = parseInt(form.querySelector('#jform_comment_id').value, 10);

		// Run ajax request to cancel edit(check in) only for edit form(not reply with quote).
		if (!empty(commentId) && parseInt(Jcomments.queryString.get(document.location.href, 'quote'), 10) !== 1) {
			queryString = Jcomments.queryString.init({
				'task': 'comment.cancel',
				[Joomla.getOptions('csrf.token')]: 1,
				'comment_id': commentId
			}).toString();

			Joomla.request({
				url: form.getAttribute('action') + '&format=json',
				method: 'POST',
				data: queryString
			});
		}

		if (Jcomments.isIframe()) {
			const frame = parent.document.querySelector('#editcomment');

			if (frame) {
				let commentDiv = !isNaN(commentId) ? parent.document.querySelector('#comment-item-' + commentId) : null;

				// Maybe this is 'reply with quote' form
				if (commentDiv === null) {
					commentId = parseInt(form.querySelector('#jform_parent').value, 10);
					commentDiv = !isNaN(commentId) ? parent.document.querySelector('#comment-item-' + commentId) : null;
				}

				if (commentDiv) {
					Jcomments.scrollTop(
						parent.window,
						commentDiv.getBoundingClientRect().top + parent.window.pageYOffset - parent.document.documentElement.clientTop
					);
				}

				frame.classList.add('d-none');
				// Do not remove the iframe here as it will block ajax request.
			}
		} else {
			el.closest('.btn-container').classList.add('d-none');
		}
	}

	/**
	 * Show error message
	 *
	 * @param   {string|object}  response  Response from server
	 * @param   {string}  selector  The selector of the container where the message will be rendered
	 *
	 * @method  Jcomments.showError
	 * @return  {void}
	 */
	Jcomments.showError = function (response, selector) {
		let _response;

		if (typeof response === 'string') {
			_response = Jcomments.parseJson(response);
		} else {
			_response = response;
		}

		Joomla.renderMessages(
			{
				'warning': [empty(_response) ? Joomla.Text._('ERROR') : (empty(_response.message) ? _response : _response.message)]
			},
			selector
		);
	}

	/**
	 * Display loader
	 *
	 * @param   {int}  state  Loader state. 0 - hide, 1 - create/show.
	 *
	 * @method  Jcomments.loader
	 * @return  {void}
	 */
	Jcomments.loader = function (state) {
		const loaderEl = document.querySelector('.jc-loader');

		// Create
		if (state === 1) {
			if (!loaderEl) {
				document.querySelector('body').insertAdjacentHTML(
					'beforeend',
					'<div class="jc-loader">' +
						'<div aria-hidden="true" class="spinner-border spinner-border-sm"></div>' +
						'<span role="status">' + Joomla.Text._('LOADING', 'Loading...') + '</span>' +
					'</div>'
				);
			} else {
				loaderEl.style.display = '';
			}
		} else {
			// Hide
			if (loaderEl) {
				loaderEl.style.display = 'none';
			}
		}
	}

	/**
	 * Set the current vertical position of the scroll bar for each of the set of matched elements.
	 *
	 * @param   {Window|Node}  el     HTML element
	 * @param   {int}          value  A number indicating the new position to set the scroll bar to.
	 *
	 * @method  Jcomments.scrollTop
	 * @return  {int|void}
	 */
	Jcomments.scrollTop = function (el, value) {
		if (value === undefined) {
			return el.pageYOffset;
		} else {
			// See https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
			if ((el === window || el === parent.window) || el.nodeType === 9) {
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
	 * @method  Jcomments.offset
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

	/**
	 * Set the current vertical position of the scroll bar for each of the set of matched elements by hash(element ID).
	 *
	 * @param   {string}  hash   ID of the element or string with hash
	 * @param   {string}  taget  Target where to find element. Can be:
	 *                           window - search in current window/document;
	 *                           parent - search in parent window/document;
	 *                           frame - search in iframe document. Default: window
	 *
	 * @method  Jcomments.scrollToByHash
	 * @return  {void}
	 */
	Jcomments.scrollToByHash = function (hash, taget) {
		const hashPos = hash.indexOf('#');

		if (hashPos > -1) {
			hash = hash.split('#')[1];
		}

		if (taget === 'parent') {
			// Search an element in parent document and scroll parent document to this position.
			const el = parent.document.querySelector('#' + hash);

			if (el) {
				Jcomments.scrollTop(
					parent.window,
					el.getBoundingClientRect().top + parent.window.pageYOffset - frameElement.clientTop
				);
			}
		} else if (taget === 'frame') {
			// Search an element in iframe document and scroll parent document to this position.
			const el = document.querySelector('#' + hash),
				iframe_el = parent.document.querySelector('#' + window.frameElement.id);

			if (iframe_el) {
				const iframe_el_pos = el.getBoundingClientRect().top;

				Jcomments.scrollTop(
					parent.window,
					iframe_el.getBoundingClientRect().top + parent.window.pageYOffset - frameElement.clientTop + iframe_el_pos
				);
			}
		} else {
			const el = document.querySelector('#' + hash);

			if (el) {
				Jcomments.scrollTop(window, Jcomments.offset(el).top);
			}
		}
	}

	/**
	 * Check if we are in iframe
	 *
	 * @method  Jcomments.isIframe
	 * @return  {boolean}
	 */
	Jcomments.isIframe = function () {
		try {
			return window.self !== window.top;
		} catch (e) {
			return true;
		}
	}

	/**
	 * Get the immediately following sibling of each element in the set of matched elements.
	 *
	 * @param   {HTMLElement}  el        DOM element
	 * @param   {string}       selector  Callback function
	 *
	 * @method  Jcomments.next
	 * @return  {HTMLElement|null}
	 */
	Jcomments.next = function (el, selector) {
		const nextEl = el.nextElementSibling;

		if (!selector || (nextEl && nextEl.matches(selector))) {
			return nextEl;
		}

		return null;
	}

	/**
	 * Ban user.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 * @param   {int}                id  Comment ID
	 *
	 * @method  Jcomments.delete
	 * @return  {void}
	 */
	Jcomments.banIP = function (el, id) {
		if (!confirm(Joomla.Text._('BUTTON_BANIP') + '?')) {
			return false;
		}

		Joomla.request({
			url: el.href + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					Joomla.renderMessages({'message': [response.message]}, '#comment-item-' + id);
					Jcomments.css(
						Jcomments.prev(el, '.toolbar-button-ip'),
						'text-decoration',
						'line-through'
					).classList.add('link-secondary');
					el.remove();
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
				Jcomments.loader();
			}
		});
	}

	/**
	 * Delete comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 * @param   {int}                id  Comment ID
	 *
	 * @method  Jcomments.delete
	 * @return  {boolean|null}
	 */
	Jcomments.delete = function (el, id) {
		if (!confirm(Joomla.Text._('BUTTON_DELETE_CONFIRM'))) {
			return false;
		}

		Joomla.request({
			url: el.href + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					Joomla.renderMessages({'message': [response.message]}, '#comment-item-' + id);

					const totalSpan = document.querySelector('.total-comments'),
						pinned_comment = document.querySelector('#comment-p-' + id);

					if (totalSpan) {
						totalSpan.textContent = response.data.total;
					}

					document.querySelector('#comment-' + id).outerHTML = response.data.html;

					if (pinned_comment) {
						pinned_comment.closest('.comment-container').remove();
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
			}
		});
	}

	/**
	 * Report about the comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 *
	 * @method  Jcomments.reportComment
	 * @return  {void}
	 */
	Jcomments.reportComment = function (el) {
		const reportModalEl = document.querySelector('#reportModal');

		if (reportModalEl)
		{
			const reportModal = new bootstrap.Modal('#reportModal');

			document.querySelector('#reportFormFrame').setAttribute('src', el.href + '&_=' + new Date().getTime());
			reportModal.show(reportModal);
		}
	}

	/**
	 * Publish/unpublish comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 * @param   {int}                id  Comment ID
	 *
	 * @method  Jcomments.state
	 * @return  {void}
	 */
	Jcomments.state = function (el, id) {
		const comment = el.closest('div.comment');

		Joomla.request({
			url: el.href + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					el.title = response.data.title;
					el.href = response.data.url;

					const icon = el.querySelectorAll(':scope span')[0],
						user_panel_links = el.closest('.comment-panels').querySelectorAll(':scope .user-panel a'),
						pinned_header = comment.querySelectorAll(':scope .comment-pinned');

					if (response.data.current_state === 0) {
						icon.classList.remove('icon-unpublish', 'link-secondary');
						icon.classList.add('icon-publish', 'link-success');
						comment.classList.add('bg-light', 'text-muted');

						if (pinned_header.length) {
							pinned_header[0].classList.remove('text-bg-success');
						}
					} else {
						icon.classList.remove('icon-publish', 'link-success');
						icon.classList.add('icon-unpublish', 'link-secondary');
						comment.classList.remove('bg-light', 'text-muted');

						if (pinned_header.length) {
							pinned_header[0].classList.add('text-bg-success');
						}
					}

					if (user_panel_links.length > 0) {
						Object.keys(user_panel_links).forEach(function (key) {
							const a = user_panel_links[key];

							// Exclude toggle comment link from disabling pointer event
							if (!a.classList.contains('toolbar-button-child-toggle')) {
								if (response.data.current_state === 0) {
									a.classList.add('pe-none');
									a.setAttribute('aria-disabled', 'true');
								} else {
									a.classList.remove('pe-none');
									a.removeAttribute('aria-disabled');
								}
							}
						});
					}

					// Find a comment in pinned list
					const pinned_comment = document.querySelector('#comment-p-' + id);

					if (pinned_comment) {
						const pl_comment_header = pinned_comment.querySelectorAll(':scope .comment-pinned');

						if (response.data.current_state === 0) {
							pinned_comment.classList.add('bg-light', 'text-muted');

							if (pl_comment_header.length) {
								pl_comment_header[0].classList.remove('text-bg-success');
							}
						} else {
							pinned_comment.classList.remove('bg-light', 'text-muted');

							if (pl_comment_header.length) {
								pl_comment_header[0].classList.add('text-bg-success');
							}
						}
					}

					Joomla.renderMessages({'message': [response.message]}, '#comment-item-' + id);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
				Jcomments.loader();
			}
		});
	}

	/**
	 * Subscribe to new comments.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 *
	 * @method  Jcomments.subscribe
	 * @return  {void}
	 */
	Jcomments.subscribe = function (el) {
		const msg = '.comments-list-footer';

		Joomla.request({
			url: el.href + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					el.href = response.data.href;
					el.title = response.data.title;
					el.innerHTML = '<span aria-hidden="true" class="fa icon-mail me-1"></span>' + response.data.title;

					Joomla.renderMessages({'message': [response.message]}, msg);
				} else {
					Jcomments.showError(null, msg);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, msg);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
				Jcomments.loader();
			}
		});
	}

	/**
	 * Toggle comments in tree mode.
	 *
	 * @param   {HTMLElement}  el  DOM element
	 *
	 * @method  Jcomments.delete
	 * @return  {void}
	 */
	Jcomments.toggleComments = function (el) {
		const span = el.querySelector(':scope > span'),
			list = Jcomments.next(el.closest('.comment-container'), '.comments-list-child');

		if (list === null) {
			return;
		}

		if (!(list.offsetWidth || list.offsetHeight || list.getClientRects().length)) {
			Jcomments.slideDown(list, 400, function () {
				el.setAttribute('title', el.dataset.titleHide);
				span.classList.replace('icon-chevron-down', 'icon-chevron-up');
			});
		} else {
			Jcomments.slideUp(list, 400, function () {
				el.setAttribute('title', el.dataset.titleShow);
				span.classList.replace('icon-chevron-up', 'icon-chevron-down');
			});
		}
	}

	/**
	 * Vote for comment.
	 *
	 * @param   {HTMLAnchorElement}  el  DOM element
	 * @param   {int}                id  Comment ID
	 *
	 * @method  Jcomments.vote
	 * @return  {void}
	 */
	Jcomments.vote = function (el, id) {
		Joomla.request({
			url: el.dataset.url + '&comment_id=' + id + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				el.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					const voteDiv = document.querySelectorAll('.comment-vote-holder-' + id);

					if (voteDiv.length > 0) {
						voteDiv.forEach(function (el) {
							// :scope selector selects only direct descendants, so use [0] to access .vote-up div.
							el.querySelectorAll(':scope .vote-up')[0].remove();
							el.querySelectorAll(':scope .vote-down')[0].remove();
							el.querySelectorAll(':scope .vote-result span')[0].outerHTML = response.data;
						});
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				el.classList.remove('pe-none');
				Jcomments.loader();
			}
		});
	}

	/**
	 * Create iframe html.
	 *
	 * @param   {HTMLElement|null}  el   Link tag with 'edit url' for iframe src attribute.
	 * @param   {string|null}       url  Edit URL for iframe src attribute if 'el' not set.
	 *
	 * @method  Jcomments.createIframe
	 * @return  {HTMLIFrameElement}
	 */
	Jcomments.createIframe = function (el, url) {
		const randomArr = new Uint32Array(1),
			iframeHtml = document.createElement('iframe');

		self.crypto.getRandomValues(randomArr);

		iframeHtml.setAttribute('width', '100%');
		iframeHtml.setAttribute('onload', 'Jcomments.iframeHeight(this);');
		iframeHtml.setAttribute('style', 'overflow: hidden; height: 0;');
		iframeHtml.setAttribute('scrolling', 'no');
		iframeHtml.setAttribute('class', 'commentEditFormFrame');
		iframeHtml.setAttribute('id', 'editcomment');
		iframeHtml.setAttribute('name', randomArr[0].toString());
		iframeHtml.setAttribute('src', (el === null ? url : el.dataset.editUrl) + '&_=' + new Date().getTime());

		return iframeHtml;
	}

	/**
	 * Get iframe DOM.
	 *
	 * @param   {HTMLIFrameElement|string}  iframe  Selector to find the iframe or DOM element
	 *
	 * @method  Jcomments.getIframeContent
	 * @return  {Document|null}
	 */
	Jcomments.getIframeContent = function (iframe) {
		if (typeof iframe === 'string') {
			iframe = document.querySelector(iframe);
		}

		if (iframe !== null) {
			return iframe.contentDocument || iframe.contentWindow.document;
		}

		return null;
	}

	/**
	 * Hide the matched elements with a sliding motion.
	 *
	 * @param   {HTMLElement}  el        DOM element
	 * @param   {int}          duration  Animation duration
	 * @param   {function}     callback  Callback function
	 *
	 * @see     https://codepen.io/ivanwebstudio/pen/OJVzPBL
	 * @method  Jcomments.slideUp
	 * @return  {void}
	 */
	Jcomments.slideUp = function (el, duration, callback) {
		if (!duration) duration = 400;

		el.style.transitionProperty = 'height, margin, padding';
		el.style.transitionDuration = duration + 'ms';
		el.style.boxSizing = 'border-box';
		el.style.height = el.offsetHeight + 'px';
		el.offsetHeight;
		el.style.overflow = 'hidden';
		el.style.height = 0;
		el.style.paddingTop = 0;
		el.style.paddingBottom = 0;
		el.style.marginTop = 0;
		el.style.marginBottom = 0;

		window.setTimeout(() => {
			el.style.display = 'none';
			el.style.removeProperty('height');
			el.style.removeProperty('padding-top');
			el.style.removeProperty('padding-bottom');
			el.style.removeProperty('margin-top');
			el.style.removeProperty('margin-bottom');
			el.style.removeProperty('overflow');
			el.style.removeProperty('transition-duration');
			el.style.removeProperty('transition-property');

			if (typeof callback === 'function') callback();
		}, duration);
	}

	/**
	 * Display the matched elements with a sliding motion.
	 *
	 * @param   {HTMLElement}  el        DOM element
	 * @param   {int}          duration  Animation duration
	 * @param   {function}     callback  Callback function
	 *
	 * @see     https://codepen.io/ivanwebstudio/pen/OJVzPBL
	 * @method  Jcomments.slideDown
	 * @return  {void}
	 */
	Jcomments.slideDown = function (el, duration, callback) {
		if (!duration) duration = 400;

		el.style.removeProperty('display');

		let display = window.getComputedStyle(el).display;

		if (display === 'none') {
			display = 'block';
		}

		el.style.display = display;
		const height = el.offsetHeight;
		el.style.overflow = 'hidden';
		el.style.height = 0;
		el.style.paddingTop = 0;
		el.style.paddingBottom = 0;
		el.style.marginTop = 0;
		el.style.marginBottom = 0;
		el.offsetHeight;
		el.style.boxSizing = 'border-box';
		el.style.transitionProperty = "height, margin, padding";
		el.style.transitionDuration = duration + 'ms';
		el.style.height = height + 'px';
		el.style.removeProperty('padding-top');
		el.style.removeProperty('padding-bottom');
		el.style.removeProperty('margin-top');
		el.style.removeProperty('margin-bottom');

		window.setTimeout(() => {
			el.style.removeProperty('height');
			el.style.removeProperty('overflow');
			el.style.removeProperty('transition-duration');
			el.style.removeProperty('transition-property');

			if (typeof callback === 'function') callback();
		}, duration);
	}

	/**
	 * Get/set/delete vars from URL.
	 * This is simple version and do not support arrays.
	 *
	 * @param   {URLSearchParams|string}  url  URL string to parse or URLSearchParams object instance.
	 *
	 * @method  Jcomments.queryString
	 * @return  {URLSearchParams|string|null}  Return URLSearchParams object on init/set/delete, string on get,
	 *                                         null if URLSearchParams nor available.
	 */
	Jcomments.queryString = {
		init: function (url) {
			let queryString = null;

			if ('URLSearchParams' in window) {
				if (url === '') {
					queryString = document.location.search;
				} else if (Jcomments.isObject(url)) {
					queryString = url;
				} else {
					if ('canParse' in URL) {
						if (URL.canParse(url)) {
							const parsed_url = new URL(url);

							queryString = parsed_url.search;
						}
					} else {
						queryString = url.split('?')[1];
					}
				}

				return new URLSearchParams(queryString);
			}

			return queryString;
		},
		get: function (url, var_name) {
			let params = '';

			if (url instanceof URLSearchParams) {
				return url.get(var_name);
			} else {
				params = Jcomments.queryString.init(url);

				return params !== null ? params.get(var_name) : null;
			}
		},
		set: function (url, var_name, var_value) {
			let params = '';

			if (url instanceof URLSearchParams) {
				url.set(var_name, var_value);

				return url;
			} else {
				params = Jcomments.queryString.init(url);

				if (params !== null) {
					params.set(var_name, var_value);
				}

				return params;
			}
		},
		delete: function (url, var_name, var_value) {
			let params = '';

			if (url instanceof URLSearchParams) {
				if (var_value !== '') {
					url.delete(var_name, var_value);
				} else {
					url.delete(var_name);
				}

				return url;
			} else {
				params = Jcomments.queryString.init(url);

				if (params !== null) {
					if (var_value !== '') {
						params.delete(var_name, var_value);
					} else {
						params.delete(var_name);
					}
				}

				return params;
			}
		}
	}

	/**
	 * Set css property.
	 *
	 * @param   {HTMLElement|string}  selector  CSS selector or object.
	 * @param   {string|object}       prop      CSS property or object with properties and their values.
	 * @param   {string|null}         val       Property value. Empty if 'prop' is object.
	 *
	 * @method  Jcomments.css
	 * @return  {HTMLElement|null}
	 */
	Jcomments.css = function (selector, prop, val) {
		const el = Jcomments.isObject(selector) ? selector : document.querySelector(selector);

		if (el) {
			if (typeof prop === 'string' && typeof val === 'string') {
				if (val.indexOf('!important') > -1) {
					val = val.replace('!important', '');
					el.style.setProperty(prop, val.trim(), 'important');
				} else {
					el.style.setProperty(prop, val.trim());
				}
			} else {
				try {
					prop = JSON.parse(prop);
				} catch (e) {
					return null;
				}

				for (const [key, value] of Object.entries(prop)) {
					if (value.indexOf('!important') > -1) {
						val = value.replace('!important', '');
						el.style.setProperty(key, val.trim(), 'important');
					} else {
						el.style.setProperty(key, value.trim());
					}
				}
			}

			return el;
		}
	}

	/**
	 * Test for object.
	 *
	 * @param   {mixed}  obj  Object to test.
	 *
	 * @method  Jcomments.isObject
	 * @return  {boolean}
	 */
	Jcomments.isObject = function (obj) {
		const type = typeof obj;

		return type === 'function' || (type === 'object' && !!obj);
	}
}(Jcomments, document));

((document, submitForm) => {
	const buttonDataSelector = 'data-submit-task',
		formId = 'adminForm';

	/**
	 * Submit the task
	 * @param task
	 * @param el
	 */
	const submitTask = (task, el) => {
		const form = document.getElementById(formId);

		if (task === 'comment.cancel' || document.formvalidator.isValid(form)) {
			if (task === 'comment.cancel') {
				if (el.dataset.cancel === 'hideEditForm') {
					Jcomments.hideEditForm(document.querySelector('[data-submit-task="' + task + '"]'));
				} else if (el.dataset.cancel === 'hideAddForm') {
					Jcomments.hideAddForm();
				}
			} else {
				const jce_config = Joomla.getOptions('jceditor');

				if (jce_config.editor_type === 'joomla' && jce_config.format === 'xhtml') {
					submitForm(task, form);
				} else {
					Jcomments.saveComment(el, !!(task === 'comment.preview'));
				}
			}
		}
	};

	// Register events
	document.addEventListener('DOMContentLoaded', () => {
		const buttons = [].slice.call(document.querySelectorAll(`[${buttonDataSelector}]`));

		buttons.forEach(button => {
			button.addEventListener('click', e => {
				e.preventDefault();
				submitTask(e.target.getAttribute(buttonDataSelector), e.target);
			});
		});
	});
})(document, Joomla.submitform);
