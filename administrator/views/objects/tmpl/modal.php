<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			JCommentsObjects.setup('<?php echo $this->url; ?>').run('<?php echo $this->hash; ?>', 0, null, null, null);
		});
	})(jQuery);
</script>

<div id="jcomments-modal-container">
	<br />
	<h1 id="jcomments-modal-header"><?php echo JText::_('A_REFRESH_OBJECTS_INFO'); ?></h1>

	<p id="jcomments-modal-message">&nbsp;</p>

	<div id="jcomments-progress-container" class="jcomments-progressbar"></div>
</div>