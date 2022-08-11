jQuery(document).ready(function ($) {
	$('.cmd-subscribe').on('click',function (e) {
		e.preventDefault();

		let $this = $(this);

		Joomla.request({
			url: $this.attr('href') + '&format=json',
			onSuccess: function (response) {
				let _response = JSON.parse(response);

				if (_response.success) {
					$this.attr({
						href: _response.data.href,
						title: _response.data.title
					});
					$this.html('<span aria-hidden="true" class="icon-mail icon-fw me-1"></span>' + _response.data.title);

					Joomla.renderMessages({'message': [_response.message]}, '.comments-list-footer');
				} else {
					Joomla.renderMessages({'warning': [_response.message]}, '.comments-list-footer');
				}
			},
			onError: function (xhr) {
				let response = JSON.parse(xhr.responseText);
				Joomla.renderMessages({'error': [response.message]}, '.comments-list-footer');
			}
		});
	});

	const formIframe = $('iframe.commentsFormFrame');

	formIframe.on('load', function () {
		const iframeDom = formIframe.contents();

		if (window.parent.location.hash === '#addcomments') {
			$(window.parent).scrollTop($(parent.document).find('#addcomments').offset().top);
		}

		iframeDom.on('click', '.cmd-showform', function (e) {
			e.preventDefault();
			$('.form-layout', iframeDom).toggle();

			// We cannot hide parent div.
			$(this).hide();

			formIframe.css('height', parseInt(iframeDom.find('body').outerHeight(), 10) + 60 + 'px');
			$(window.parent).scrollTop($(parent.document).find('.commentsFormWrapper').offset().top);
		});
	});
});
