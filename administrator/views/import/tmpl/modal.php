<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('jcomments.stylesheet');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');

$source   = $this->state->get('import.source');
$language = $this->state->get('import.language');
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			JCommentsImport.setup('<?php echo $this->importUrl; ?>');
			JCommentsImport.onSuccess = function () {
				var oldHeader = $('#jcomments-modal-header').html();
				var oldMessage = $('#jcomments-modal-message').html();

				$('#jcomments-modal-header').html('<?php echo Text::_('A_REFRESH_OBJECTS_INFO'); ?>');
				$('#jcomments-modal-message').html('');

				JCommentsObjects.setup('<?php echo $this->objectsUrl; ?>');
				JCommentsObjects.onSuccess = function () {
					if (oldHeader) {
						$('#jcomments-modal-header').html(oldHeader);
						$('#jcomments-modal-message').html(oldMessage);
					}
				};
				JCommentsObjects.run('<?php echo $this->hash; ?>', 0, null, null);
			};
			JCommentsImport.run('<?php echo $source; ?>', '<?php echo $language; ?>', 0);
		});
	})(jQuery);
</script>

<div id="jcomments-modal-container">
	<br/>

	<h1 id="jcomments-modal-header"><?php echo Text::_('A_IMPORT'); ?></h1>

	<p id="jcomments-modal-message">&nbsp;</p>

	<div id="jcomments-progress-container" class="jcomments-progressbar"></div>
</div>
