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

/** @var Joomla\Component\Jcomments\Administrator\View\Blacklists\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useStyle('jcomments.backend_style')
	->useScript('jquery')
	->useScript('kwood.plugin')
	->useScript('kwood.more')
	->useScript('table.columns')
	->useScript('multiselect');

$db        = Factory::getContainer()->get('DatabaseDriver');
$userId    = $this->user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			$('.read-more').more({length: 80, wordBreak: true});
		});
	})(jQuery);
</script>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=blacklists'); ?>" method="post"
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
							<?php echo Text::_('A_SUBMENU_BLACKLIST'); ?>,
							<span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
							<span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
						</caption>
						<thead>
						<tr>
							<td class="w-1 text-center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</td>
							<th scope="col" class="w-20 d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_BLACKLIST_IP', 'jb.ip', $listDirn, $listOrder, null, 'asc', 'A_BLACKLIST_IP', 'icon-sort'); ?>
							</th>
							<th scope="col">
								<?php echo Text::_('A_BLACKLIST_REASON'); ?>
							</th>
							<th scope="col" class="d-none d-md-table-cell">
								<?php echo Text::_('A_BLACKLIST_NOTES'); ?>
							</th>
							<th scope="col" class="w-10 d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'u.name', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED_DATE', 'jb.created', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'A_BLACKLIST_EXPIRE', 'jb.expire', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="w-5 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'jb.id', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item):
							$canEdit = $this->user->authorise('core.edit', 'com_jcomments');
							$canCheckin = $this->user->authorise('core.manage', 'com_checkin')
								|| $item->checked_out == $userId
								|| $item->checked_out == 0;
							$expireClass = '';
							$expireText = Text::_('JNO');

							if ($item->expire && $item->expire != $db->getNullDate())
							{
								$expireText = HTMLHelper::_('date', $item->expire, Text::_('DATE_FORMAT_LC6'));

								// Checking if ban is expired.
								if (new DateTime($item->expire) <= new DateTime('now'))
								{
									$expireClass = ' table-danger';
								}
							}
							?>
							<tr class="row<?php echo $i % 2; ?><?php echo $expireClass; ?>">
								<td class="text-center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->ip); ?>
								</td>
								<th scope="row" class="has-context">
									<div class="break-word">
										<?php if ($item->checked_out): ?>
											<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'blacklists.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit && $canCheckin): ?>
											<a href="<?php echo Route::_('index.php?option=com_jcomments&task=blacklist.edit&id=' . (int) $item->id); ?>"
											   title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->ip); ?>">
												<?php echo $this->escape($item->ip); ?></a>
										<?php else: ?>
											<?php echo $this->escape($item->ip); ?>
										<?php endif; ?>
									</div>
								</th>
								<td class="small d-md-table-cell">
									<span class="read-more"><?php echo $item->reason; ?></span>
								</td>
								<td class="small d-none d-md-table-cell">
									<span class="read-more"><?php echo $item->notes; ?></span>
								</td>
								<td class="d-md-table-cell">
									<?php echo $item->name; ?>
								</td>
								<td class="d-none d-md-table-cell">
									<?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC6')); ?>
								</td>
								<td class="d-none d-md-table-cell">
									<?php echo $expireText; ?>
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

		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
