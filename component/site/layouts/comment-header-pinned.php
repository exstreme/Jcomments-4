<?php
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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

/** @var array $displayData */
?>
<div class="row py-1 border-bottom rounded <?php echo $displayData['comment']->published ? 'text-bg-success' : ''; ?> comment-pinned-title">
	<div class="col-auto">
		<span class="icon icon-pin pe-2"></span> <?php echo JcommentsText::getMessagesBasedOnLanguage($displayData['params']->get('messages_fields'), 'message_comment_pinned', Factory::getApplication()->getLanguage()->getTag(), 'COMMENT_PINNED_TITLE'); ?>
	</div>
</div>
