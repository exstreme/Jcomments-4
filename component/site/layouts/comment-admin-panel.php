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

/** @var JoomlaTuneTemplate $displayData */
$comment = $displayData->getVar('comment');

if ($displayData->getVar('comments-panel-visible', 0) == 1): ?>
	<div class="toolbar" id="comment-toolbar-<?php echo $comment->id; ?>">
		<div class="main-actions">
			<?php if ($displayData->getVar('button-edit') == 1): ?>
				<a class="toolbar-button-edit" href="#" title="<?php echo Text::_('BUTTON_EDIT'); ?>"
				   onclick="jcomments.editComment(<?php echo $comment->id; ?>); return false;">
					<span class="icon-edit" aria-hidden="true"></span>
				</a>
			<?php endif; ?>

			<?php if ($displayData->getVar('button-delete') == 1): ?>
				<a class="link-danger toolbar-button-delete" href="#" title="<?php echo Text::_('BUTTON_DELETE'); ?>"
				   onclick="if (confirm('<?php echo Text::_('BUTTON_DELETE_CONIRM'); ?>')){jcomments.deleteComment(<?php echo $comment->id; ?>);}return false;">
					<span class="icon-delete" aria-hidden="true"></span>
				</a>
			<?php endif; ?>

			<?php if ($displayData->getVar('button-publish') == 1):
				$text  = $comment->published ? Text::_('BUTTON_UNPUBLISH') : Text::_('BUTTON_PUBLISH');
				$class = $comment->published ? 'icon-publish link-success' : 'icon-unpublish link-secondary'; ?>
				<a class="toolbar-button-<?php echo $class; ?>" href="#" title="<?php echo $text; ?>"
				   onclick="jcomments.publishComment(<?php echo $comment->id; ?>, this);return false;">
					<span class="<?php echo $class; ?>" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</div>

		<div class="user-actions">
			<?php if ($displayData->getVar('button-ip') == 1): ?>
				<a class="toolbar-button-ip" href="#" title="<?php echo Text::_('BUTTON_IP') . ' ' . $comment->ip; ?>"
				   onclick="jcomments.go('https://www.ripe.net/perl/whois?searchtext=<?php echo $comment->ip; ?>');return false;">
					<?php echo $comment->ip; ?>
				</a>
			<?php endif; ?>

			<?php if ($displayData->getVar('button-ban') == 1): ?>
				<a class="toolbar-button-ban" href="#" title="<?php echo Text::_('BUTTON_BANIP'); ?>"
				   onclick="jcomments.banIP(<?php echo $comment->id; ?>);return false;">
					<span class="icon-unpublish link-secondary" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
<?php endif;
