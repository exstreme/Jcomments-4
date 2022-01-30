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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var JoomlaTuneTemplate $displayData */
$objectID    = $displayData->getVar('comment-object_id');
$objectGroup = $displayData->getVar('comment-object_group');
?>
<div class="comments-list-footer">
	<?php if ($displayData->getVar('comments-refresh', true)): ?>
	<div class="comments-refresh">
		<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>"
		   onclick="jcomments.showPage(<?php echo $objectID; ?>, '<?php echo $objectGroup; ?>', 0); return false;">
			<span aria-hidden="true" class="icon-loop icon-fw"></span> <?php echo Text::_('BUTTON_REFRESH'); ?>
		</a>
	</div>
	<?php endif; ?>

	<?php if ($displayData->getVar('comments-rss', 1) == 1): ?>
	<div class="comments-rss">
		<a href="<?php echo Route::_('index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $objectID . '&object_group=' . $objectGroup . '&format=feed'); ?>"
		   title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
			<span aria-hidden="true" class="icon-rss icon-fw"></span> <?php echo Text::_('BUTTON_RSS'); ?>
		</a>
	</div>
	<?php endif; ?>

	<?php if ($displayData->getVar('comments-can-subscribe', 0) == 1):
		$isSubscribed = $displayData->getVar('comments-user-subscribed', 0);
		$text = $isSubscribed ? Text::_('BUTTON_UNSUBSCRIBE') : Text::_('BUTTON_SUBSCRIBE');
		$func = $isSubscribed ? 'remove' : 'add';
		$url  = 'index.php?option=com_jcomments&task=subscriptions.' . $func . '&object_id=' . $objectID
			. '&object_group=' . $objectGroup . '&return=' . base64_encode(Uri::getInstance());
		?>
	<div class="comments-subscription">
		<a href="<?php echo Route::_($url); ?>" class="cmd-subscribe" title="<?php echo $text; ?>" rel="nofollow">
			<span aria-hidden="true" class="icon-mail icon-fw"></span> <?php echo $text; ?>
		</a>
	</div>
	<?php endif; ?>
</div>
