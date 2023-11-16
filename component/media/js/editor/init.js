// phpcs:disable
document.addEventListener('DOMContentLoaded', function () {
	const jce = document.getElementById('jform_comment');
	const jce_config = JSON.parse(jce.dataset.config);
	let jce_limitreached = false;
	const icon_spoiler = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><g stroke-width="1.0595"><path d="m1.9738 1.0168v4.9944h28.022v-4.9944z"/><path d="m1.9738 25.977v4.9944h28.022v-4.9944z"/></g><g><rect x="6.559" y="9.4897" width="3.74" height="7.296" stroke-width=".70362"/><path transform="matrix(.94771 .99029 -1.6415 .57174 44.965 2.7122)" d="m5.3868 25.343-6.8114 0.02507 3.384-5.9114z"/></g><g transform="rotate(180 15.463 20.048)"><rect x="5.515" y="17.55" width="3.74" height="7.0931" stroke-width=".69377"/><path transform="matrix(.94771 .99029 -1.6415 .57174 43.921 10.783)" d="m5.3868 25.343-6.8114 0.02507 3.384-5.9114z"/></g></svg>';

	if (sceditor.icons.material) {
		sceditor.icons.material.icons.hide = '<text fill="#000000" text-align="center" text-anchor="middle" style="line-height:0" xml:space="preserve" y="16" font-size=".8em" x="12">HIDE</text>';
		sceditor.icons.material.icons.spoiler = icon_spoiler;
	} else if (sceditor.icons.monocons) {
		sceditor.icons.monocons.icons.hide = '<text fill="#000000" text-align="center" text-anchor="middle" style="line-height:0" xml:space="preserve" y="12" font-size=".6em" x="8">HIDE</text>';
		sceditor.icons.monocons.icons.spoiler = icon_spoiler;
	}

	if (sceditor.formats.bbcode) {
		sceditor.formats.bbcode.set('quote', {
			tags: {
				'blockquote': null
			},
			quoteType: sceditor.BBCodeParser.QuoteType.never,
			format: function (element, content) {
				let bbcodeAttr = '',
					citeEl = element.querySelector('span.author'),
					parentId = !empty(element.dataset.quoted) ? parseInt(element.dataset.quoted, 10) : 0,
					name = '';

				if (citeEl !== null) {
					name = citeEl.textContent;
					content = content.replace(Joomla.Text._('COMMENT_TEXT_QUOTE') + citeEl.textContent, '');

					if (!empty(parentId) && !isNaN(parseInt(parentId, 10))) {
						name += ';' + parentId;
					}

					bbcodeAttr = ' name="' + name + '"';
				}

				return '[quote' + bbcodeAttr + ']' + content + '[/quote]';
			},
			html: function (token, attrs, content) {
				let name = '', id = '';

				if (Object.keys(attrs).length > 0) {
					if (!empty(attrs.name)) {
						const separatorPos = attrs.name.lastIndexOf(';');

						if (separatorPos !== -1) {
							name = attrs.name.substring(0, separatorPos);
							id = attrs.name.substring(separatorPos + 1);
						} else {
							name = attrs.name;
						}

						if (!empty(id) && !isNaN(parseInt(id, 10))) {
							id = ' data-quoted="' + parseInt(id, 10) + '"';
						}

						name = '<span class="cite d-block">' + Joomla.Text._('COMMENT_TEXT_QUOTE') +
							'<span class="author fst-italic fw-semibold">' + name + '</span>' +
						'</span>';
					}
				}

				return '<blockquote class="blockquote"' + id + '>' + name + content + '</blockquote>';
			}
		});
		sceditor.command.set('quote', {
			exec: function () {
				this.wysiwygEditorInsertHtml('<blockquote class="blockquote">', '<br /></blockquote>');
			},
			state: function (parent) {
				return sceditor.dom.closest(parent, 'blockquote') ? 1 : 0;
			}
		});

		sceditor.formats.bbcode.set('hide', {
			tags: {
				'span': {
					'class': ['badge text-bg-light hide']
				}
			},
			format: function (element, content) {
				return '[hide]' + content + '[/hide]';
			},
			html: function (token, attrs, content) {
				return '<span class="badge text-bg-light hide">' + content + '</span>';
			}
		});
		sceditor.command.set('hide', {
			exec: function () {
				this.wysiwygEditorInsertHtml('<span class="badge text-bg-light hide">', '</span>');
			},
			txtExec: function () {
				this.insertText('[hide]', '[/hide]');
			},
			state: function (parent) {
				return sceditor.dom.closest(parent, 'span.hide') ? 1 : 0;
			},
			tooltip: Joomla.Text._('FORM_BBCODE_HIDE')
		});

		sceditor.formats.bbcode.set('code', {
			tags: {
				'figure': {
					'class': ['codeblock']
				}
			},
			quoteType: sceditor.BBCodeParser.QuoteType.always,
			format: function (element, content) {
				let bbcodeAttr = '', el = element.querySelector('code[class]');

				if (el !== null) {
					// Get language name from class name
					el.classList.forEach((item) => {
						/*
						 * CODE
						 * Match programming language name in lower case and can contain symbols: #, ., +, !, --, ++, *, /.
						 * See https://en.wikipedia.org/wiki/List_of_programming_languages
						*/
						const langName = item.match(/lang-([a-z0-9\#\.\+\!\-\-\+\+\*\/]+)/);

						if (langName && langName[1] !== null) {
							bbcodeAttr = '="' + langName[1] + '"'.replace(' ', '');
						}
					});

					// Replace figcaption's Node.textContent by empty value
					content = content.replace(Joomla.Text._('COMMENT_TEXT_CODE') + "\n", '');
				}

				return '[code' + bbcodeAttr + ']' + content + '[/code]';
			},
			html: function (token, attrs, content) {
				let langName = '';

				if (Object.keys(attrs).length > 0) {
					langName = attrs.defaultattr.replace(' ', '');
				}

				return '<figure class="codeblock">' +
					'<figcaption class="code">' + Joomla.Text._('COMMENT_TEXT_CODE') + '</figcaption>' +
					'<pre class="card card-body p-2"><code class="lang-' + langName + '">' + content + '</code></pre>' +
				'</figure>';
			}
		});
		sceditor.command.set('code', {
			exec: function () {
				this.wysiwygEditorInsertHtml(
					'<figure class="codeblock"><figcaption class="code">' + Joomla.Text._('COMMENT_TEXT_CODE') + '</figcaption>' +
						'<pre class="card card-body p-2"><code class="lang-">',
					'</code></pre></figure>'
				);
			},
			state: function (parent) {
				return sceditor.dom.closest(parent, 'figure.codeblock') ? 1 : 0;
			}
		});

		sceditor.command.set('font', {
			state: function (parent) {
				return sceditor.dom.closest(parent, 'font[face]') ? 1 : 0;
			}
		});

		sceditor.command.set('size', {
			state: function (parent) {
				return sceditor.dom.closest(parent, 'font[size]') ? 1 : 0;
			}
		});
		sceditor.command.set('spoiler', {
			exec: function () {
				this.insertText('[spoiler]', '[/spoiler]');
			},
			txtExec: function () {
				this.insertText('[spoiler]', '[/spoiler]');
			},
			tooltip: Joomla.Text._('FORM_BBCODE_SPOILER')
		});
	}

	sceditor.create(jce, jce_config);

	let editorInstance = sceditor.instance(jce);

	editorInstance.bind('keyup blur focus', function () {
		const counterEl = document.querySelector('.jce-counter');
		const maxlength = parseInt(jce_config.maxlength, 10);

		if (counterEl) {
			if (!maxlength) {
				return true;
			}

			const length = maxlength - editorInstance.val().length;
			const charsEl = document.querySelector('.jce-counter .chars');
			charsEl.textContent = (length < 0) ? '0.' : length.toString() + '.';

			if (editorInstance.val().length >= maxlength) {
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
		}
	})
	.addShortcut('ctrl+enter', function () {
		// Required to update original textarea when shortcut pressed in editor area.
		editorInstance.updateOriginal();
		document.forms['comments-form'].submit();
	});

	/** @method JcommentsFormSubmit() */
	const comment_form = document.querySelector('#comments-form');

	if (comment_form !== null) {
		document.getElementById('comments-form').addEventListener('submit', function (e) {
			e.preventDefault();

			const length = editorInstance.val().length,
				min = parseInt(jce_config.minlength, 10),
				max = parseInt(jce_config.maxlength, 10);
console.log(length);
			if (min !== 0 && length <= min) {
				Jcomments.showError('', '#system-message-container'); // TODO Не работает
			}

			if (max !== 0 && length >= max) {
				Jcomments.showError('', '#system-message-container');
			}
		});
	}
});
