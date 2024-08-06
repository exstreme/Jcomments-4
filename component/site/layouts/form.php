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
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsText;

extract($displayData);

$app = Factory::getApplication();

if ($canViewForm !== true || !$canComment)
{
	if (is_bool($canViewForm))
	{
		$message = JcommentsText::getMessagesBasedOnLanguage(
			$params->get('messages_fields'),
			'message_policy_whocancomment',
			$app->getLanguage()->getTag(),
			'JGLOBAL_AUTH_ACCESS_DENIED'
		);
		$message = $message != '' ? nl2br($message) : Text::_('ERROR_CANT_COMMENT');

		echo JcommentsComponentHelper::renderMessage($message, 'warning');
	}
	else
	{
		echo $canViewForm;
	}

	return;
}

if ($displayForm || !empty($item->comment_id) || $form->getValue('parent') > 0)
{
	// Display form, hide `show form` button
	$displayBtnForm = 'd-none';
	$displayFormClass = 'show visible';
}
else
{
	// Hide form, display `show form` button
	if ($app->input->getWord('view') != 'form')
	{
		$displayBtnForm = 'show visible';
		$displayFormClass = 'd-none';
	}
	else
	{
		$displayBtnForm = 'd-none';
	}
}

$quote = $app->input->getInt('quote', 0) > 0 ? '&quote=1' : '';

if (empty($item->comment_id) && (!$displayForm && !$form->getValue('parent'))): ?>
<div class="d-grid my-2 showform-btn-container">
	<a href="#" class="btn btn-primary cmd-showform <?php echo $displayBtnForm; ?>" id="addcomment">
		<span class="icon-comment icon-fw"></span> <?php echo Text::_('FORM_HEADER_ADD'); ?>
	</a>
</div>
<?php endif; ?>

<div class="form-layout form-comment-container my-2 p-1 <?php echo $displayFormClass; ?>">
	<div class="form-header h6">
		<?php if (empty($item->comment_id)):
			echo Text::_('FORM_HEADER_ADD');
		else:
			if ($app->input->getInt('quote') == 1):
				echo Text::_('FORM_HEADER_QUOTE');
			else:
				echo Text::_('FORM_HEADER_EDIT');
			endif;
		endif; ?>
	</div>

	<?php if ($item->policy != ''): ?>
		<div class="mb-2 alert alert-info comments-policy" role="alert"><?php echo $item->policy; ?></div>
	<?php endif; ?>

	<?php
	// Trigger onJCommentsFormBeforeDisplay event
	echo $item->event->jcommentsFormBeforeDisplay;
	?>

	<form action="<?php echo Route::_('index.php?option=com_jcomments&comment_id=' . $item->comment_id . $quote); ?>"
		  method="post" class="form-validate form-vertical" id="commentForm" name="commentForm" autocomplete="off">
		<?php
		// Trigger onJCommentsFormPrepend event
		echo $item->event->jcommentsFormPrepend;
		?>

		<fieldset>
			<?php if ($form->getInput('name') != ''): ?>
				<?php echo $form->renderField('name'); ?>
			<?php endif; ?>

			<?php if ($form->getInput('email') != ''): ?>
				<?php echo $form->renderField('email'); ?>
			<?php endif; ?>

			<?php if ($form->getInput('homepage') != ''): ?>
				<?php echo $form->renderField('homepage'); ?>
			<?php endif; ?>

			<?php if ($form->getInput('title') != ''): ?>
				<?php echo $form->renderField('title'); ?>
			<?php endif; ?>

			<?php echo $form->renderField('comment'); ?>
			<?php if ($params->get('show_commentlength')
				&& ($params->get('editor_type') == 'component' || $params->get('editor_type') == 'joomla' && $app->getConfig()->get('editor') == 'none')): ?>
				<div class="col-12 text-secondary small jce-counter d-none">

					<?php if ($form->getFieldAttribute('comment', 'maxlength', 0) > 0): ?>
						<?php echo Text::sprintf('FORM_CHARSLEFT', '<span class="chars">' . $form->getFieldAttribute('comment', 'maxlength', 0) . '</span>'); ?>
					<?php else: ?>
						<?php echo Text::sprintf('FORM_CHARSLEFT', Text::_('FORM_CHARSLEFT_NOLIMIT')); ?>
					<?php endif; ?>

				</div>
			<?php endif; ?>

			<?php echo $form->renderField('pinned'); ?>
			<?php echo $form->renderField('subscribe'); ?>

			<?php if ($form->getInput('terms_of_use') != ''): ?>
				<div class="mb-1">
					<div class="mb-2 alert alert-info comments-tos" role="alert"><?php echo $item->terms; ?></div>
					<div class="com-users-registration">
						<?php echo $form->renderField('terms_of_use'); ?>
					</div>
				</div>
				<?php
				$link = $form->getFieldAttribute('terms_of_use', 'data-url');

				if ($link > '')
				{
					echo HTMLHelper::_(
						'bootstrap.renderModal',
						'tosModal',
						array(
							'url'    => $link,
							'title'  => $form->getFieldAttribute('terms_of_use', 'data-label'),
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

			<?php echo LayoutHelper::render('params', $viewObject, '', array('component' => 'com_jcomments')); ?>

			<?php if ($item->captchaEnabled): ?>
				<?php echo $form->renderField('comment_captcha'); ?>
			<?php endif; ?>

			<?php
			// Trigger onJCommentsFormAppend event
			echo $item->event->jcommentsFormAppend;
			?>

			<?php echo $form->getInput('parent'); ?>
			<?php echo $form->getInput('userid'); ?>
			<?php echo $form->getInput('object_id'); ?>
			<?php echo $form->getInput('object_group'); ?>
			<input type="hidden" name="task" value="">
			<input type="hidden" name="return" value="<?php echo $returnPage; ?>">
		</fieldset>

		<div class="start-0 btn-container">
			<button class="btn btn-success" type="button" data-submit-task="comment.apply">
				<span class="icon-check" aria-hidden="true"></span> <?php echo Text::_('JSAVE'); ?>
			</button>

			<?php // Do not display button for new comment form
			if ($item->comment_id > 0): ?>
				<button class="btn btn-success" type="button" data-submit-task="comment.save">
					<span class="icon-check" aria-hidden="true"></span> <?php echo Text::_('JSAVEANDCLOSE'); ?>
				</button>
			<?php endif; ?>

			<button class="btn btn-light" type="button" data-submit-task="comment.preview">
				<span class="icon-eye" aria-hidden="true"></span> <?php echo Text::_('FORM_PREVIEW'); ?>
			</button>

			<button class="btn btn-danger <?php echo empty($item->comment_id) ? 'd-none' : ''; ?>"
					type="button" data-submit-task="comment.cancel"
					data-cancel="<?php echo $form->getValue('parent') > 0 ? 'hideEditForm' : 'hideAddForm'; ?>">
				<span class="icon-cancel" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
			</button>
		</div>
	</form>

	<?php
	// Trigger onJCommentsFormAfterDisplay event
	echo $item->event->jcommentsFormAfterDisplay;
	?>
</div>
