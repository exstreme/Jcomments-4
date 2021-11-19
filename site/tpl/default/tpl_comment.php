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
 * Comment item template. Results of rendering used in tpl_list.php
 *
 * @since  3.0
 */
class jtt_tpl_comment extends JoomlaTuneTemplate
{
	public function render()
	{
		$comment = $this->getVar('comment');

		if (isset($comment))
		{
			if ($this->getVar('get_comment_vote', 0) == 1)
			{
				// Return comment vote
				echo LayoutHelper::render('comment-vote-value', $this, JPATH_ROOT . '/components/com_jcomments/layouts/');
			}
			elseif ($this->getVar('get_comment_body', 0) == 1)
			{
				// Return only comment body (for example after quick edit)
				echo $comment->comment;
			}
			else
			{
				// Return all comment item
				?>
				<div class="flex-fill border rounded rbox">
					<?php
					$commentBoxIndentStyle = ($this->getVar('avatar') == 1) ? ' avatar-indent' : '';

					if ($this->getVar('avatar') == 1)
					{
						?>
						<div class="comment-avatar"><?php echo $comment->avatar; ?></div>
						<?php
					}
					?>
					<?php echo LayoutHelper::render('comment-info', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?>

					<div class="comment-box">
						<div class="comment-body text-break" id="comment-body-<?php echo $comment->id; ?>">
							<?php echo $comment->comment; ?>
						</div>
					</div>

					<?php echo LayoutHelper::render('comment-buttons', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?>
					<?php echo LayoutHelper::render('comment-admin-panel', $this, JPATH_ROOT . '/components/com_jcomments/layouts/'); ?>
				</div>
				<?php
			}
		}
	}
}
