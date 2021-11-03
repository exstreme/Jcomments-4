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

use Joomla\CMS\Layout\LayoutHelper;

/**
 * Flat comments list template
 */
class jtt_tpl_list extends JoomlaTuneTemplate
{
	function render()
	{
		$comments = $this->getVar('comments-items');

		if (isset($comments))
		{
			// display full comments list with navigation and other stuff
			$this->getHeader();

			if ($this->getVar('comments-nav-top') == 1)
			{
				?>
				<div id="nav-top"><?php echo LayoutHelper::render('pagination', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?></div>
				<?php
			}
			?>
			<div id="comments-list" class="container-fluid comments-list">
				<?php
				$i = 0;

				foreach ($comments as $id => $comment)
				{
					?>
					<div class="<?php echo($i % 2 ? 'odd' : 'even'); ?>"
					     id="comment-item-<?php echo $id; ?>"><?php echo $comment; ?></div>
					<?php
					$i++;
				}
				?>
			</div>
			<?php
			if ($this->getVar('comments-nav-bottom') == 1)
			{
				?>
				<div id="nav-bottom"><?php echo LayoutHelper::render('pagination', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?></div>
				<?php
			}
			?>
			<div id="comments-list-footer"><?php echo $this->getFooter(); ?></div>
			<?php
		}
		else
		{
			// display single comment item (works when new comment is added)

			$comment = $this->getVar('comment-item');

			if (isset($comment))
			{
				$i  = $this->getVar('comment-modulo');
				$id = $this->getVar('comment-id');

				?>
				<div class="<?php echo($i % 2 ? 'odd' : 'even'); ?>"
				     id="comment-item-<?php echo $id; ?>"><?php echo $comment; ?></div>
				<?php
			}
			else
			{
				?>
				<div id="comments-list" class="container-fluid comments-list"></div>
				<?php
			}
		}
	}

	/*
	 *
	 * Display comments header and small buttons: rss and refresh
	 *
	 */
	function getHeader()
	{
		$object_id    = $this->getVar('comment-object_id');
		$object_group = $this->getVar('comment-object_group');

		$btnRSS     = '';
		$btnRefresh = '';

		if ($this->getVar('comments-refresh', 1) == 1)
		{
			$btnRefresh = '<a href="#" title="' . JText::_('BUTTON_REFRESH') . '" onclick="jcomments.showPage(' . $object_id . ',\'' . $object_group . '\',0);return false;"><span aria-hidden="true" class="icon-loop icon-fw"></span></a>';
		}

		if ($this->getVar('comments-rss') == 1)
		{
			$link = $this->getVar('rssurl');
			if (!empty($link))
			{
				$btnRSS = '<a href="' . $link . '" title="' . JText::_('BUTTON_RSS') . '" target="_blank">
								<span aria-hidden="true" class="icon-rss icon-fw"></span>
						   </a>';
			}
		}
		?>
		<h6><?php echo JText::_('COMMENTS_LIST_HEADER'); ?>
			&nbsp;&nbsp;<?php echo $btnRSS; ?><?php echo $btnRefresh; ?></h6>
		<?php
	}

	/*
	 *
	 * Display RSS feed and/or Refresh buttons after comments list
	 *
	 */
	function getFooter()
	{
		$footer = '';

		$object_id    = $this->getVar('comment-object_id');
		$object_group = $this->getVar('comment-object_group');

		$lines = array();

		if ($this->getVar('comments-refresh', 1) == 1)
		{
			$lines[] = '<a href="#" title="' . JText::_('BUTTON_REFRESH') . '" onclick="jcomments.showPage(' . $object_id . ',\'' . $object_group . '\',0);return false;"><span aria-hidden="true" class="icon-loop icon-fw"></span> ' . JText::_('BUTTON_REFRESH') . '</a>';
		}

		if ($this->getVar('comments-rss', 1) == 1)
		{
			$link = $this->getVar('rssurl');
			if (!empty($link))
			{
				$lines[] = '<a href="' . $link . '" title="' . JText::_('BUTTON_RSS') . '" target="_blank">
								<span aria-hidden="true" class="icon-rss icon-fw"></span> ' . JText::_('BUTTON_RSS') . '
				</a>';
			}
		}

		if ($this->getVar('comments-can-subscribe', 0) == 1)
		{
			$isSubscribed = $this->getVar('comments-user-subscribed', 0);

			$text = $isSubscribed ? JText::_('BUTTON_UNSUBSCRIBE') : JText::_('BUTTON_SUBSCRIBE');
			$func = $isSubscribed ? 'unsubscribe' : 'subscribe';

			$lines[] = '<a id="comments-subscription" href="#" title="' . $text . '" onclick="jcomments.' . $func . '(' . $object_id . ',\'' . $object_group . '\');return false;"><span aria-hidden="true" class="icon-mail icon-fw"></span> ' . $text . '</a>';
		}

		if (count($lines))
		{
			$footer = implode('<br />', $lines);
		}

		return $footer;
	}
}
