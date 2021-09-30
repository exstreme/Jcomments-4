/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

(function ($) {
    var JCommentsImport = {
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

        run: function (source, language, start) {
            if (JCommentsImport.progress == null) {
                JCommentsImport.progress = new JCommentsProgressbar('#jcomments-progress-container');
            }
            $.ajax({
                type: "POST",
                url: JCommentsImport.url,
                data: {source: source, language: language, start: start},
                dataType: 'json'
            }).done(function (data) {
                    if (data) {
                        var count = data['count'];
                        var total = data['total'];

                        var source = data['source'];
                        var language = data['language'];
                        var start = data['start'];

                        if (data['percent']) {
                            JCommentsImport.progress.set(data['percent']);
                        }

                        if (count < total) {
                            JCommentsImport.run(source, language, start);
                        } else {
                            if (data['message']) {
                                $('#jcomments-modal-message').html(data['message']).show();
                                JCommentsImport.progress.hide();
                            }

                            if (typeof JCommentsImport.onSuccess == 'function') {
                                JCommentsImport.onSuccess();
                            }
                        }
                    } else {
                        if (typeof JCommentsImport.onFailure == 'function') {
                            JCommentsImport.onFailure();
                        }
                    }
                });
        }
    };

    window.JCommentsImport = JCommentsImport;
})(jQuery);