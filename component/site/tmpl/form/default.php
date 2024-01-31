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

/** @var Joomla\Component\Jcomments\Site\View\Form\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useStyle('jcomments.style')
	->useScript('keepalive')
	->useScript('form.validate')
	->useScript('jcomments.core')
	->useScript('bootstrap.collapse');

if ($this->displayForm || !empty($this->form->getValue('comment_id')) || $this->form->getValue('parent') > 0)
{
	// Display form, hide `show form` button
	$displayBtnForm = 'd-none';
	$displayForm = 'show visible';
}
else
{
	// Hide form, display `show form` button
	$displayBtnForm = 'show visible';
	$displayForm = 'd-none';
}

if (empty($this->form->getValue('comment_id')) && (!$this->displayForm && !$this->form->getValue('parent'))): ?>
<div class="d-grid my-2 showform-btn-container">
	<a href="#addcomment" class="btn btn-primary cmd-showform <?php echo $displayBtnForm; ?>" onclick="Jcomments.showAddForm();return false;">
		<span class="icon-comment icon-fw"></span> <?php echo Text::_('FORM_HEADER'); ?>
	</a>
</div>
<?php endif; ?>

<div class="form-layout my-2 p-1 <?php echo $displayForm; ?>" id="editForm">
	<div class="h6"><?php echo empty($this->form->getValue('comment_id')) ? Text::_('FORM_HEADER') : Text::_('FORM_HEADER_EDIT'); ?></div>

	<?php if ($this->policy != ''): ?>
		<div class="mb-2 alert alert-info comments-policy" role="alert"><?php echo $this->policy; ?></div>
	<?php endif; ?>

	<?php
		// Trigger onJCommentsFormBeforeDisplay event
		echo $this->event->jcommentsFormBeforeDisplay;
	?>

	<form action="<?php echo Route::_('index.php?option=com_jcomments'); ?>" method="post"
		  class="form-validate form-vertical" id="adminForm" name="adminForm" autocomplete="off">
		<?php
			// Trigger onJCommentsFormPrepend event
			echo $this->event->jcommentsFormPrepend;
		?>

		<fieldset>
			<?php if ($this->form->getInput('name') != ''): ?>
				<?php echo $this->form->renderField('name'); ?>
			<?php endif; ?>

			<?php if ($this->form->getInput('email') != ''): ?>
				<?php echo $this->form->renderField('email'); ?>
			<?php endif; ?>

			<?php if ($this->form->getInput('homepage') != ''): ?>
				<?php echo $this->form->renderField('homepage'); ?>
			<?php endif; ?>

			<?php if ($this->form->getInput('title') != ''): ?>
				<?php echo $this->form->renderField('title'); ?>
			<?php endif; ?>

			<?php echo $this->form->renderField('comment'); ?>
			<?php if ($this->params->get('show_commentlength') && $this->params->get('editor_type') == 'component'): ?>
				<div class="col-12 text-secondary small jce-counter">

					<?php if ($this->form->getFieldAttribute('comment', 'maxlength', '') > 0): ?>
						<?php echo Text::sprintf('FORM_CHARSLEFT', '<span class="chars">' . $this->form->getFieldAttribute('comment', 'maxlength', 0) . '</span>'); ?>
					<?php else: ?>
						<?php echo Text::sprintf('FORM_CHARSLEFT', Text::_('FORM_CHARSLEFT_NOLIMIT')); ?>
					<?php endif; ?>

				</div>
			<?php endif; ?>

			<?php echo $this->form->renderField('pinned'); ?>
			<?php echo $this->form->renderField('subscribe'); ?>

			<?php if ($this->form->getInput('terms_of_use') != ''): ?>
				<div class="mb-1">
					<div class="mb-2 alert alert-info comments-tos" role="alert"><?php echo $this->terms; ?></div>
					<div class="com-users-registration">
						<?php echo $this->form->renderField('terms_of_use'); ?>
					</div>
				</div>
				<?php
				$link = $this->form->getFieldAttribute('terms_of_use', 'data-url');

				if ($link > '')
				{
					echo HTMLHelper::_(
						'bootstrap.renderModal',
						'tosModal',
						array(
							'url'    => $link,
							'title'  => $this->form->getFieldAttribute('terms_of_use', 'data-label'),
							'height' => '100%',
							'width'  => '100%',
							'bodyHeight'  => 70,
							'modalWidth'  => 80,
							'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-hidden="true">'
								. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>'
						)
					);
				}
			endif; ?>

			<?php echo LayoutHelper::render('params', $this); ?>

			<?php if ($this->captchaEnabled): ?>
				<?php echo $this->form->renderField('comment_captcha'); ?>
			<?php endif; ?>

			<?php
				// Trigger onJCommentsFormAppend event
				echo $this->event->jcommentsFormAppend;
			?>

			<?php echo $this->form->getInput('comment_id'); ?>
			<?php echo $this->form->getInput('parent'); ?>
			<?php echo $this->form->getInput('userid'); ?>
			<input type="hidden" name="object_id" value="<?php echo $this->objectID; ?>">
			<input type="hidden" name="object_group" value="<?php echo $this->objectGroup; ?>">
			<input type="hidden" name="task" value="">
			<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>">
			<?php echo HTMLHelper::_('form.token'); ?>
		</fieldset>

		<div class="start-0 btn-container">
			<button class="btn btn-success" type="button" data-submit-task="comment.apply">
				<span class="icon-check" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?>
			</button>

			<?php if ($this->form->getValue('comment_id') > 0): ?>
			<button class="btn btn-success" type="button" data-submit-task="comment.save">
				<span class="icon-check" aria-hidden="true"></span> <?php echo Text::_('JSAVEANDCLOSE'); ?>
			</button>
			<?php endif; ?>

			<?php if ($this->params->get('editor_type') == 'component'): ?>
			<button class="btn btn-light" type="button" data-submit-task="comment.preview">
				<span class="icon-eye" aria-hidden="true"></span> <?php echo Text::_('FORM_PREVIEW'); ?>
			</button>
			<?php endif; ?>

			<?php if ($this->form->getValue('comment_id') > 0 || $this->params->get('form_show') != 1):
				$btnCancelEvent = $this->form->getValue('parent') > 0 ? 'hideEditForm' : 'hideAddForm';
				?>
				<button class="btn btn-danger" type="button" data-submit-task="comment.cancel"
						data-cancel="<?php echo $btnCancelEvent; ?>">
					<span class="icon-cancel" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
				</button>
			<?php endif; ?>
		</div>
	</form>

	<?php
		// Trigger onJCommentsFormAfterDisplay event
		echo $this->event->jcommentsFormAfterDisplay;
	?>
</div>
