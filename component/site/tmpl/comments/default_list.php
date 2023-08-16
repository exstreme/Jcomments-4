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

$i = 0;

if (($this->params->get('comments_pagination') == 'top'  || $this->params->get('comments_pagination') == 'both')
	&& $this->pagination->pagesTotal > 1): ?>
	<div class="w-100 pagination-top">
		<p class="counter float-end pt-3 pe-2">
			<?php echo $this->pagination->getPagesCounter(); ?>
		</p>
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
<?php endif; ?>

<div class="list-unstyled comments-list-parent">

	<?php foreach ($this->items as $id => $comment): ?>
		<div class="comment-container <?php echo $i % 2 ? 'odd' : 'even'; ?>" id="comment-item-<?php echo $id; ?>">
			<?php echo LayoutHelper::render(
				'comment',
				array('comment' => $comment, 'params' => $this->params),
				'',
				array('component' => 'com_jcomments')
			); ?>
		</div>
		<?php $i++;
	endforeach; ?>

</div>

<?php if (($this->params->get('comments_pagination') == 'bottom'  || $this->params->get('comments_pagination') == 'both')
	&& $this->pagination->pagesTotal > 1): ?>
	<div class="w-100 pagination-bottom">
		<p class="counter float-end pt-3 pe-2">
			<?php echo $this->pagination->getPagesCounter(); ?>
		</p>
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
<?php endif;
