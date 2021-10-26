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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('jquery')
	->useScript('keepalive')
	->useScript('form.validate');

HTMLHelper::_('bootstrap.tooltip', '.hasTip');
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			$('a.cmd-delete-report').click(function (e) {
				e.preventDefault();

				var id = $(this).data('report-id');
				var row = $(this).closest('tr');

				if (id) {
					$.post('index.php?option=com_jcomments&task=comment.deleteReportAjax&tmpl=component', {id: id})
					.done(function (result) {
						if (result === '0') {
							document.location.reload();
						} else if (result > 0) {
							if (row) {
								row.remove();
							}
						}
					});
				}
			});
		});
	})(jQuery);
</script>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=comment&layout=edit&id=' . (int) $this->item->id); ?>"
	  method="post" name="adminForm" id="item-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('A_COMMENT_EDIT')); ?>
		<div class="row">
			<div class="col-lg-9">
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('title'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('title'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('homepage'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('homepage'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('email'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('email'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('comment'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('comment'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('date'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('date'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('id'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('id'); ?>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('published'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('published'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('deleted'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('deleted'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('userid'); ?>
					</div>
					<div class="controls">
						<?php if ($this->item->userid): ?>
							<?php echo $this->form->getInput('userid'); ?>
						<?php else: ?>
							<?php echo $this->form->getInput('name'); ?>
						<?php endif; ?>
					</div>
				</div>

				<?php echo $this->form->renderField('lang'); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'reports', Text::_('A_REPORTS_LIST')); ?>
		<table class="adminlist table">
			<thead>
			<tr>
				<td class="w-1 text-center">#</td>
				<th scope="col"><?php echo Text::_('A_REPORTS_REPORT_REASON'); ?></th>
				<th scope="col" class="w-20 d-none d-md-table-cell"><?php echo Text::_('A_REPORTS_REPORT_NAME'); ?></th>
				<th scope="col" class="w-20 d-none d-md-table-cell"><?php echo Text::_('A_BLACKLIST_IP'); ?></th>
				<th scope="col" class="w-20 d-none d-md-table-cell"><?php echo Text::_('A_REPORTS_REPORT_DATE'); ?></th>
				<td class="w-1 text-center"></td>
			</tr>
			</thead>
			<tbody>
			<?php
			$i = 1;

			foreach ($this->reports as $report): ?>
				<tr>
					<td><?php echo $i; ?></td>
					<td><?php echo $report->reason; ?></td>
					<td><?php echo $report->name; ?></td>
					<td><?php echo $report->ip; ?></td>
					<td>
						<?php echo HTMLHelper::_('date', $report->date, 'Y-m-d H:i:s'); ?>
					</td>
					<td>
						<a title="<?php echo Text::_('A_REPORTS_REMOVE_REPORT'); ?>" href="#"
						   data-report-id="<?php echo $report->id; ?>" class="cmd-delete-report hasTip">
							<i class="icon-remove"></i>
						</a>
					</td>
				</tr>
				<?php
				$i++;
			endforeach; ?>
			</tbody>
		</table>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
