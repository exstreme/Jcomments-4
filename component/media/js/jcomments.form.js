// phpcs:disable
/**
 * JComments - Joomla Comment System
 * The code for working with the form.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://libra.ms)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

import { JoomlaEditor } from 'editor-api';

let jce_limitreached = false;

(function (Jcomments, document) {
	'use strict';

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
			config = Joomla.getOptions('jcomments', '');

		if (counter_div) {
			if (config.editor.editor_type === 'component') {
				// Do not use .sceditor-container as selector!
				form.querySelector('#' + config.editor.field).after(counter_div);
				counter_div.classList.remove('d-none');
			} else {
				if (config.editor.editor === 'none') {
					let editor = JoomlaEditor.get(config.editor.field);
					const textarea = form.querySelector('joomla-editor-none textarea'),
						maxlength = textarea.getAttribute('maxlength');
					const event = function () {
						if (!maxlength) {
							return true;
						}

						let editor_value = editor.getValue();
						const length = maxlength - editor_value.length,
							charsEl = counter_div.querySelector('.chars');

						charsEl.textContent = (length < 0) ? '0' : length.toString();

						if (editor_value.length >= maxlength) {
							if (jce_limitreached)
							{
								return true;
							}

							jce_limitreached = true; // Display error message only once
							counter_div.insertAdjacentHTML(
								'beforeend',
								'<span class="limit-error badge text-bg-danger bg-opacity-75 fw-normal">' + Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_LONG') + '</span>'
							);
						} else {
							// Check previous state
							if (jce_limitreached)
							{
								counter_div.querySelector(':scope .limit-error').remove();
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
	 * "Show" quote form.
	 *
	 * @param   {Event}  e  Event
	 *
	 * @method  Jcomments.reply
	 * @return  {void}
	 */
	Jcomments.quote = function (e) {
		e.preventDefault();

		if (!document.querySelector('#commentForm')) {
			Jcomments.showEditForm(e);

			return;
		}

		const editor = JoomlaEditor.get(Joomla.getOptions('jcomments', '').editor.field);

		if (!editor) {
			throw new Error('An active editor are not available');
		}

		const comment = this.closest('.comment'), parent_id = parseInt(comment.dataset.id, 10),
			parent_input = document.querySelector('#jform_parent'),
			form_container = document.querySelector('.form-comment-container'),
			cancel_btn = document.querySelector('button[data-submit-task="comment.cancel"]');

		if (parent_input && parent_id > 0) {
			parent_input.value = parent_id;

			Joomla.request({
				url: e.target.dataset.quoteUrl,
				onBefore: function () {
					Jcomments.loader(1);
				},
				onSuccess: function (response) {
					response = Jcomments.parseJson(response);
					editor.setValue(response.data.comment);

					try {
						editor.focus();
					} catch (e) {}

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
				},
				onError: function (xhr) {
					Jcomments.showError(xhr.response, '#comment-item-' + parent_id);
				},
				onComplete: function () {
					Jcomments.loader(0);
				}
			});

			if (cancel_btn.classList.contains('d-none')) {
				cancel_btn.classList.remove('d-none');
			}

			cancel_btn.setAttribute('data-cancel', 'cancelQuote');
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
			Jcomments.showEditForm(e);

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
	 * Save or Preview comment
	 *
	 * @param   {string}  task   Task.
	 *
	 * @method  Jcomments.saveComment
	 * @return  {void}
	 * @todo Refactor
	 */
	Jcomments.saveComment = function (task) {
		const config = Joomla.getOptions('jcomments', ''),
			form = document.getElementById('commentForm'),
			editor_input = document.querySelector('#' + config.editor.field),
			min = parseInt(editor_input.getAttribute('minlength'), 10),
			max = parseInt(editor_input.getAttribute('maxlength'), 10);
		let editor, form_data = new FormData(form), queryString = '';

		form_data.set('task', task);

		editor = JoomlaEditor.get(config.editor.field);

		if (!editor) {
			throw new Error('An active editor are not available');
		}

		// Check comment min length
		if (!isNaN(min)) {
			if (min !== 0 && editor.getValue().length <= min) {
				Jcomments.showError([Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_SHORT')], '#commentForm fieldset', 'error');
				Jcomments.scrollTop(window, Jcomments.offset(document.querySelector('form joomla-alert')).top);

				return;
			}
		}

		// Check comment max length
		if (max !== 0 && editor.getValue().length >= max) {
			Jcomments.showError([Joomla.Text._('ERROR_YOUR_COMMENT_IS_TOO_LONG')], '#commentForm fieldset', 'error');
			Jcomments.scrollTop(window, Jcomments.offset(document.querySelector('form joomla-alert')).top);

			return;
		}

		form_data.set(editor_input.getAttribute('name'), editor.getValue());

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
						Jcomments.scrollTop(window, Jcomments.offset(document.querySelector('form joomla-alert')).top);

						return;
					}

					document.querySelector('#commentForm').insertAdjacentHTML('afterbegin', response.data);
					Jcomments.scrollToByHash('comment-item-preview');
				} else {
					if (response.success) {
						Joomla.renderMessages({'message': [response.message]}, '#commentForm fieldset');
					} else {
						Jcomments.showError(response.message, '#commentForm fieldset', 'error');
						Jcomments.scrollTop(window, Jcomments.offset(document.querySelector('form joomla-alert')).top);
					}
				}
			},
			onError: function (xhr) {
				Jcomments.showError(xhr.response, '#commentForm fieldset', 'error');
				Jcomments.scrollTop(window, Jcomments.offset(document.querySelector('form joomla-alert')).top);
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
	 * Show hidden 'Add comment' form
	 *
	 * @method  Jcomments.showAddForm
	 * @return  {void}
	 */
	Jcomments.showAddForm = function () {
		this.toggleCommentForm(true);
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

		let _return = Joomla.getOptions('jcomments', '').return;

		if (Jcomments.queryString.get(document.location.href, 'task') === 'comment.show') {
			_return = btoa(document.location.href);
		}

		document.location.href = e.target.closest('a').dataset.url + '&return=' + _return;
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
					} else if (view_name !== 'form' && (el.dataset.cancel === 'cancelReply' || el.dataset.cancel === 'cancelQuote')) {
						document.querySelector('#jform_parent').value = '0';

						if (el.dataset.cancel === 'cancelQuote') {
							JoomlaEditor.get(Joomla.getOptions('jcomments', '').editor.field).setValue('');
						}

						el.classList.add('d-none');
						el.dataset.cancel = 'hideAddForm';
					} else {
						submitForm(task, form);
					}
				} else {
					Jcomments.saveComment(task);
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

// Bind some base events
document.addEventListener('DOMContentLoaded', function () {
	const container = document.querySelector('div.comments'),
		comment_form = document.querySelector('#commentForm');

	if (comment_form) {
		const show_form_btn = document.querySelector('.cmd-showform');

		if (show_form_btn) {
			show_form_btn.addEventListener('click', function (e) {
				e.preventDefault();
				Jcomments.toggleCommentForm();
			});
		}

		Jcomments.on(comment_form, 'click', function(e) {
			e.preventDefault();
			this.closest('div.comment-preview').remove();
		}, '.comment-preview-title a.close-preview');
		Jcomments.initEditorCharsCounter(comment_form);

		if (Joomla.getOptions('jcomments', '').editor.editor_type !== 'component') {
			// Bind shortcut to submit form
			document.addEventListener('keyup', function (e) {
				if (e.ctrlKey && e.code === 'Enter') {
					document.querySelector('#commentForm button[data-submit-task="comment.apply"]').click();
				}
			});
		}
	}

	if (container) {
		Jcomments.on(container, 'click', Jcomments.showEditForm, '.cmd-edit');
		Jcomments.on(container, 'click', Jcomments.reply, '.cmd-reply');
		Jcomments.on(container, 'click', Jcomments.quote, '.cmd-quote');
	}
});
