// phpcs:disable
let jce_limitreached = false;

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
				height = iframe.body.scrollHeight;
			}

			iframe.style.height = height + 'px';

			return height;
		}

		if (!empty(el)) {
			const _el = doc.querySelector(el);

			if (_el !== null) {
				const style = window.getComputedStyle(_el);

				height = _el.scrollHeight;

				if (margins === true) {
					height = height + parseInt(style.getPropertyValue('margin-top'), 10) + parseInt(style.getPropertyValue('margin-bottom'), 10);
				}
			}
		} else {
			height = doc.body.scrollHeight;
		}

		iframe.style.height = height + 'px';

		return height;
	};

	/**
	 * Initialize editor chars counter. Available only sceditor and Joomla's 'none' editor.
	 *
	 * @param   {HTMLFormElement}  form  Form DOM.
	 *
	 * @method  Jcomments.initEditorCharsCounter
	 * @return  {void}
	 */
	Jcomments.initEditorCharsCounter = function (form) {
		const counter_div = form.querySelector('.jce-counter'),
			options = Joomla.getOptions('jcomments', '');

		if (counter_div) {
			if (options.editor.editor_type === 'component') {
				// Do not use .sceditor-container as selector!
				form.querySelector('textarea#' + options.editor.field).after(counter_div);
				counter_div.classList.remove('d-none');
			} else {
				if (options.editor.editor === 'none') {
					const editor_value = Joomla.editors.instances[options.editor.field].getValue();
					const textarea = form.querySelector('joomla-editor-none textarea');
					const event = function () {
						if (!options.editor.maxlength) {
							return true;
						}

						const length = options.editor.maxlength - editor_value.length,
							charsEl = counter_div.querySelector('.chars');
						charsEl.textContent = (length < 0) ? '0' : length.toString();

						if (editor_value.length >= options.editor.maxlength) {
							if (jce_limitreached)
							{
								return true;
							}

							jce_limitreached = true; // Display error message only once
							counterEl.insertAdjacentHTML(
								'beforeend',
								'<span class="limit-error badge text-bg-danger bg-opacity-75 fw-normal">' + Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_LONG') + '</span>'
							);
						} else {
							// Check previous state
							if (jce_limitreached)
							{
								counterEl.querySelector(':scope .limit-error').remove();
							}

							jce_limitreached = false;
						}
					};

					textarea.addEventListener('keyup', event);
					textarea.addEventListener('blur', event);
					textarea.addEventListener('focus', event);

					textarea.after(counter_div);
					counter_div.classList.remove('d-none');
				}
			}
		}
	}

	/**
	 * Load comments for object
	 *
	 * @param   {object|null}    e             Event.
	 * @param   {string}         object_id     Object ID.
	 * @param   {string}         object_group  Object group. E.g. com_content
	 * @param   {string|number}  page          Limitstart value.
	 * @param   {boolean}        scroll        Scroll the page to the comments if the user came by the link
	 * 									       from 'Readmore block' or `scrollTo` is not empty. Default: false.
	 * @param   {string}         scrollTo      Scroll page to element with this ID. E.g. #comments
	 *
	 * @method  Jcomments.loadComments
	 * @return  {void}
	 */
	Jcomments.loadComments = function (e, object_id, object_group, page, scroll, scrollTo) {
		if (e && e.type === 'click') {
			e.preventDefault();
		}

		page = parseInt(page, 10);

		if (isNaN(page)) {
			page = 0;
		}

		const container = document.querySelector('.comments-list-container'),
			options = Joomla.getOptions('jcomments', ''),
			oid = empty(object_id) ? options.object_id : object_id,
			ogroup = empty(object_group) ? options.object_group : object_group,
			limitstart_varname = options.pagination_prefix + 'limitstart',
			_page = empty(page) ? '' : '&' + limitstart_varname + '=' + page;

		Joomla.request({
			url: options.list_url + '&object_id=' + oid + '&object_group=' + ogroup + _page + '&format=raw' + '&_=' + new Date().getTime(),
			onBefore: function () {
				// Do not show loader on first run
				if (container.querySelectorAll('div.comments-list-parent').length === 1) {
					Jcomments.loader(1);
				}

				document.querySelectorAll('.refresh-list').forEach(function (el) {
					el.classList.add('pe-none');
					el.querySelector('span.fa').classList.add('refresh-list-spin');
				});
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					container.innerHTML = response.data.html;
					document.querySelector('.total-comments').textContent = response.data.total;

					// Handle limitstart value from query string
					if (response.data.type === 'list') {
						let searchParams = Jcomments.queryString.init(window.location.href);
						let originalParams = Jcomments.queryString.init(window.location.href);

						if (searchParams) {
							// Change limitstart value in URL
							if (page > 0) {
								searchParams.set(limitstart_varname, page);
								let newRelativePathQuery = window.location.pathname + '?' + searchParams.toString() + window.location.hash;

								if (originalParams.has(limitstart_varname) === false && page !== originalParams.get(limitstart_varname)) {
									history.pushState(null, '', decodeURIComponent(newRelativePathQuery));
								}
							} else if (page === 0 || page === '') {
								// Remove empty limitstart from query string
								searchParams.delete(limitstart_varname);
								let newRelativePathQuery = window.location.pathname + '?' + searchParams.toString() + window.location.hash;

								if (originalParams.has(limitstart_varname)) {
									history.pushState(null, '', decodeURIComponent(newRelativePathQuery));
								}
							}
						}
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
					el.querySelector('span.fa').classList.remove('refresh-list-spin');
				});
			}
		});
	}

	/**
	 * Preview comment
	 *
	 * @param   {HTMLButtonElement}  el    Button.
	 * @param   {string}             task  Task.
	 *
	 * @method  Jcomments.saveComment
	 * @return  {void}
	 * @todo Refactor
	 */
	Jcomments.saveComment = function (el, task) {
		const config = Joomla.getOptions('jcomments', ''),
			form = document.getElementById('commentForm'),
			editor_input = document.querySelector('#' + config.editor.field);
		let editor = null, length = 0, form_data = new FormData(form), queryString = '';

		form_data.set('task', task);

		if (config.editor.editor_type === 'component') {
			editor = sceditor.instance(document.getElementById(config.editor.field));
			form_data.set(editor_input.getAttribute('name'), editor.val());
		} else {
			editor = Joomla.editors.instances[config.editor.field];

			if (!this.isObject(editor)) {
				console.log('Error! Joomla editor not loaded!');

				return;
			}

			form_data.set(editor_input.getAttribute('name'), editor.getValue());
		}

		if ('URLSearchParams' in window) {
			queryString = new URLSearchParams(form_data).toString();
		}

		Joomla.request({
			url: form.getAttribute('action') + '&format=json',
			method: 'POST',
			data: queryString,
			onBefore: function () {
				Joomla.removeMessages('#commentForm fieldset');
				document.querySelectorAll('#commentForm .btn-container button').forEach(function (el) {
					el.setAttribute('disabled', '');
				});
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				const previewDiv = document.querySelector('.comment-preview');

				response = Jcomments.parseJson(response);

				if (previewDiv) {
					previewDiv.remove();
				}

				if (task === 'comment.preview') {
					if (!response) {
						Joomla.renderMessages({'error': [Joomla.Text._('ERROR')]}, '#commentForm fieldset');

						return;
					}

					document.querySelector('#commentForm').insertAdjacentHTML('afterbegin', response.data);
					Jcomments.scrollToByHash('comment-item-preview');
				} else {
					if (response.success) {
						Joomla.renderMessages({'message': [response.message]}, '#commentForm fieldset');
					} else {
						Jcomments.showError(response.message, '#commentForm fieldset', 'error');
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#commentForm fieldset', 'error');
			},
			onComplete: function () {
				document.querySelectorAll('#commentForm .btn-container button').forEach(function (el) {
					el.removeAttribute('disabled');
				});
				Jcomments.loader();
			}
		});
	}

	/**
	 * Navigate to comment edit form.
	 *
	 * @param   {Event}  e  Event.
	 *
	 * @method  Jcomments.showEditForm
	 * @return  {void}
	 */
	Jcomments.showEditForm = function (e) {
		e.preventDefault();
		const options = Joomla.getOptions('jcomments', '');
		document.location.href = this.dataset.url + '&return=' + options.return;
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
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.pin
	 * @return  {void}
	 */
	Jcomments.pin = function (e) {
		e.preventDefault();

		const comment = this.closest('.comment'), _this = this, id = parseInt(comment.dataset.id, 10);

		Joomla.request({
			url: _this.dataset.url + '&' + Joomla.getOptions('csrf.token', '') + '=1&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					_this.title = response.data.title;
					_this.dataset.url = response.data.url;

					const icon = _this.querySelectorAll(':scope span')[0],
						pinned_header = comment.querySelectorAll(':scope .comment-pinned-title');

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
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
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
	 * @method  Jcomments.showAddForm
	 * @return  {void}
	 */
	Jcomments.showAddForm = function () {
		this.toggleCommentForm(true);
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
	Jcomments.showError = function (response, selector, type) {
		let _response;

		if (typeof response === 'string') {
			_response = Jcomments.parseJson(response);
		} else {
			_response = response;
		}

		if (empty(type)) {
			type = 'warning';
		}

		Joomla.renderMessages(
			{
				[type]: [empty(_response) ? Joomla.Text._('ERROR') : (empty(_response.message) ? _response : _response.message)]
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
			return el.scrollY || el.pageYOffset;
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
		const box = el.getBoundingClientRect(),
			docElem = document.documentElement,
			y_offset = window.scrollY || window.pageYOffset,
			x_offset = window.scrollX || window.pageXOffset;

		return {
			top: box.top + y_offset - docElem.clientTop,
			left: box.left + x_offset - docElem.clientLeft
		};
	}

	/**
	 * Set the current vertical position of the scroll bar for each of the set of matched elements by hash(element ID).
	 *
	 * @param   {string}  hash   ID of the element or string with hash
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
	 * @param   {Event} e Event
	 *
	 * @method  Jcomments.banIP
	 * @return  {boolean|null}
	 */
	Jcomments.banIP = function (e) {
		e.preventDefault();

		if (!confirm(Joomla.Text._('BUTTON_BANIP') + '?')) {
			return false;
		}

		const _this = this, id = parseInt(this.closest('.comment').dataset.id, 10);

		Joomla.request({
			url: _this.dataset.url + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					Joomla.renderMessages({'message': [response.message]}, '#comment-item-' + id);
					Jcomments.css(
						Jcomments.prev(_this, '.cmd-ip'),
						'text-decoration',
						'line-through'
					).classList.add('link-secondary');
					_this.remove();
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#comment-item-' + id);
			},
			onComplete: function () {
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
			}
		});
	}

	/**
	 * Delete comment.
	 *
	 * @param   {Event} e Event
	 *
	 * @method  Jcomments.delete
	 * @return  {boolean|null}
	 */
	Jcomments.delete = function (e) {
		e.preventDefault();

		if (!confirm(Joomla.Text._('BUTTON_DELETE_CONFIRM'))) {
			return false;
		}

		const _this = this, id = parseInt(this.closest('.comment').dataset.id, 10);

		Joomla.request({
			url: _this.dataset.url + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
				Jcomments.loader(1);
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
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
			}
		});
	}

	/**
	 * Report about the comment.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.reportComment
	 * @return  {void}
	 */
	Jcomments.reportComment = function (e) {
		e.preventDefault();

		if (document.querySelector('#reportModal'))
		{
			const reportModal = new bootstrap.Modal('#reportModal');

			document.querySelector('#reportFormFrame').setAttribute('src', this.dataset.url + '&_=' + new Date().getTime());
			reportModal.show(reportModal);
		}
	}

	/**
	 * "Show" reply form.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.reply
	 * @return  {void}
	 */
	Jcomments.reply = function (e) {
		e.preventDefault();

		if (!document.querySelector('#commentForm')) {
			return;
		}

		const comment = this.closest('.comment'), parent_id = parseInt(comment.dataset.id, 10),
			parent_input = document.querySelector('#jform_parent'),
			form_container = document.querySelector('.form-comment-container'),
			cancel_btn = document.querySelector('button[data-submit-task="comment.cancel"]');

		if (parent_input && parent_id > 0) {
			parent_input.value = parent_id;

			if (document.getElementById('addcomment')) {
				if (form_container.classList.contains('d-none')) {
					Jcomments.scrollToByHash('#addcomment');
					Jcomments.showAddForm();
				} else {
					Jcomments.scrollTop(window, Jcomments.offset(form_container).top);
				}
			} else {
				Jcomments.scrollTop(window, Jcomments.offset(form_container).top);
				Jcomments.showAddForm();
			}

			if (cancel_btn.classList.contains('d-none')) {
				cancel_btn.classList.remove('d-none');
			}

			cancel_btn.setAttribute('data-cancel', 'cancelReply');
		}
	}

	/**
	 * Publish/unpublish comment.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.state
	 * @return  {void}
	 */
	Jcomments.state = function (e) {
		e.preventDefault();

		const comment = this.closest('.comment'), _this = this, id = parseInt(comment.dataset.id, 10);

		Joomla.request({
			url: _this.dataset.url + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					_this.title = response.data.title;
					_this.dataset.url = response.data.url;

					const icon = _this.querySelectorAll(':scope span')[0],
						user_panel_links = _this.closest('.comment-panels').querySelectorAll(':scope .user-panel a'),
						pinned_header = comment.querySelectorAll(':scope .comment-pinned-title');

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
							if (!a.classList.contains('cmd-child-toggle')) {
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
						const pl_comment_header = pinned_comment.querySelectorAll(':scope .comment-pinned-title');

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
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
			}
		});
	}

	/**
	 * Subscribe to new comments.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.subscribe
	 * @return  {void}
	 */
	Jcomments.subscribe = function (e) {
		e.preventDefault();

		if (e.target.origin !== window.location.origin) return;

		const _this = this, msg = '.comments-list-footer';

		Joomla.request({
			url: _this.href + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
				Jcomments.loader(1);
			},
			onSuccess: function (response) {
				response = Jcomments.parseJson(response);

				if (response !== false && response.success) {
					_this.href = response.data.href;
					_this.title = response.data.title;
					_this.innerHTML = '<span aria-hidden="true" class="fa icon-mail me-1"></span>' + response.data.title;

					Joomla.renderMessages({'message': [response.message]}, msg);
				} else {
					Jcomments.showError(null, msg);
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, msg);
			},
			onComplete: function () {
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
			}
		});
	}

	/**
	 * Toggle comments in tree mode.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.toggleComments
	 * @return  {void}
	 */
	Jcomments.toggleComments = function (e) {
		e.preventDefault();

		const el = this, span = el.querySelector(':scope > span'),
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
	 * Toggle 'Add comment' form
	 *
	 * @method  Jcomments.toggleCommentForm
	 * @return  {void}
	 */
	Jcomments.toggleCommentForm = function (onlyShow) {
		const btn = document.querySelectorAll('.showform-btn-container'),
			form = document.querySelectorAll('.form-comment-container')[0];

		if (form) {
			if (form.classList.contains('d-none')) {
				form.classList.remove('d-none');
				Jcomments.scrollToByHash('#addcomment');

				if (btn.length > 0) {
					btn[0].classList.add('d-none');
				}
			} else {
				if (!onlyShow) {
					form.classList.add('d-none');

					if (btn.length > 0) {
						btn[0].classList.remove('d-none');
						Jcomments.scrollToByHash('#addcomment');
					}
				}
			}
		}
	}

	/**
	 * Vote for comment.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.vote
	 * @return  {void}
	 */
	Jcomments.vote = function (e) {
		e.preventDefault();

		const comment = this.closest('.comment'), _this = this, id = parseInt(comment.dataset.id, 10);

		Joomla.request({
			url: _this.dataset.url + '&comment_id=' + id + '&format=json&_=' + new Date().getTime(),
			onBefore: function () {
				_this.classList.add('pe-none');
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
				_this.classList.remove('pe-none');
				Jcomments.loader(0);
			}
		});
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
		el.style.height = '0px';
		el.style.paddingTop = '0px';
		el.style.paddingBottom = '0px';
		el.style.marginTop = '0px';
		el.style.marginBottom = '0px';

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
		el.style.height = '0px';
		el.style.paddingTop = '0px';
		el.style.paddingBottom = '0px';
		el.style.marginTop = '0px';
		el.style.marginBottom = '0px';
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
	 * @param   {HTMLElement|string|mixed}  obj  Object to test.
	 *
	 * @method  Jcomments.isObject
	 * @return  {boolean}
	 */
	Jcomments.isObject = function (obj) {
		const type = typeof obj;

		return type === 'function' || (type === 'object' && !!obj);
	}

	/**
	 * Attach an event handler function for event to the selected elements.
	 *
	 * @param   {HTMLElement}  el            DOM element.
	 * @param   {string}       eventName     Event type.
	 * @param   {function}     eventHandler  Event handler.
	 * @param   {string}       selector      A selector string to filter the descendants of the selected elements that trigger the event.
	 *
	 * @method  Jcomments.on
	 * @return  {function}
	 */
	Jcomments.on = function (el, eventName, eventHandler, selector) {
		if (selector) {
			const wrappedHandler = (e) => {
				if (!e.target) return;
				const el = e.target.closest(selector);
				if (el) {
					eventHandler.call(el, e);
				}
			};
			el.addEventListener(eventName, wrappedHandler);
			return wrappedHandler;
		}
	}
}(Jcomments, document));

if (typeof Joomla !== 'undefined') {
	((document, submitForm) => {
		const buttonDataSelector = 'data-submit-task',
			formId = 'commentForm';

		/**
		 * Submit the task
		 * @param task
		 * @param el
		 */
		const submitTask = (task, el) => {
			const form = document.getElementById(formId),
				view_name = Jcomments.queryString.get(window.location.href, 'view');

			if (task === 'comment.cancel' || document.formvalidator.isValid(form)) {
				if (!form.querySelector('input[name="' + Joomla.getOptions('csrf.token', '') + '"]')) {
					let input = document.createElement('input');

					input.setAttribute('type', 'hidden');
					input.setAttribute('value', 1);
					input.setAttribute('name', Joomla.getOptions('csrf.token', ''));
					form.querySelector('fieldset').appendChild(input);
				}

				if (task === 'comment.cancel') {
					if (view_name !== 'form' && el.dataset.cancel === 'hideAddForm') {
						Jcomments.toggleCommentForm();
					} else if (view_name !== 'form' && el.dataset.cancel === 'cancelReply') {
						document.querySelector('#jform_parent').value = '0';
						el.classList.add('d-none');
						el.dataset.cancel = 'hideAddForm';
					} else {
						submitForm(task, form);
					}
				} else {
					Jcomments.saveComment(el, task);
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
}

document.addEventListener('DOMContentLoaded', function () {
	const options = Joomla.getOptions('jcomments', ''),
		container = document.querySelector('div.comments'),
		addCommentForm = document.querySelector('#commentForm');

	if (container && container.classList.contains('hasLoader')) {
		// Load comments on document ready
		let page = 0;

		// Get limitstart value from query to load comments page
		const searchParams = Jcomments.queryString.init(window.location.href),
			paramName = options.pagination_prefix + 'limitstart';

		if (searchParams.has(paramName) && searchParams.get(paramName)) {
			page = searchParams.get(paramName);
		}

		Jcomments.loadComments(null, '', '', page, true);
		// End
	}

	if (addCommentForm) {
		if (window.location.hash === '#addcomment') {
			Jcomments.scrollToByHash('#addcomment');
		}
	}

	// Set the initial value of the height due to the fact that the 'iframe-height' script adds a new height to
	// the current one. Current iframe content height + (20px or 60 px).
	const report_iframe = document.querySelector('#reportFormFrame'),
		subscribe_link = document.querySelector('.cmd-subscribe'),
		comment_form = document.querySelector('#commentForm');

	if (report_iframe) {
		const report_loader = document.querySelector('.report-loader');

		document.querySelector('#reportModal').addEventListener('hidden.bs.modal', function () {
			report_iframe.style.height = 0;
			Jcomments.getIframeContent(report_iframe).querySelector('body').remove();
			report_loader.classList.remove('d-none');
		});
		document.querySelector('#reportModal').addEventListener('show.bs.modal', function () {
			report_iframe.style.height = 0;
		});
		report_iframe.addEventListener('load', function () {
			// Do not remove 'd-none' from classlist. Do it directly in iframe content in 'tmpl/form/report.php'
			Jcomments.iframeHeight(report_iframe);
		});
	}

	if (container) {
		if (subscribe_link) {
			subscribe_link.addEventListener('click', Jcomments.subscribe);
		}

		document.querySelectorAll('.refresh-list').forEach(function(el) {
			if (el) {
				el.addEventListener('click', Jcomments.loadComments);
			}
		});

		if (options.template === 'list') {
			Jcomments.on(container, 'click',
				function (e) {
					e.preventDefault();
					Jcomments.loadComments(null, '', '', this.dataset.page, true, '#comments');
				},
				'.page-link.hasNav'
			);
		}

		Jcomments.on(container, 'click',
			function (e) {
				e.preventDefault();
				Jcomments.scrollToByHash(this.href);
			},
			'.permalink .comment-anchor'
		);
		Jcomments.on(container, 'click', Jcomments.parentComment, '.cmd-parent'); // TODO Not ready
		Jcomments.on(container, 'click', Jcomments.vote, '.cmd-vote');
		Jcomments.on(container, 'click', Jcomments.showEditForm, '.cmd-edit');
		Jcomments.on(container, 'click', Jcomments.state, '.cmd-state');
		Jcomments.on(container, 'click', Jcomments.pin, '.cmd-pin');
		Jcomments.on(container, 'click', Jcomments.delete, '.cmd-delete');
		Jcomments.on(container, 'click',
			function (e) {
				e.preventDefault();
				Jcomments.openWindow(this.href, this.dataset.error);
			},
			'.cmd-ip'
		);
		Jcomments.on(container, 'click', Jcomments.banIP, '.cmd-ban');
		Jcomments.on(container, 'click', Jcomments.reply, '.cmd-reply');
		Jcomments.on(container, 'click', Jcomments.showEditForm, '.cmd-quote'); // TODO Not ready
		Jcomments.on(container, 'click', Jcomments.reportComment, '.cmd-report');
		Jcomments.on(container, 'click', Jcomments.toggleComments, '.cmd-child-toggle');
	}

	if (comment_form) {
		const show_form_btn = document.querySelector('.cmd-showform');

		if (show_form_btn) {
			show_form_btn.addEventListener('click', function (e) {
				e.preventDefault();
				Jcomments.toggleCommentForm();
			});
		}

		Jcomments.initEditorCharsCounter(comment_form);
	}
});
