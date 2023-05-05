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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var Joomla\Component\Jcomments\Administrator\View\User\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate')
	->useScript('jquery');

$labels = $this->form->getFieldsets()['jlabels'];
$url = empty($this->item->id) ? '' : '&id=' . (int) $this->item->id;
?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#jform_id_id').on('change', function(e) {
			const input = $(this); // Hidden input with ID

			$.get(
				'<?php echo Route::_('index.php?option=com_jcomments&task=user.exists&' . Session::getFormToken() . '=1&id=', false); ?>' + input.val(),
				function(response) {
					if (response.success === false) {
						Joomla.renderMessages({'error': [response.message]}, '.uid');
						input.val('');
					}
				}
			);

			return true;
		});
	});
</script>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=user&layout=edit' . $url); ?>"
	  method="post" name="adminForm" id="item-form" class="form-validate">
	<div class="main-card">
		<div class="row col-12">
			<div class="mx-3 mt-3 uid">
				<?php echo $this->form->renderField('id'); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'labels', Text::_('COM_JCOMMENTS_USERS_LABELS')); ?>

			<div class="row">
				<div class="col-12">
					<?php if (!empty($labels->description)) : ?>
						<div class="tab-description alert alert-info mt-0">
							<span class="icon-info-circle" aria-hidden="true"></span>
							<span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
							<?php echo Text::_($labels->description); ?>
						</div>
					<?php endif; ?>
					<?php echo $this->form->renderFieldset('jlabels'); ?>
				</div>
			</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'misc', Text::_('JOPTIONS')); ?>

			<div class="row">
				<div class="col-12">
					<?php echo $this->form->renderField('terms_of_use'); ?>
				</div>
			</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
