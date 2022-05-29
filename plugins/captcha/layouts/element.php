<?php
/**
 * Kcaptcha image plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2022 Vladimir Globulopolis. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url         https://xn--80aeqbhthr9b.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var object $displayData */
$params = $displayData->params;
$url = Route::_('index.php?option=com_ajax&plugin=kcaptcha&group=captcha&format=raw');

\Joomla\CMS\HTML\HTMLHelper::_('bootstrap.tooltip', '#cmd-captcha-help');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->addInlineStyle('
	.captcha-container img.captcha {
		padding: 0;
		margin: 0 0 3px 0;
		border: 1px solid #ccc;
	}
	
	.captcha-container span.captcha {
		color: #777;
		cursor: pointer;
		display: inline-block;
	}'
);
?>
<div class="captcha-container">
	<img class="captcha" id="<?php echo $displayData->id; ?>_image" src="<?php echo $url; ?>"
		 width="<?php echo $params->get('width'); ?>" height="<?php echo $params->get('height'); ?>"
		 alt="<?php echo Text::_('FORM_CAPTCHA'); ?>">
	<br>
	<div class="captcha-reload">
		<a href="javascript:void(0);" id="cmd-captcha-reload">
			<span aria-hidden="true" class="icon-loop icon-fw"></span><?php echo Text::_('FORM_CAPTCHA_REFRESH'); ?></a>
		<a href="javascript:void(0);" id="cmd-captcha-help" data-bs-placement="right"
		   title="<?php echo Text::_('FORM_CAPTCHA_DESC'); ?>" aria-describedby="tip-<?php echo $displayData->id; ?>">
			<span aria-hidden="true" class="icon-help icon-fw"></span>
		</a>
	</div>
	<input class="form-control form-control captcha" id="<?php echo $displayData->id; ?>" type="text" name="<?php echo $displayData->name; ?>"
		   value="" size="5" autocomplete="off" required style="width: <?php echo $params->get('width'); ?>px;">
</div>
