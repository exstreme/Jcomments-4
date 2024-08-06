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
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;

/** @var Joomla\Component\Jcomments\Site\View\Form\HtmlView $this */

$input = Factory::getApplication()->input;

$wa = $this->document->getWebAssetManager();
$wa->useStyle('jcomments.style')
	->useScript('form.validate')
	->addInlineScript(
		"const report_loader = parent.document.querySelector('.report-loader');
		if (report_loader) {
			report_loader.classList.add('d-none');
		}"
	);
?>
<div class="container-fluid mt-2 form-layout">
	<?php if ($input->getString('tmpl') != 'component'): ?>
		<div class="h6"><?php echo Text::_('REPORT_TO_ADMINISTRATOR'); ?></div>
	<?php endif; ?>

	<form action="<?php echo Route::_('index.php?option=com_jcomments&view=form&tmpl=component&layout=report'); ?>" method="post"
		  class="d-grid gap-2 form-validate" id="report-form" name="report-form" autocomplete="off">
		<?php if ($this->form->getInput('name') > ''): ?>
		<div class="row align-items-center">
			<div class="col-6">
				<?php echo $this->form->getInput('name'); ?>
			</div>
			<div class="col-auto">
				<?php echo $this->form->getLabel('name'); ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('email') > ''): ?>
		<div class="row align-items-center">
			<div class="col-6">
				<?php echo $this->form->getInput('email'); ?>
			</div>
			<div class="col-auto">
				<?php echo $this->form->getLabel('email'); ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('reason') > ''): ?>
		<div class="row align-items-center">
			<div class="col-6">
				<?php echo $this->form->getInput('reason'); ?>
			</div>
			<div class="col-auto">
				<?php echo $this->form->getLabel('reason'); ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (empty($this->form->getInput('name'))
			&& empty($this->form->getInput('email'))
			&& empty($this->form->getInput('reason'))):
		?>

			<?php echo JcommentsComponentHelper::renderMessage(Text::_('REPORT_NOTE'), 'warning'); ?>

		<?php endif; ?>

		<?php echo $this->form->getInput('report_captcha'); ?>
		<?php echo $this->form->getInput('comment_id'); ?>
		<input type="hidden" name="return" value="<?php echo $input->getBase64('return'); ?>">

		<input type="hidden" name="task" value="comment.report">
		<?php echo HTMLHelper::_('form.token'); ?>

		<div class="start-0 btn-container">
			<button class="report-form-send btn btn-danger" type="submit">
				<span class="icon-apply" aria-hidden="true"></span> <?php echo Text::_('JSUBMIT'); ?>
			</button>
			<?php if ($input->getString('tmpl') == 'component'): ?>
				<button class="report-form-cancel btn btn-secondary" type="button"
						onclick="parent.jQuery('#reportModal').modal('hide');">
					<span class="icon-cancel" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?></button>
			<?php endif; ?>
		</div>
	</form>
</div>
