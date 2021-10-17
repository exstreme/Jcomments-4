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

/** @var JCommentsViewSettings $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate')
	->useScript('bootstrap.modal');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task === 'settings.saveConfig') {
			window.location = '<?php echo Uri::base(); ?>index.php?option=com_jcomments&task=settings.saveConfig&format=raw';
		} else if (task === 'settings.restoreConfig') {
			Joomla.submitform(task, document.getElementById('adminRestoreConfig'));
		} else {
			Joomla.submitform(task, document.getElementById('item-form'));
		}

		return false;
	}
</script>
<form action="<?php echo Route::_('index.php?option=com_jcomments'); ?>" method="post" name="adminForm"
	  id="item-form" class="form-validate">
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'general', 'recall' => true, 'breakpoint' => 768]); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'common', Text::_('A_COMMON')); ?>
			<?php echo $this->loadTemplate('general'); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'layout', Text::_('A_LAYOUT')); ?>
			<?php echo $this->loadTemplate('layout'); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'restrictions', Text::_('A_RESTRICTIONS')); ?>
			<div class="row">
				<div class="col-lg-6">
					<fieldset class="options-form">
						<legend><?php echo Text::_('A_RESTRICTIONS'); ?></legend>
						<?php echo $this->form->renderFieldset('restrictions'); ?>
					</fieldset>
				</div>

				<div class="col-lg-6">
					<fieldset class="options-form">
						<legend><?php echo Text::_('A_SECURITY'); ?></legend>
						<?php echo $this->form->renderFieldset('security'); ?>
					</fieldset>
				</div>
			</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'censor', Text::_('A_CENSOR')); ?>
			<div class="row">
				<div class="col-lg-12">
					<fieldset class="options-form">
						<legend><?php echo Text::_('A_CENSOR'); ?></legend>
						<?php echo $this->form->renderFieldset('censor'); ?>
					</fieldset>
				</div>
			</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'messages', Text::_('A_MESSAGES')); ?>
			<div class="row">
				<div class="col-lg-12">
					<fieldset class="options-form">
						<legend><?php echo Text::_('A_MESSAGES'); ?></legend>
						<?php echo $this->form->renderFieldset('messages'); ?>
					</fieldset>
				</div>
			</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'rules', Text::_('JCONFIG_PERMISSIONS_LABEL')); ?>
		<?php
			// Due to unable save permissions via ajax with native Joomla functions it's better to use component settings in com_config.
			// But user can change permission in dropdown - just save the settings.
			echo $this->form->renderFieldset('rules');
		?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="return" value="<?php echo Factory::getApplication()->input->getBase64('return'); ?>">
	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php // File Modal
$fileModalData = array(
	'selector' => 'fileModal',
	'params'   => array(
		'title'      => Text::_('A_SETTINGS_BUTTON_RESTORECONFIG'),
		'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
		'height'     => '400px',
		'width'      => '800px',
		'bodyHeight' => 70,
		'modalWidth' => 80,
	),
	'body' => $this->loadTemplate('modal_file_body')
);
?>
<?php echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $fileModalData);
