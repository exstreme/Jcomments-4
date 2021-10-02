<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('jcomments.stylesheet');

$document = Factory::getDocument();
$document->addScript(JURI::root(true) . '/administrator/components/com_jcomments/assets/js/jcomments.progressbar.js');
$document->addScript(JURI::root(true) . '/administrator/components/com_jcomments/assets/js/jcomments.objects.js?v=5');
?>
<script type="text/javascript">
    (function ($) {
        $(document).ready(function () {
            JCommentsObjects.setup('<?php echo $this->url; ?>').run('<?php echo $this->hash; ?>', 0, null, null, null);
        });
    })(jQuery);
</script>

<div class="main-card">
	<br/>
	<h1 class="text-center"><?php echo Text::_('A_REFRESH_OBJECTS_INFO'); ?></h1>
	<p id="jcomments-modal-message">&nbsp;</p>

	<div id="jcomments-progress-container" class="jcomments-progressbar bg-success text-white"></div>
</div>
