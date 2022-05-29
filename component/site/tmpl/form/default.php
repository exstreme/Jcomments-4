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

/** @var $this Joomla\Component\Jcomments\Site\View\Form\HtmlView */

// Checking if user can edit comment.
if ($this->error > '')
{
	echo $this->error;

	return;
}

$wa = $this->document->getWebAssetManager();
$wa->useScript('form.validate');

$displayStyleForm = 'block;';
$formMargin = 'my-3';
?>
<?php if ($this->displayForm == false):
	$displayStyleForm = 'none;';
	$formMargin = 'mb-3';
	?>
	<div class="d-grid my-2">
		<a href="#addcomments" class="btn btn-outline-primary cmd-showform">
			<span class="icon-comment icon-fw"></span> <?php echo Text::_('FORM_HEADER'); ?>
		</a>
	</div>
<?php endif; ?>

<div class="form-layout <?php echo $formMargin; ?>" style="display: <?php echo $displayStyleForm; ?>">
	<h6><?php echo Text::_('FORM_HEADER'); ?></h6>

	<?php if ($this->acl->showPolicy() && $this->policy != ''): ?>
		<div class="mb-2 p-2 border rounded comments-policy"><?php echo $this->policy; ?></div>
	<?php endif; ?>

	<a id="addcomments" href="#addcomments"></a>

	<?php
		// Trigger onJCommentsFormBeforeDisplay event
		echo $this->event->jcommentsFormBeforeDisplay;
	?>

	<form action="<?php echo Route::_('index.php?option=com_jcomments'); ?>" method="post"
		  class="d-grid gap-2 validate" id="comments-form" name="comments-form" autocomplete="off">
		<?php
			// Trigger onJCommentsFormPrepend event
			echo $this->event->jcommentsFormPrepend;
		?>

		<?php if ($this->form->getInput('name') != ''): ?>
			<div class="row align-items-center">
				<div class="col-6">
					<?php echo $this->form->getInput('name'); ?>
				</div>
				<div class="col-auto">
					<?php echo $this->form->getLabel('name'); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('email') != ''): ?>
			<div class="row align-items-center">
				<div class="col-6">
					<?php echo $this->form->getInput('email'); ?>
				</div>
				<div class="col-auto">
					<?php echo $this->form->getLabel('email'); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('homepage') != ''): ?>
			<div class="row align-items-center">
				<div class="col-6">
					<?php echo $this->form->getInput('homepage'); ?>
				</div>
				<div class="col-auto">
					<?php echo $this->form->getLabel('homepage'); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('title') != ''): ?>
			<div class="row align-items-center">
				<div class="col-6">
					<?php echo $this->form->getInput('title'); ?>
				</div>
				<div class="col-auto">
					<?php echo $this->form->getLabel('title'); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php echo $this->form->renderField('comment'); ?>

		<?php if ($this->form->getInput('subscribe') != ''): ?>
			<div class="mb-1">
				<div class="form-check">
					<label class="form-check-label">
						<input type="checkbox" name="subscribe" class="form-check-input"
							   value="<?php echo $this->form->getValue('subscribe', '', 0); ?>"
							   <?php echo $this->form->getFieldAttribute('subscribe', 'checked', ''); ?>>
						<?php echo Text::_($this->form->getFieldAttribute('subscribe', 'label')); ?>
					</label>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($this->form->getInput('terms_of_use') != ''):
			$tosRequired = $this->form->getFieldAttribute('terms_of_use', 'required', false); ?>
			<div class="mb-1">
				<div class="form-check">
					<label class="form-check-label">
						<input type="checkbox" name="terms_of_use" class="form-check-input"
							   value="<?php echo $this->form->getValue('terms_of_use', '', 1); ?>"
							   <?php echo $tosRequired ? ' required' : ''; ?>>
						<?php echo $this->form->getFieldAttribute('terms_of_use', 'label'); ?> *
					</label>
				</div>
			</div>
		<?php endif; ?>

		<?php echo LayoutHelper::render('params', $this); ?>

		<?php echo $this->form->renderField('captcha'); ?>

		<?php
			// Trigger onJCommentsFormAppend event
			echo $this->event->jcommentsFormAppend;
		?>

		<?php echo $this->form->getInput('id'); ?>
		<input type="hidden" name="object_id" value="<?php echo $this->objectID; ?>">
		<input type="hidden" name="object_group" value="<?php echo $this->objectGroup; ?>">
		<input type="hidden" name="task" value="comment.save">
		<input type="hidden" name="return" value="<?php echo $this->return_page; ?>">
		<?php echo HTMLHelper::_('form.token'); ?>

		<div class="start-0">
			<button class="btn btn-success" id="comments-form-send" type="submit"
					title="<?php echo Text::_('FORM_SEND_HINT'); ?>"><?php echo Text::_('JSUBMIT'); ?></button>
			<button class="btn btn-secondary" id="comments-form-reset" type="reset"
					title="<?php echo Text::_('JCLEAR'); ?>"><?php echo Text::_('JCLEAR'); ?></button>
			<button class="btn btn-secondary" id="comments-form-cancel" type="button" style="display: none;"
					title="<?php echo Text::_('JCLEAR'); ?>"><?php echo Text::_('JCANCEL'); ?></button>
		</div>
	</form>

	<?php
		// Trigger onJCommentsFormAfterDisplay event
		echo $this->event->jcommentsFormAfterDisplay;
	?>
</div>
