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

/** @var Joomla\Component\Jcomments\Administrator\View\Comments\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('jcomments.objects')
	->useScript('keepalive');
?>
<div class="main-card p-3">
	<form action="<?php echo Route::_('index.php?option=com_jcomments&task=objects.refresh', false); ?>"
		  name="objectsUpdateForm" id="objectsUpdateForm" autocomplete="off">
		<div class="progress" style="height: 25px;">
			<div class="progress-bar progress-bar-striped bg-success progress-bar-animated" role="progressbar"
				 style="width: 0;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
		</div>
		<ul class="list-group list-group-flush log"></ul>
		<?php echo HTMLHelper::_('form.token'); ?>
		<input type="hidden" name="step" id="step" value="1">
		<input type="hidden" name="finished" id="finished" value="0">
		<div class="text-center">
			<button type="submit" class="btn btn-success mt-3 cmd-objects-update">
				<span class="icon-refresh" aria-hidden="true"></span>
				<?php echo Text::_('A_REFRESH_OBJECTS_BUTTON'); ?>
			</button>
			<a href="<?php echo Route::_('index.php?option=com_jcomments&view=comments'); ?>" role="button"
			   class="btn btn-secondary mt-3"><?php echo Text::_('JCLOSE'); ?></a>
		</div>
		<div class="alert alert-light" role="alert">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-exclamation-fill" viewBox="0 0 16 16">
				<path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
			</svg>
			<?php echo Text::_('A_REFRESH_OBJECTS_INFO_HELP'); ?>
		</div>
	</form>
</div>
