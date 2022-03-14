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
 * Threaded comments list template
 *
 */
class jtt_tpl_tree extends JoomlaTuneTemplate
{
	public function render()
	{
		$comments = $this->getVar('comments-items');

		if (isset($comments))
		{
			echo LayoutHelper::render('comments-header', $this, JPATH_ROOT . '/components/com_jcomments/layouts/');
			?>
			<div class="container-fluid comments-list" id="comments-list-0">
			<?php
			$count        = count($comments);
			$currentLevel = 0;
			$i            = 0;
			$j            = 0;

			foreach ($comments as $id => $comment)
			{
				if ($currentLevel < $comment->level)
				{
					?>
					</div>
					<div class="comments-list-child" id="comments-list-<?php echo $comment->parent; ?>">
					<?php
				}
				else
				{
					if ($currentLevel >= $comment->level)
					{
						$j = $currentLevel - $comment->level;
					}
					elseif ($comment->level > 0 && $i == $count - 1)
					{
						$j = $comment->level;
					}

					while ($j > 0)
					{
						?>
						</div>
						<?php
						$j--;
					}
				}
				?>
			<div class="comment <?php echo $i % 2 ? 'odd' : 'even'; ?>" id="comment-item-<?php echo $id; ?>">
				<?php
				echo $comment->html;

				if ($comment->children == 0)
				{
					?>
					</div>
					<?php
				}

				if ($comment->level > 0 && $i == $count - 1)
				{
					$j = $comment->level;
				}

				while ($j > 0)
				{
					?>
					</div>
					<?php $j--;
				}

				$i++;
				$currentLevel = $comment->level;
			}
			?>
			</div>
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
				<div class="<?php echo $i % 2 ? 'odd' : 'even'; ?>"
					 id="comment-item-<?php echo $id; ?>"><?php echo $comment; ?></div>
				<?php
			}
			else
			{
				?>
				<div class="container-fluid comments-list" id="comments-list-0"></div>
				<?php
			}
		}

	}
}
