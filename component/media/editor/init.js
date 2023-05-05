// phpcs:disable
document.addEventListener('DOMContentLoaded', function () {
	const jce = document.getElementById('jform_comment');
	const jce_config = JSON.parse(jce.dataset.config);

	sceditor.icons.monocons.icons.hide = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.75 -7 19.18 7"><path d="M1.38 0 .75 0 .75-7 1.38-7 1.38-3.77 4.78-3.77 4.78-7 5.41-7 5.41 0 4.78 0 4.78-3.17 1.38-3.17 1.38 0ZM8.55-.48 8.55 0 6.67 0 6.67-.48 7.27-.55 7.27-6.44 6.72-6.51 6.72-7 8.5-7 8.5-6.51 7.95-6.44 7.95-.55 8.55-.48ZM10.06 0 10.06-7 11.86-7 11.86-7Q13.96-7 14.6-5.24L14.6-5.24 14.6-5.24Q14.84-4.6 14.84-3.56L14.84-3.56 14.84-3.56Q14.84-2.51 14.61-1.79L14.61-1.79 14.61-1.79Q14.38-1.08 13.96-.68L13.96-.68 13.96-.68Q13.54-.28 12.95-.14L12.95-.14 12.95-.14Q12.35 0 11.61 0L11.61 0 10.06 0ZM11.81-6.45 10.74-6.45 10.74-.55 11.56-.55 11.56-.55Q12.08-.55 12.56-.64L12.56-.64 12.56-.64Q14.16-.92 14.16-3.61L14.16-3.61 14.16-3.61Q14.16-5.12 13.46-5.84L13.46-5.84 13.46-5.84Q12.85-6.45 11.81-6.45L11.81-6.45ZM19.73-7 19.68-6.4 16.77-6.4 16.77-3.77 19.47-3.77 19.42-3.17 16.77-3.17 16.77-.6 19.93-.6 19.88 0 16.09 0 16.09-7 19.73-7Z"/></svg>';

	sceditor.formats.bbcode.set('quote', {
		tags: {
			'blockquote': null
		},
		quoteType: sceditor.BBCodeParser.QuoteType.always,
		format: function (element, content) {
			let bbcodeAttr = '', citeEl = element.querySelector('span.author');

			if (citeEl !== null) {
				bbcodeAttr = ' name="' + citeEl.textContent + '"';
				content = content.replace(Joomla.Text._('COMMENT_TEXT_QUOTE') + citeEl.textContent, '');
			}

			return '[quote' + bbcodeAttr + ']' + content + '[/quote]';
		},
		html: function (token, attrs, content) {
			let name = '';

			if (Object.keys(attrs).length > 0) {
				name = '<span class="cite d-block">' + Joomla.Text._('COMMENT_TEXT_QUOTE') +
					'<span class="author fst-italic fw-semibold">' + attrs.name + '</span>' +
				'</span>';
			}

			return '<blockquote class="blockquote">' + name + content + '</blockquote>';
		}
	});
	sceditor.command.set('quote', {
		exec: function () {
			this.wysiwygEditorInsertHtml('<blockquote class="blockquote">', '<br /></blockquote>');
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
				el.classList.forEach((item, i) => {
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
		}
	});

	sceditor.create(jce, jce_config);

	let editorInstance = sceditor.instance(jce);

	editorInstance.bind('keyup blur focus', function () {
		if (document.querySelector('.jce-counter')) {
			const length = parseInt(document.querySelector('#jform_comment').getAttribute('maxlength'), 10) - editorInstance.val().length;
			document.querySelector('.jce-counter .chars').textContent = length.toString();
		}
	});
});
