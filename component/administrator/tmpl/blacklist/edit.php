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

/** @var Joomla\Component\Jcomments\Administrator\View\Blacklist\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=blacklist&layout=edit&id=' . (int) $this->item->id); ?>"
	  method="post" name="adminForm" id="item-form" class="form-validate">
	<fieldset class="adminform">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="form-grid">
						<?php echo $this->form->renderFieldset('edit'); ?>
					</div>
				</div>
			</div>

			<input type="hidden" name="task" value=""/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</fieldset>
</form>
