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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Utility\Utility;

/** @var Joomla\Component\Jcomments\Administrator\View\Settings\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive');
?>
<div class="main-card">
	<div class="row">
		<div class="col-12">
			<div class="container-fluid">
				<div class="mt-2 p-2">
					<div class="row">
						<div class="col-md-8">
							<a class="button-download btn btn-primary"
							   href="<?php echo Uri::base(); ?>index.php?option=com_jcomments&task=settings.saveConfig&format=raw">
								<?php echo Text::_('A_SETTINGS_BUTTON_SAVECONFIG'); ?>
							</a>
						</div>
					</div>
				</div>

				<div class="mt-2 p-2">
					<div class="row">
						<div class="col-md-8">
							<form method="post" action="<?php echo Route::_('index.php?option=com_jcomments&task=settings.restoreConfig'); ?>"
								  enctype="multipart/form-data" class="mb-4">
								<div class="input-group">
									<input type="file" name="form_upload_config" aria-labelledby="upload" class="form-control" required>
									<?php echo HTMLHelper::_('form.token'); ?>
									<button type="submit" class="btn btn-primary" id="upload">
										<?php echo Text::_('A_SETTINGS_BUTTON_RESTORECONFIG'); ?>
									</button>
								</div>
								<?php $maxSize = HTMLHelper::_('number.bytes', Utility::getMaxUploadSize()); ?>
								<span class="mt-2"><?php echo Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', '&#x200E;' . $maxSize); ?></span>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
