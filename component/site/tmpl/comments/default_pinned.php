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

/** @var Joomla\Component\Jcomments\Site\View\Comments\RawView $this */

if (!$this->pinnedItems)
{
	return;
}

$i = 0;

if (count($this->pinnedItems) > 0): ?>
	<div class="list-unstyled shadow comments-list-pinned">

	<?php foreach ($this->pinnedItems as $comment): ?>
		<div class="comment-container <?php echo $i % 2 ? 'odd' : 'even'; ?>" id="p-comment-item-<?php echo $comment->id; ?>">
			<?php echo LayoutHelper::render(
				'comment',
				array('comment' => $comment, 'params' => $this->params, '_pinned' => true),
				'',
				array('component' => 'com_jcomments')
			); ?>
		</div>
		<?php $i++;
	endforeach; ?>

	</div>
<?php endif;
