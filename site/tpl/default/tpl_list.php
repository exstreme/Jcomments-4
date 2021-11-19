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
	public function render()
	{
		$comments = $this->getVar('comments-items');

		if (isset($comments))
		{
			// Display full comments list with navigation and other stuff
			echo LayoutHelper::render('comments-header', $this, JPATH_ROOT . '/components/com_jcomments/layouts/');

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
					<div class="d-flex <?php echo $i % 2 ? 'odd' : 'even'; ?>"
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
			<?php echo LayoutHelper::render('comments-footer', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?>
			<?php
		}
		else
		{
			// Display single comment item (works when new comment is added)
			$comment = $this->getVar('comment-item');

			if (isset($comment))
			{
				$i  = $this->getVar('comment-modulo');
				$id = $this->getVar('comment-id');

				?>
				<div class="d-flex <?php echo $i % 2 ? 'odd' : 'even'; ?>"
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
}
