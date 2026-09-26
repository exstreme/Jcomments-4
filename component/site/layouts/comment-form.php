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

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Language\Text;

/** @var JoomlaTuneTemplate $displayData */
$objectID    = $displayData->getVar('comment-object_id');
$objectGroup = $displayData->getVar('comment-object_group');

if ($displayData->getVar('comments-form-link', 0) == 1): ?>
	<div id="comments-form-link" class="d-grid gap-2">
		<a id="addcomments" href="#addcomments" class="btn btn-outline-primary showform"
		   data-object_id="<?php echo $objectID; ?>" data-object_group="<?php echo $objectGroup; ?>">
			<span class="icon-comment icon-fw"></span><?php echo Text::_('FORM_HEADER'); ?>
		</a>
	</div>
<?php else: ?>
	<div class="h6"><?php echo Text::_('FORM_HEADER'); ?></div>

	<?php if ($displayData->getVar('comments-form-policy', 0) == 1): ?>
		<div class="border rounded comments-policy"><?php echo $displayData->getVar('comments-policy'); ?></div>
	<?php endif; ?>

	<?php
	// Trigger onJCommentsFormBeforeDisplay event
	echo $displayData->getVar('comments-html-before-form');
	?>

	<?php
	// Editor configuration. Stored as inert JSON and applied by jcomments.initForm(), no inline script required.
	$formConfig = array('buttons' => array(), 'smiles' => array(), 'smilesUrl' => '', 'counter' => null);

	if ($displayData->getVar('comments-form-bbcode', 0) == 1)
	{
		$bbcodes = array(
			'b'     => array(0 => Text::_('FORM_BBCODE_B'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT')),
			'i'     => array(0 => Text::_('FORM_BBCODE_I'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT')),
			'u'     => array(0 => Text::_('FORM_BBCODE_U'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT')),
			's'     => array(0 => Text::_('FORM_BBCODE_S'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT')),
			'img'   => array(0 => Text::_('FORM_BBCODE_IMG'), 1 => Text::_('BBCODE_HINT_ENTER_FULL_URL_TO_THE_IMAGE')),
			'url'   => array(0 => Text::_('FORM_BBCODE_URL'), 1 => Text::_('BBCODE_HINT_ENTER_FULL_URL')),
			'hide'  => array(0 => Text::_('FORM_BBCODE_HIDE'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT_TO_HIDE_IT_FROM_UNREGISTERED')),
			'quote' => array(0 => Text::_('FORM_BBCODE_QUOTE'), 1 => Text::_('BBCODE_HINT_ENTER_TEXT_TO_QUOTE')),
			'list'  => array(0 => Text::_('FORM_BBCODE_LIST'), 1 => Text::_('BBCODE_HINT_ENTER_LIST_ITEM_TEXT'))
		);

		foreach ($bbcodes as $k => $v)
		{
			if ($displayData->getVar('comments-form-bbcode-' . $k, 0) == 1)
			{
				$formConfig['buttons'][] = array($k, trim($v[0]), trim($v[1]));
			}
		}
	}

	$customBBCodes = $displayData->getVar('comments-form-custombbcodes');

	if (!empty($customBBCodes))
	{
		foreach ($customBBCodes as $code)
		{
			if ($code->button_enabled)
			{
				$formConfig['buttons'][] = array(
					'custombbcode' . $code->id,
					trim($code->button_title),
					empty($code->button_prompt) ? Text::_('BBCODE_HINT_ENTER_TEXT') : Text::_($code->button_prompt),
					$code->button_open_tag,
					$code->button_close_tag,
					$code->button_css,
					$code->button_image
				);
			}
		}
	}

	$smiles = $displayData->getVar('comment-form-smiles');

	if (!empty($smiles))
	{
		$formConfig['smilesUrl'] = $displayData->getVar('smilesurl');

		foreach ($smiles as $code => $icon)
		{
			$formConfig['smiles'][] = array(trim($code), trim($icon));
		}
	}

	if ($displayData->getVar('comments-form-showlength-counter', 0) == 1)
	{
		$formConfig['counter'] = array(
			(int) $displayData->getVar('comment-maxlength'),
			Text::_('FORM_CHARSLEFT_PREFIX'),
			Text::_('FORM_CHARSLEFT_SUFFIX'),
			'counter'
		);
	}

	$formConfig = htmlspecialchars(
		json_encode($formConfig, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
		ENT_QUOTES,
		'UTF-8'
	);
	?>
	<a id="addcomments" href="#addcomments"></a>
	<form class="d-grid gap-2 validate" id="comments-form" name="comments-form" action="javascript:void();" autocomplete="off"
		  data-config="<?php echo $formConfig; ?>">
		<?php
		// Trigger onJCommentsFormPrepend event
		$displayData->getFormFields($displayData->getVar('comments-form-html-prepend'));

		if ($displayData->getVar('comments-form-user-name', 1) == 1):
			$required = $displayData->getVar('comments-form-user-name-required', 1);
			$requiredInput = !$required ? '' : ' required';
			$text = !$required ? Text::_('FORM_NAME') : Text::_('FORM_NAME_REQUIRED');
			?>
			<div class="row align-items-center">
				<div class="col-5">
					<input class="form-control form-control-sm" id="comments-form-name" type="text" name="name" value=""
						   maxlength="<?php echo $displayData->getVar('comment-name-maxlength'); ?>" size="22"
						   <?php echo $requiredInput; ?>/>
				</div>
				<div class="col-auto">
					<label class="form-label" for="comments-form-name"><?php echo $text; ?></label>
				</div>
			</div>
		<?php endif;

		if ($displayData->getVar('comments-form-user-email', 1) == 1):
			$required = $displayData->getVar('comments-form-email-required', 1);
			$requiredInput = !$required ? '' : ' required';
			$text = !$required ? Text::_('FORM_EMAIL') : Text::_('FORM_EMAIL_REQUIRED');
			?>
			<div class="row align-items-center">
				<div class="col-5">
					<input class="form-control form-control-sm" id="comments-form-email" type="text" name="email" value=""
						   size="22"<?php echo $requiredInput; ?>/>
				</div>
				<div class="col-auto">
					<label class="form-label" for="comments-form-email"><?php echo $text; ?></label>
				</div>
			</div>
		<?php endif;

		if ($displayData->getVar('comments-form-user-homepage', 0) == 1):
			$required = $displayData->getVar('comments-form-homepage-required', 1);
			$requiredInput = !$required ? '' : ' required';
			$text = !$required ? Text::_('FORM_HOMEPAGE') : Text::_('FORM_HOMEPAGE_REQUIRED');
			?>
			<div class="row align-items-center">
				<div class="col-5">
					<input class="form-control form-control-sm" id="comments-form-homepage" type="text" name="homepage" value=""
						   size="22"<?php echo $requiredInput; ?>/>
				</div>
				<div class="col-auto">
					<label class="form-label" for="comments-form-homepage"><?php echo $text; ?></label>
				</div>
			</div>
		<?php endif;

		if ($displayData->getVar('comments-form-title', 0) == 1):
			$required = $displayData->getVar('comments-form-title-required', 1);
			$requiredInput = !$required ? '' : ' required';
			$text = !$required ? Text::_('FORM_TITLE') : Text::_('FORM_TITLE_REQUIRED');
			?>
			<div class="row align-items-center">
				<div class="col-5">
					<input class="form-control form-control-sm" id="comments-form-title" type="text" name="title" value=""
						   size="22"<?php echo $requiredInput; ?>/>
				</div>
				<div class="col-auto">
					<label class="form-label" for="comments-form-title"><?php echo $text; ?></label>
				</div>
			</div>
		<?php endif; ?>

		<label class="form-label visually-hidden" for="comments-form-comment"
			   aria-label="<?php echo Text::_('NOTIFICATION_COMMENT_TEXT'); ?>">
			<?php echo Text::_('NOTIFICATION_COMMENT_TEXT'); ?>
		</label>
		<textarea class="form-control" id="comments-form-comment" name="comment" cols="65" rows="8"
				  placeholder="<?php echo Text::_('NOTIFICATION_COMMENT_TEXT'); ?>..."></textarea>

		<?php if ($displayData->getVar('comments-form-subscribe', 0) == 1): ?>
			<div class="form-check">
				<input class="form-check-input" id="comments-form-subscribe" type="checkbox" name="subscribe" value="1"/>
				<label class="form-check-label" for="comments-form-subscribe"><?php echo Text::_('FORM_SUBSCRIBE'); ?></label><br/>
			</div>
		<?php endif;

		if ($displayData->getVar('var_show_checkbox_terms_of_use', 0) == 1): ?>
			<div class="form-check" id="checkbox_terms_of_use">
				<input class="form-check-input" id="show_checkbox_terms_of_use" type="checkbox" name="name_checkbox_terms_of_use"
					   value="1" required/>
				<label class="form-check-label" for="show_checkbox_terms_of_use"><?php echo $displayData->getVar('var_tos_text'); ?></label>
			</div>
		<?php endif;

		if ($displayData->getVar('comments-form-captcha', 0) == 1):
			$html = (!extension_loaded('gd') || !function_exists('imagecreatefrompng'))
					? '' : $displayData->getVar('comments-form-captcha-html');

			if ($html == 'kcaptcha')
			{
				$link = JCommentsFactory::getLink('captcha');
				?>
				<div class="captcha-container">
					<img class="captcha" id="comments-form-captcha-image" src="<?php echo $link; ?>" width="121"
						 height="60" alt="<?php echo Text::_('FORM_CAPTCHA'); ?>"/>
					<br/>
					<div class="captcha-reload">
						<a href="javascript:void(0);" id="cmd-captcha-reload">
							<span aria-hidden="true" class="icon-loop icon-fw"></span><?php echo Text::_('FORM_CAPTCHA_REFRESH'); ?>
						</a>
					</div>
					<input class="form-control form-control-sm captcha" id="comments-form-captcha" type="text"
						   name="captcha_refid" value="" size="5" autocomplete="off" required/>
				</div>
				<?php
			}
			elseif ($html == 'recaptcha')
			{
				$recaptcha = Captcha::getInstance($html, array('namespace' => 'jcomments'));
				echo $recaptcha->display('recaptcha', 'dynamic_recaptcha_1', 'g-recaptcha');
			}
			elseif ($html == 'recaptcha_invisible')
			{
				$recaptcha = Captcha::getInstance($html, array('namespace' => 'jcomments'));
				echo $recaptcha->display('recaptcha', 'dynamic_recaptcha_invisible_1', 'g-recaptcha');
			}
			elseif ($html == 'hcaptcha')
			{
				$hcaptcha = Captcha::getInstance($html, array('namespace' => 'jcomments'));
				echo $hcaptcha->display('hcaptcha', 'dynamic_hcaptcha_1', 'hcaptcha');
			}
            elseif ($html == 'turnstile')
			{
				$turnstile = Captcha::getInstance($html, array('namespace' => 'jcomments'));
				echo $turnstile->display('turnstile', 'dynamic_turnstile_1', 'turnstile');
			}
		endif; ?>

		<?php
		// Trigger onJCommentsFormAppend event
		$displayData->getFormFields($displayData->getVar('comments-form-html-append'));
		?>

		<input type="hidden" name="object_id" value="<?php echo $objectID; ?>"/>
		<input type="hidden" name="object_group" value="<?php echo $objectGroup; ?>"/>

		<div id="comments-form-buttons">
			<button class="btn btn-success btn-sm" id="comments-form-send" type="submit"
					title="<?php echo Text::_('FORM_SEND_HINT'); ?>"><?php echo Text::_('FORM_SEND'); ?></button>
			<button class="btn btn-secondary btn-sm" id="comments-form-reset" type="reset"
					title="<?php echo Text::_('JCLEAR'); ?>"><?php echo Text::_('JCLEAR'); ?></button>
			<button class="btn btn-secondary btn-sm" id="comments-form-cancel" type="button" style="display: none;"
					title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL'); ?></button>
			<div style="clear: both;"></div>
		</div>
		<br>

		<?php
		// Trigger onJCommentsFormAfterDisplay event
		echo $displayData->getVar('comments-html-after-form');
		?>
	</form>
<?php endif;
