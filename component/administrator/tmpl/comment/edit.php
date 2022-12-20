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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.multiselect');

/** @var Joomla\Component\Jcomments\Administrator\View\Comment\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('jquery')
	->useScript('keepalive')
	->useScript('form.validate');

Text::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
Text::script('JTOOLBAR_DELETE');
Text::script('ERROR');

$this->ignore_fieldsets = ['details'];
$this->useCoreUI = true;
?>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$('body').on('click', 'a.cmd-delete-report', function (e) {
			e.preventDefault();

			const row = $(this).closest('tr');
			const id = row.find('input:checkbox').val();

			if (id) {
				$.post('index.php?option=com_jcomments&task=comment.deleteReports&format=json',
					{'cid[]': id, 'id': <?php echo $this->form->getValue('id'); ?>, '<?php echo Session::getFormToken(); ?>': 1}
				).done(function (response) {
					response = JSON.parse(response);

					if (response.success) {
						if (row) {
							row.remove();
							$('.rep-items').text('(' + response.data.total + ')');
						}
					} else {
						Joomla.renderMessages({'error': [response.message]});
					}
				});
			}
		});

		$('.cmd-delete-reports').click(function (e) {
			e.preventDefault();

			if (parseInt(document.adminForm.boxchecked.value) === 0) {
				Joomla.renderMessages({'error': [Joomla.Text._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')]});
			} else {
				if (confirm(Joomla.Text._('JTOOLBAR_DELETE') + '?')) {
					const ids = $('#reportsTable tbody input:checkbox').serialize();

					$.post('index.php?option=com_jcomments&task=comment.deleteReports&format=json',
						ids + '&id=<?php echo $this->form->getValue('id'); ?>&list=1&<?php echo Session::getFormToken(); ?>=1'
					).done(function (response) {
						response = JSON.parse(response);

						if (response.success) {
							$('#reportsTable tbody tr').remove();
							$('#reportsTable tbody').append(response.data.html);

							if ($('input[name="checkall-toggle"]:checked').length) {
								$('input[name="checkall-toggle"]').trigger('click');
							}
						} else {
							Joomla.renderMessages({'error': [response.message]});
						}
					});
				}
			}
		});

		$('.cmd-update-reports').click(function (e) {
			e.preventDefault();

			$.get('index.php?option=com_jcomments&task=comment.getReports&format=json',
				'&id=<?php echo $this->form->getValue('id'); ?>&<?php echo Session::getFormToken(); ?>=1'
			).done(function (response) {
				response = JSON.parse(response);

				if (response.success) {
					$('#reportsTable tbody tr').remove();
					$('#reportsTable tbody').append(response.data.html);

					if ($('input[name="checkall-toggle"]:checked').length) {
						$('input[name="checkall-toggle"]').trigger('click');
					}
				} else {
					Joomla.renderMessages({'error': [response.message]});
				}
			});
		});
	});
</script>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=comment&layout=edit&id=' . (int) $this->item->id); ?>"
	  method="post" name="adminForm" id="adminForm" class="form-validate">
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
							<?php echo $this->form->getLabel('object_id'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('object_id'); ?>
						</div>
					</div>

					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('object_group'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('object_group'); ?>
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

		<?php echo LayoutHelper::render('joomla.edit.params', $this); ?>

		<?php $totalRep = count($this->reports);
		$repTabTitle  = Text::_('A_REPORTS_LIST');
		$repTabTitle .= count($this->reports) ? ' <span class="rep-items">(' . $totalRep . ')</span>' : '';
		echo HTMLHelper::_('uitab.addTab', 'myTab', 'reports', $repTabTitle); ?>

		<table class="table table-sm table-hover" id="reportsTable">
			<thead>
			<tr>
				<td class="w-1 text-center">
					<?php echo HTMLHelper::_('grid.checkall'); ?>
				</td>
				<td class="w-1 text-center d-none d-md-table-cell">#</td>
				<th scope="col"><?php echo Text::_('A_REPORTS_REPORT_REASON'); ?></th>
				<th scope="col" class="w-20 d-none d-md-table-cell"><?php echo Text::_('A_REPORTS_REPORT_NAME'); ?></th>
				<th scope="col" class="w-20 d-md-table-cell"><?php echo Text::_('A_BLACKLIST_IP'); ?></th>
				<th scope="col" class="w-20 d-md-table-cell"><?php echo Text::_('A_REPORTS_REPORT_DATE'); ?></th>
				<td class="w-1 text-center"></td>
			</tr>
			</thead>
			<tbody>
			<?php echo LayoutHelper::render('reports-list', array('reports' => $this->reports)); ?>
			</tbody>
		</table>

		<button type="button" class="cmd-update-reports btn btn-success">
			<span class="icon-refresh" aria-hidden="true"></span>
		</button>
		<button type="button" class="cmd-delete-reports btn btn-danger">
			<span class="icon-remove" aria-hidden="true"></span> <?php echo Text::_('A_REPORTS_REMOVE_REPORTS'); ?>
		</button>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
