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

HTMLHelper::_('behavior.multiselect');

$user           = Factory::getApplication()->getIdentity();
$userId         = $user->get('id');
$listOrder      = $this->escape($this->state->get('list.ordering'));
$listDirection  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=subscriptions'); ?>" method="post"
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
				<?php else : ?>
					<table class="adminlist table">
						<caption class="visually-hidden">
							<?php echo Text::_('A_SUBMENU_SUBSCRIPTIONS'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
							<tr>
								<td class="w-1 text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" class="w-5 text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'js.published', $listDirection, $listOrder); ?>
								</th>
								<th scope="col" class="d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'A_CUSTOM_BBCODE_NAME', 'js.name', $listDirection, $listOrder, null, 'asc', 'A_SUBSCRIPTION_NAME', 'icon-sort'); ?>
								</th>
								<th scope="col" class="w-20 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'A_SUBSCRIPTION_EMAIL', 'js.email', $listDirection, $listOrder); ?>
								</th>
								<th scope="col" class="w-5 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'A_COMPONENT', 'js.object_group', $listDirection, $listOrder); ?>
								</th>
								<th scope="col" class="w-20 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'A_COMMENT_OBJECT_TITLE', 'jo.title', $listDirection, $listOrder); ?>
								</th>
								<th scope="col" class="w-10 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'jc.lang', $listDirection, $listOrder); ?>
								</th>
								<th scope="col" class="w-5 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'js.id', $listDirection, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item):
							$canEdit = $user->authorise('core.edit', 'com_jcomments');
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
							$canChange = $user->authorise('core.edit.state', 'com_jcomments') && $canCheckin;
							$item->language = $item->lang;
						?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->id); ?>
								</td>
								<td class="small text-center">
									<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'subscriptions.', $canChange); ?>
								</td>
								<th scope="row" class="has-context">
									<div class="break-word">
										<?php if ($item->checked_out): ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'subscriptions.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit && $canCheckin): ?>
											<a href="<?php echo Route::_('index.php?option=com_jcomments&task=subscription.edit&id=' . (int) $item->id); ?>"
											   title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->name); ?>">
												<?php echo $this->escape($item->name); ?></a>
										<?php else : ?>
											<?php echo $this->escape($item->name); ?>
										<?php endif; ?>
									</div>
								</th>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->email; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->object_group; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php if (isset($item->object_link)): ?>
										<a href="<?php echo $item->object_link; ?>"
										   title="<?php echo htmlspecialchars($item->object_title); ?>"
										   target="_blank"><?php echo $item->object_title; ?></a>
									<?php else: ?>
										<?php echo $item->object_title; ?>
									<?php endif; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo LayoutHelper::render('joomla.content.language', $item); ?>
								</td>
								<td class="small d-none d-md-table-cell">
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

	<?php // Load the batch processing form.
	if ($user->authorise('core.create', 'com_jcomments')
		&& $user->authorise('core.edit', 'com_jcomments')
		&& $user->authorise('core.edit.state', 'com_jcomments')):
		echo HTMLHelper::_(
			'bootstrap.renderModal',
			'collapseModal',
			array(
				'title'  => Text::_('COM_JCOMMENTS_BATCH_OPTIONS'),
				'footer' => $this->loadTemplate('batch_footer'),
			),
			$this->loadTemplate('batch_body')
		);
	endif; ?>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
