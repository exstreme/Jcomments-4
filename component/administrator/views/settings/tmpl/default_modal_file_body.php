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
use Joomla\CMS\Utility\Utility;
?>
<div class="container-fluid">
	<div class="mt-2 p-2">
		<div class="row">
			<div class="col-md-8">
				<form method="post" action="<?php echo Route::_('index.php?option=com_jcomments&task=settings.restore'); ?>"
					  enctype="multipart/form-data" class="mb-4">
					<div class="input-group">
						<input type="file" name="form_upload_config" aria-labelledby="upload" class="form-control" required>
						<?php echo HTMLHelper::_('form.token'); ?>
						<button type="submit" class="btn btn-primary" id="upload"><?php echo Text::_('JTOOLBAR_UPLOAD'); ?></button>
					</div>
					<?php $maxSize = HTMLHelper::_('number.bytes', Utility::getMaxUploadSize()); ?>
					<span class="mt-2"><?php echo Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', '&#x200E;' . $maxSize); ?></span>
				</form>
			</div>
		</div>
	</div>
</div>
