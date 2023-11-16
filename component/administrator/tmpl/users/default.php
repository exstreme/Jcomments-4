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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var Joomla\Component\Jcomments\Administrator\View\Users\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('multiselect');

$user          = Factory::getApplication()->getIdentity();
$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_jcomments&view=users'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

				<?php if (empty($this->items)): ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else: ?>
					<table class="adminlist table">
						<caption class="visually-hidden">
							<?php echo Text::_('COM_JCOMMENTS_USERS'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_COMMENT_NAME', 'u.name', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'ju.id', $listDirection, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item): ?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->id); ?>
								</td>
								<th scope="row" class="has-context">
									<div class="break-word">
										<?php if ($user->authorise('core.edit', 'com_jcomments')): ?>
											<a href="<?php echo Route::_('index.php?option=com_jcomments&task=user.edit&id=' . $item->id); ?>"
											   title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->username); ?>">
												<?php echo $this->escape($item->username); ?></a>
										<?php else : ?>
											<span><?php echo $this->escape($item->username); ?></span>
										<?php endif; ?>
									</div>
								</th>
								<td class="d-none d-md-table-cell">
									<?php echo (int) $item->id; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php echo $this->pagination->getListFooter(); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
