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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\String\StringHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('multiselect')
	->useScript('table.columns');

$user           = Factory::getApplication()->getIdentity();
$userId         = $user->get('id');
$listOrder      = $this->escape($this->state->get('list.ordering'));
$listDirection  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=comments'); ?>" method="post" name="adminForm"
      id="adminForm">
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
							<?php echo Text::_('A_SUBMENU_COMMENTS'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-5 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'jc.published', $listDirection, $listOrder); ?>
							</th>
							<th scope="col">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_COMMENT_TITLE', 'jc.title', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'jc.name', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_BLACKLIST_IP', 'jc.ip', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_COMPONENT', 'jc.object_group', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_COMMENT_OBJECT_TITLE', 'jo.title', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_COMMENT_DATE', 'jc.date', $listDirection, $listOrder, null, 'asc', 'A_COMMENT_DATE', 'icon-sort'); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'jc.lang', $listDirection, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'jc.id', $listDirection, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item) :
							$canEdit = $user->authorise('core.edit', 'com_jcomments');
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
							$canChange = $user->authorise('core.edit.state', 'com_jcomments') && $canCheckin;

							$title = $item->title;

							if (empty($title))
							{
								if ($this->state->get('config.comment_title', 0) == 1 && !empty($item->object_title))
								{
									$title = Text::_('A_COMMENT_TITLE_RE') . ' ' . $item->object_title;
								}
								else
								{
									$title = JCommentsText::cleanText(strip_tags($item->comment));
									$title = StringHelper::substr($title, 0, 200);
								}
							}
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->id); ?>
								</td>
								<td class="small text-center">
									<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'comments.', $canChange); ?>
								</td>
								<th scope="row" class="has-context">
									<div class="break-word">
										<?php if ($item->checked_out) : ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'comments.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit && $canCheckin) : ?>
											<a href="<?php echo Route::_('index.php?option=com_jcomments&task=comment.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($title); ?>">
												<?php echo $this->escape($title); ?></a>
										<?php else : ?>
											<?php echo $this->escape($title); ?>
										<?php endif; ?>
									</div>
									<?php if ($item->deleted): ?>
										<span class="badge bg-secondary" aria-hidden="true">
											<?php echo Text::_('A_COMMENTS_HAS_BEEN_MARKED_AS_DELETED'); ?>
										</span>
									<?php endif; ?>
								</th>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->name; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->ip; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->object_group; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php if (isset($item->object_link)) : ?>
										<a href="<?php echo $item->object_link; ?>"
										   title="<?php echo htmlspecialchars($item->object_title); ?>"
										   target="_blank"><?php echo $item->object_title; ?></a>
									<?php else: ?>
										<?php echo $item->object_title; ?>
									<?php endif; ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo HTMLHelper::_('date', $item->date, Text::_('DATE_FORMAT_LC6')); ?>
								</td>
								<td class="small d-none d-md-table-cell">
									<?php echo $item->language_title; ?>
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

		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
