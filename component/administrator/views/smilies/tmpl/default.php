<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.multiselect');

$user           = Factory::getApplication()->getIdentity();
$userId         = $user->get('id');
$listOrder      = $this->escape($this->state->get('list.ordering'));
$listDirection  = $this->escape($this->state->get('list.direction'));
$canOrder       = $user->authorise('core.edit.state', 'com_jcomments');
$saveOrder      = $listOrder == 'js.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_jcomments&task=smilies.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_jcomments&view=smilies'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else : ?>
					<table class="adminlist table">
						<caption class="visually-hidden">
							<?php echo Text::_('A_SUBMENU_SMILIES'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-1 text-center d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'js.ordering', $listDirection, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col" class="w-5 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'js.published', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_SMILIES_HEADING_NAME', 'js.name', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-1 d-md-table-cell">
								<?php echo Text::_('A_SMILIES_HEADING_CODE'); ?>
							</th>
							<th scope="col" class="w-5 text-center d-md-table-cell">
								<?php echo Text::_('A_SMILIES_HEADING_IMAGE'); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'js.id', $listDirection, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>"
							data-direction="<?php echo strtolower($listDirection); ?>" data-nested="false"<?php endif; ?>>
						<?php foreach ($this->items as $i => $item):
							$canEdit = $user->authorise('core.edit', 'com_jcomments');
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
							$canChange = $user->authorise('core.edit.state', 'com_jcomments') && $canCheckin;
							?>
							<tr class="row<?php echo $i % 2; ?>" data-item-id="<?php echo $item->id ?>"
							    data-draggable-group="0" data-parents="" data-level="0">
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->name); ?>
								</td>
								<td class="text-center d-none d-md-table-cell">
									<?php
									$iconClass = '';
									if (!$canChange)
									{
										$iconClass = ' inactive';
									}
									elseif (!$saveOrder)
									{
										$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
									}
									?>
									<span class="sortable-handler<?php echo $iconClass ?>">
										<span class="icon-ellipsis-v" aria-hidden="true"></span>
									</span>
									<?php if ($canChange && $saveOrder) : ?>
										<input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
									<?php endif; ?>
								</td>
								<td class="text-center">
									<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'smilies.', $canChange); ?>
								</td>
								<th scope="row" class="has-context">
									<div class="break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'smilies.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit) : ?>
											<a href="<?php echo Route::_('index.php?option=com_jcomments&task=smiley.edit&id=' . $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->name); ?>">
												<?php echo $this->escape($item->name); ?></a>
										<?php else : ?>
											<span><?php echo $this->escape($item->name); ?></span>
										<?php endif; ?>
									</div>
								</th>
								<td class="d-md-table-cell">
									<?php echo $item->code; ?>1
								</td>
								<td class="text-center hidden-phone">
									<?php echo HTMLHelper::image($this->liveSmiliesPath . $item->image, ''); ?>
								</td>
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
