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
$wa->useScript('keepalive')
	->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=custombbcode&layout=edit&id=' . (int) $this->item->id); ?>"
	  method="post" name="adminForm" id="item-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('A_CUSTOM_BBCODE_EDIT')); ?>
		<div class="row">
			<?php echo $this->loadTemplate('general'); ?>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'simple', Text::_('A_CUSTOM_BBCODE_SIMPLE')); ?>
		<div class="row">
			<div class="col-lg-12">
				<?php echo $this->form->renderField('simple_pattern'); ?>

				<?php echo $this->form->renderField('simple_replacement_html'); ?>

				<?php echo $this->form->renderField('simple_replacement_text'); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'advanced', Text::_('A_CUSTOM_BBCODE_ADVANCED')); ?>
		<div class="row">
			<div class="col-lg-12">
				<?php echo $this->form->renderFieldset('advanced'); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'button', Text::_('A_CUSTOM_BBCODE_BUTTON')); ?>
		<div class="row">
			<div class="col-lg-12">
				<?php echo $this->form->renderFieldset('button'); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('A_CUSTOM_BBCODE_PERMISSIONS')); ?>
		<div class="row">
			<div class="col-lg-12">
				<?php echo HTMLHelper::_('jcomments.usergroups', 'jform[button_acl]', $this->groups, true); ?>
			</div>
		</div>

		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
