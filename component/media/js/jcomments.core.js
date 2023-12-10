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
	 * @param   {Element}  iframe   Iframe element
	 * @param   {string}   el       Selector for an element. If set, iframe will be resized to this element height.
	 * @param   {boolean}  margins  Add top and bottom margins from element.
	 *
	 * @method  Jcomments.iframeHeight
	 * @return  {int}  Current calculated height in pixels or default value(250) on error.
	 */
	Jcomments.iframeHeight = function (iframe, el, margins) {
		const doc = Jcomments.getIframeContent(iframe);
		let height;

		if (empty(doc)) {
			return 250;
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
	 * @param   {string}   object_id     Object ID.
	 * @param   {string}   object_group  Object group. E.g. com_content
	 * @param   {number}   page          Limitstart value.
	 * @param   {boolean}  scroll        Scroll the page to the comments if the user came by the link
	 * 									 from 'Readmore block'. Default: false.
	 *
	 * @method  Jcomments.loadComments
	 * @return  {void}
	 */
	Jcomments.loadComments = function (object_id, object_group, page, scroll) {
		page = parseInt(page, 10);

		const comments_container = document.querySelector('.comments-list-container'),
			id = empty(object_id) ? comments_container.dataset.objectId : object_id,
			group = empty(object_group) ? comments_container.dataset.objectGroup : object_group,
			limitstart_varname = comments_container.dataset.navPrefix + 'limitstart',
			_page = empty(page) ? '' : '&' + limitstart_varname + '=' + page;

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

							// Change limitstart value in URL
							if (page > 0) {
								searchParams.set(limitstart_varname, page);
							} else if (page === 0 || page === '') {
								// Remove empty limitstart from query string
								searchParams.delete(limitstart_varname);
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

					if (scroll && window.location.hash) {
						if (window.location.hash === '#comments') {
							Jcomments.scrollToByHash('#comments');
						}
					}
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
	 * Preview comment
	 *
	 * @param   {HTMLElement}  el       Button.
	 * @param   {boolean}      preview  Preview comment if false, save otherwise.
	 *
	 * @method  Jcomments.saveComment
	 * @return  {void}
	 */
	Jcomments.saveComment = function (el, preview) {
		const form = document.getElementById('comments-form');
		let form_data = new FormData(form),
			queryString = '';

		if (preview === true) {
			form_data.set('task', 'comment.preview');
		}

		const jce_config = Joomla.getOptions('jceditor'),
			editor = sceditor.instance(document.getElementById(jce_config.field)),
			length = parseInt(editor.val().length, 10),
			doc = Jcomments.isIframe() ? parent.document : document,
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

		Jcomments.createLoader(0);

		Joomla.request({
			url: form.getAttribute('action') + '&format=json',
			method: 'POST',
			data: queryString,
			onSuccess: function (response) {
				const _response = Jcomments.parseJson(response),
					previewDiv = document.querySelector('.comment-preview');

				if (previewDiv) {
					previewDiv.remove();
				}

				if (preview === true) {
					const _frame = doc.querySelector(iframeName);

					if (Jcomments.isIframe()) {
						el.closest('#comments-form').insertAdjacentHTML('afterbegin', _response.data);

						if (comment_id > 0 || parent_id > 0) {
							Jcomments.iframeHeight(doc.querySelector('.commentEditFormFrame'));
						} else {
							Jcomments.iframeHeight(parent.document.querySelector('.commentFormFrame'));
						}
					} else {
						doc.querySelector('#comments-form').insertAdjacentHTML('afterbegin', _response.data);
						Jcomments.scrollToByHash('comment-item-');
					}
				} else {
					if (_response.success) {
						Joomla.renderMessages({'message': [_response.message]}, '#system-message-container');
					} else {
						Jcomments.showError(xhr.response, '#system-message-container');
					}
				}

				Jcomments.createLoader(100);
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#system-message-container');
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

		Jcomments.createLoader(0);

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
			const iframeDom = Jcomments.getIframeContent(document.querySelector('.commentEditFormFrame'))

			iframeDom.querySelector('#editForm').classList.remove('d-none');

			const parentDoc = parent.document,
				parentIframe = parentDoc.querySelector('.commentEditFormFrame');

			// Resize an iframe before scrolling to element inside iframe.
			parentIframe.style.height = Jcomments.iframeHeight(parentIframe) + 'px';

			Jcomments.scrollTop(
				parent.window,
				parentDoc.querySelector('.commentEditFormFrame').getBoundingClientRect().top + parent.window.pageYOffset - parentDoc.documentElement.clientTop
			);

			Jcomments.createLoader(100);
		});
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
	 * @method  Jcomments.hideAddForm
	 * @return  {void}
	 */
	Jcomments.hideAddForm = function () {
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
		const form = el.closest('#comments-form');
		let queryString = '',
			commentId = parseInt(form.querySelector('#jform_comment_id').value, 10);

		// Run ajax only for edit form(not reply with quote)
		if (!empty(commentId)) {
			if ('URLSearchParams' in window) {
				queryString = new URLSearchParams({
					'task': 'comment.cancel',
					[Joomla.getOptions('csrf.token')]: 1,
					'comment_id': commentId
				}).toString();
			}

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
			el.classList.add('d-none');
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
				'<div class="jc-loader progress rounded-0" style="height: 3px; top: 0; position: fixed; width: 100%; left: 0; z-index: 10000;"><div class="progress-bar bg-secondary" role="progressbar" aria-label="Comments loading" style="width: ' + value + '%;" aria-valuenow="' + value + '" aria-valuemin="0" aria-valuemax="100"></div></div>'
			);
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
	 * @param   {string}  hash  ID of the element or string with hash
	 *
	 * @method  Jcomments.scrollToByHash
	 * @return  {void}
	 */
	Jcomments.scrollToByHash = function (hash) {
		const hashPos = hash.indexOf('#');

		if (hashPos > -1) {
			hash = hash.split('#')[1];
		}

		const el = document.querySelector('#' + hash);

		if (el) {
			Jcomments.scrollTop(window, Jcomments.offset(el).top);
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
	 * @param   {Element|string}  iframe  Selector to find the iframe or DOM element
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
}(Jcomments, document));
