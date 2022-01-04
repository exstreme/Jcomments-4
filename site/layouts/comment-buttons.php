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

if ($displayData->getVar('button-reply') == 1
	|| $displayData->getVar('button-quote') == 1
	|| $displayData->getVar('button-report') == 1): ?>
	<div class="comments-buttons">
		<?php if ($displayData->getVar('button-reply') == 1): ?>
			<a href="#" onclick="jcomments.showReply(<?php echo $comment->id; ?>); return false;">
				<?php echo Text::_('BUTTON_REPLY'); ?>
			</a>
			<?php if ($displayData->getVar('button-quote') == 1): ?>
				&vert; <a href="#" onclick="jcomments.showReply(<?php echo $comment->id; ?>,1); return false;">
					<?php echo Text::_('BUTTON_REPLY_WITH_QUOTE'); ?>
				</a> &vert;
			<?php endif; ?>
		<?php endif; ?>

		<?php if ($displayData->getVar('button-quote') == 1): ?>
			<a href="#" onclick="jcomments.quoteComment(<?php echo $comment->id; ?>); return false;">
				<?php echo Text::_('BUTTON_QUOTE'); ?>
			</a>
		<?php endif; ?>

		<?php if ($displayData->getVar('button-report') == 1): ?>
			<?php if ($displayData->getVar('button-quote') == 1 || $displayData->getVar('button-reply') == 1): ?>
				&vert;
			<?php endif; ?>
			<a href="#" onclick="jcomments.reportComment(<?php echo $comment->id; ?>); return false;">
				<?php echo Text::_('BUTTON_REPORT'); ?>
			</a>
		<?php endif; ?>

        <?php if (isset($comment->children) && $comment->children != 0) : ?>
            <?php if ($displayData->getVar('button-quote') == 1 || $displayData->getVar('button-reply') == 1 || $displayData->getVar('button-report') == 1): ?>
				&vert;
			<?php endif; ?>
            <a href="#" id="hide-button-<?php echo $comment->id; ?>" onclick="jcomments.hideChildComments(this, <?php echo $comment->id; ?>); return false;">
                <?php echo Text::_('BUTTON_HIDE'); ?>
            </a>
            <a href="#" id="show-button-<?php echo $comment->id; ?>" onclick="jcomments.showChildComments(this, <?php echo $comment->id; ?>); return false;" style="display: none">
                <?php echo Text::_('BUTTON_SHOW'); ?>
            </a>
        <?php endif; ?>
	</div>
<?php endif;
