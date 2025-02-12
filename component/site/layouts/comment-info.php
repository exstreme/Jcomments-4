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
use Joomla\CMS\Layout\LayoutHelper;

/** @var JoomlaTuneTemplate $displayData */
$comment = $displayData->getVar('comment');
$commentBoxIndentStyle = ($displayData->getVar('avatar') == 1) ? ' avatar-indent' : '';
?>
<dl class="comment-info text-break text-muted<?php echo $commentBoxIndentStyle; ?>">
	<?php if (($displayData->getVar('comment-display-title') > 0) && ($comment->title != '')): ?>
		<dt>
			<span class="text-break comment-title"><?php echo $comment->title; ?></span>
		</dt>
	<?php endif; ?>

	<dd class="permalink">
		<a href="<?php echo $displayData->getVar('thisurl', ''); ?>#comment-<?php echo $comment->id; ?>"
		   class="comment-anchor" id="comment-<?php echo $comment->id; ?>">#<?php echo $displayData->getVar('comment-number', 1); ?>
		</a>
	</dd>

	<dd class="createdby" itemprop="author" itemscope="" itemtype="https://schema.org/Person">
	<?php if ($displayData->getVar('comment-show-homepage') == 1): ?>
		<a href="<?php echo $comment->homepage; ?>" rel="nofollow" itemprop="url" target="_blank">
			<span class="icon-user icon-fw" aria-hidden="true"></span>
			<span itemprop="name"><?php echo $comment->author; ?></span>
		</a>
	<?php else: ?>
		<span class="icon-user icon-fw" aria-hidden="true"></span>
		<span itemprop="name"><?php echo $comment->author ?></span>
	<?php endif; ?>
	</dd>
<!-- 152-FZ RF -->
	<?php /* if (($displayData->getVar('comment-show-email') > 0) && ($comment->email != '')): ?>
		<dd>
			<?php // Only Super user can see real email.
			if (!Factory::getApplication()->getIdentity()->get('isRoot')):
				echo JComments::maskEmail($comment->id, $comment->email, true);
			else: ?>
				<a class="comment-email" href="mailto:<?php echo $comment->email; ?>">
					<span class="icon-envelope icon-fw" aria-hidden="true"></span>
					<?php echo $comment->email; ?>
				</a>
			<?php endif; ?>
		</dd>
	<?php endif; */ ?>

	<dd class="published">
		<span class="icon-calendar icon-fw" aria-hidden="true"></span>
		<time datetime="<?php echo HTMLHelper::_('date', $comment->date, 'c'); ?>" itemprop="datePublished">
			<?php echo HTMLHelper::_('date', $comment->date, 'DATE_FORMAT_LC5'); ?>
		</time>
	</dd>

	<?php if ($comment->parent > 0): ?>
		<dd class="parent-comment">
			<a href="#comment-item-<?php echo $comment->parent; ?>"
			   title="<?php echo Text::_('GOTO_PREV_COMMENT_ANCHOR'); ?>">
				<span class="icon-arrow-up-4 icon-fw" aria-hidden="true"></span>
			</a>
		</dd>
	<?php endif; ?>

	<?php if ($displayData->getVar('comment-show-vote', 0) == 1): ?>
		<dd class="comments-vote">
			<div id="comment-vote-holder-<?php echo $comment->id; ?>">
				<?php if ($displayData->getVar('button-vote', 0) == 1): ?>
					<a href="#" class="link-success" title="<?php echo Text::_('BUTTON_VOTE_GOOD'); ?>"
					   onclick="jcomments.voteComment(<?php echo $comment->id; ?>, 1);return false;">
						<span class="icon-thumbs-up" aria-hidden="true"></span>
					</a>&nbsp;
					<a href="#" class="link-danger" title="<?php echo Text::_('BUTTON_VOTE_BAD'); ?>"
					   onclick="jcomments.voteComment(<?php echo $comment->id; ?>, -1);return false;">
						<span class="icon-thumbs-down" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php echo LayoutHelper::render('comment-vote-value', $displayData, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?>
			</div>
		</dd>
	<?php endif; ?>
</dl>
