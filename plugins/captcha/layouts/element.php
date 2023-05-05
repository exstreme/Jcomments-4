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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var object $displayData */
$params = $displayData->params;

\Joomla\CMS\HTML\HTMLHelper::_('bootstrap.tooltip', '#cmd-captcha-help');

?>
<div class="captcha-container">
	<span style="color: #777; display: inline-block;">
		<img class="captcha" id="<?php echo $displayData->id; ?>_image"
			 src="<?php echo Route::_('index.php?option=com_ajax&plugin=kcaptcha&group=captcha&format=raw'); ?>"
			 width="<?php echo $params->get('width'); ?>" height="<?php echo $params->get('height'); ?>"
			 alt="<?php echo Text::_('FORM_CAPTCHA'); ?>"
			 style="padding: 0; margin: 0 0 3px 0; border: 1px solid #ccc;">
	</span>
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
<?php
if (\Joomla\CMS\Factory::getApplication()->input->getWord('format', '') == 'raw'): ?>
<script type="text/javascript"><?php echo file_get_contents(JPATH_BASE . '/media/plg_captcha_kcaptcha/js/kcaptcha.min.js'); ?></script>
<?php endif;
