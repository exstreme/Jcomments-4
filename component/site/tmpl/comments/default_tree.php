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

use Joomla\CMS\Layout\LayoutHelper;

/** @var Joomla\Component\Jcomments\Site\View\Comments\HtmlView $this */

if (!$this->items)
{
	return;
}

$currentLevel = 0;
$i            = 0;
$j            = 0;
?>
<div class="list-unstyled comments-list-parent">

<?php foreach ($this->items as $id => $comment):
	if ($currentLevel < $comment->level)
	{
		?>

	</div>
	<div class="ms-3 comments-list-child" id="comments-list-<?php echo $comment->parent; ?>">

		<?php
	}
	else
	{
		if ($currentLevel >= $comment->level)
		{
			$j = $currentLevel - $comment->level;
		}
		elseif ($comment->level > 0 && $i == $this->totalComments - 1)
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
	} ?>

	<div class="comment-container <?php echo $i % 2 ? 'odd' : 'even'; ?>" id="comment-item-<?php echo $id; ?>">
	<?php
		echo LayoutHelper::render('comment', array('comment' => $comment, 'params' => $this->params), '', array('component' => 'com_jcomments'));

	if ($comment->children == 0)
	{
		?>

	</div>
	<?php }

	if ($comment->level > 0 && $i == $this->totalComments - 1)
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

	$i++;
	$currentLevel = $comment->level;
	?>
<?php endforeach; ?>

</div>
