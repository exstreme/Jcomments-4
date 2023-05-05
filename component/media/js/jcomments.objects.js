/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

jQuery(document).ready(function ($) {
	$('#objectsUpdateForm').on('submit', function (e) {
		e.preventDefault();

		const step_input = $('#step'),
			update_btn = $('.cmd-objects-update'),
			close_btn = $('.cmd-close');

		if (parseInt(step_input.val()) === 0) {
			$('.progress-bar').prop('aria-valuenow', 0).text('0%').width('0%');
		}

		update_btn.prop('disabled', true);
		close_btn.addClass('disabled');
		close_btn.prop({'aria-disabled': true, 'tab-index': -1});

		$.ajax({
			type: 'POST',
			url: $(this).attr('action'),
			data: $(this).serialize(),
			dataType: 'json'
		}).done(function (response) {
			/** @var response.messages object|null */
			/*if (!response.success) {
				let messages = [];

				// Show all enqueued messages
				if (typeof response.messages === 'object' && response.messages) {
					messages = response.messages;
					messages.error.push(response.message);
				} else {
					messages = {'error': [response.message]};
				}

				Joomla.renderMessages(messages, '.main-card');
				$('.cmd-objects-update').removeAttr('disabled');
			} else {
				const percent = parseInt(response.data.percent),
					  log = $('.log');

				$('.progress-bar').attr('aria-valuenow', percent).text(percent + '%').width(percent + '%');
				step_input.val(response.data.step);

				if (response.data.step === 0) {
					$('li', log).remove();
				}

				log.append('<li class="list-group-item">' + response.data.log + '</li>');

				$('#objectsUpdateForm').trigger('submit');*/




				/*if (response.data.count < response.data.total) {
					$('#objectsUpdateForm').trigger('submit');
				} else {
					step_input.val('0');
					$('.cmd-objects-update').removeAttr('disabled');
				}*/
			//}
		}).fail(function (xhr) {
			Joomla.renderMessages({'error': [xhr.status + ' ' + xhr.statusText]}, '.main-card');
			$('.progress-bar').prop('aria-valuenow', 0).text('0%').width('0%');
			update_btn.prop('disabled', false);
			close_btn.removeClass('disabled');
			close_btn.removeProp('aria-disabled');
			close_btn.prop('tab-index', 0);
		});
	});
	/*var JCommentsObjects = {
		progress: null,
		url: null,

		onSuccess: function () {
		},
		onFailure: function () {
		},

		setup: function (url) {
			this.url = url;
			return this;
		},

		run: function (hash, step, object_group, language, language_sef) {
			if (JCommentsObjects.progress == null) {
				JCommentsObjects.progress = new JCommentsProgressbar('#jcomments-progress-container');
			}
			$.ajax({
				type: "POST",
				url: JCommentsObjects.url + (language_sef != null ? '&lang=' + language_sef : ''),
				data: {hash: hash, step: step, object_group: object_group, lang: language},
				dataType: 'json'
			}).done(function (data) {
				if (data) {
					var count = data['count'];
					var total = data['total'];

					var hash = data['hash'];
					var step = data['step'];
					var object_group = data['object_group'];
					var language = data['lang'];
					var language_sef = data['lang_sef'];

					if (data['percent']) {
						JCommentsObjects.progress.set(data['percent']);
					}

					if (count < total) {
						JCommentsObjects.run(hash, step, object_group, language, language_sef);
					} else {
						if (data['message']) {
							$('#jcomments-modal-message').html(data['message']).show();
							JCommentsObjects.progress.hide();
						}

						if (typeof JCommentsObjects.onSuccess == 'function') {
							JCommentsObjects.onSuccess();
						}
					}
				} else {
					if (typeof JCommentsObjects.onFailure == 'function') {
						JCommentsObjects.onFailure();
					}
				}
			});
		}
	};

	window.JCommentsObjects = JCommentsObjects;*/
});
