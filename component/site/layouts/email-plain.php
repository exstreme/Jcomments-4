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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * @var array $displayData
 * @see NotificationHelper::getMessageBody()
 */
$comment = $displayData['data'];

if ($comment->notification_type == 'comment-delete' || $comment->notification_type == 'moderate-delete'):
	echo Text::sprintf('NOTIFICATION_SUBJECT_DELETED', $comment->object_title) . "\n";
else:
	echo Text::_('EMAIL_HEADER'); ?> <?php echo $comment->object_title . "\n";
endif;
?>
>------------------------------------------
<?php if ($displayData['report']): ?>
><?php echo htmlspecialchars_decode(Text::sprintf('REPORT_NOTIFICATION_MESSAGE', $comment->author, $comment->report_name, $comment->ip)); ?>
	<?php if ($comment->report_reason != ''): ?>
<?php echo "\n>\n>"; ?><?php echo Text::_('REPORT_NOTIFICATION_MESSAGE_REASON'); ?> - <?php echo $comment->report_reason; ?>
	<?php endif; ?>
<?php echo "\n"; ?>
>------------------------------------------
<?php echo "\n"; ?>
<?php endif; ?>
<?php echo Text::_('NOTIFICATION_COMMENT_NAME'); ?>: <?php echo $comment->author . "\n"; ?>
<?php if ($displayData['isAdmin'] && !empty($comment->email)): ?><?php echo $comment->email; ?>, IP: <?php echo $comment->ip . "\n"; ?><?php endif; ?>
<?php echo Text::_('NOTIFICATION_COMMENT_DATE'); ?>: <?php echo HTMLHelper::_('date', $comment->date, 'DATE_FORMAT_LC5') . "\n"; ?>
<?php if ($comment->title > ''): ?>
<?php echo Text::_('FORM_TITLE'); ?>: <?php echo htmlspecialchars($comment->title, ENT_QUOTES) . "\n"; ?>
<?php endif; ?>
<?php echo Text::_('NOTIFICATION_COMMENT_TEXT') . ": \n"; ?>
>------------------------------------------
<?php echo $comment->comment . "\n"; ?>
<?php if ($comment->notification_type != 'comment-delete' && $comment->notification_type != 'moderate-delete'): ?>
>------------------------------------------
<?php echo Text::_('BUTTON_PERMALINK'); ?>

<?php echo Route::_($comment->object_link) ?>#comment-<?php echo $comment->id . "\n";
endif;
?>
<?php if ($displayData['isAdmin']): ?>
>------------------------------------------
<?php if ($displayData['config']->get('enable_quick_moderation')):
$aTag   = array();
$return = '&return=' . base64_encode(Route::_($comment->object_link));

// Publish/unpublish link
$action = ($comment->published == 0) ? 'publish' : 'unpublish';
$hash   = JcommentsFactory::getCmdHash($action, $comment->id);
$link   = 'index.php?option=com_jcomments&task=comment.' . $action . '&id=' . $comment->id . '&hash=' . $hash;
$aTag[] = Text::_(strtoupper($action)) . "\n" . Route::link('site', $link, false, 0, true) . $return;

// Delete link
$hash   = JcommentsFactory::getCmdHash('delete', $comment->id);
$link   = 'index.php?option=com_jcomments&task=comment.delete&id=' . $comment->id . '&hash=' . $hash;
$aTag[] = Text::_('JACTION_DELETE') . "\n" . Route::link('site', $link, false, 0, true) . $return;

if ($displayData['config']->get('enable_blacklist'))
{
	$hash   = JcommentsFactory::getCmdHash('banIP', $comment->id);
	$link   = 'index.php?option=com_jcomments&task=comment.banIP&id=' . $comment->id . '&hash=' . $hash;
	$aTag[] = Text::_('BUTTON_BANIP') . "\n" . Route::link('site', $link, false, 0, true) . $return;
}

if (count($aTag))
{
echo Text::_('QUICK_MODERATION') . "\n" . implode("\n\n", $aTag) . "\n";
}
	endif;
endif; ?>
>------------------------------------------
<?php echo Text::_('NOTIFICATION_COMMENT_UNSUBSCRIBE_MESSAGE') . "\n"; ?>
>------------------------------------------
<?php echo Text::_('FORM_TOS'); ?>

<?php echo Route::link('site', 'index.php?option=com_jcomments&task=terms', false, 0, true) . "\n"; ?>

<?php echo Text::_('FORM_PRIVACY'); ?>

<?php echo Route::link('site', 'index.php?option=com_jcomments&task=privacy', false, 0, true) . "\n"; ?>
>------------------------------------------
<?php echo Text::_('NOTIFICATION_COMMENT_UNSUBSCRIBE_LINK'); ?>

<?php echo Route::link('site', 'index.php?option=com_jcomments&task=subscription.remove&hash=' . $displayData['hash'], false, 0, true);
