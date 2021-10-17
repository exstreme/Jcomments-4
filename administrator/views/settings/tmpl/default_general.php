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

/** @var JCommentsViewSettings $this */

use Joomla\CMS\Language\Text;
?>
<div class="row">
	<div class="col-12">
		<fieldset id="fieldset-categories" class="options-form">
			<legend><?php echo Text::_('A_CATEGORIES'); ?></legend>
			<?php echo $this->form->renderFieldset('categories'); ?>
		</fieldset>
	</div>
	<div class="col-12">
		<fieldset id="fieldset-misc" class="options-form">
			<legend><?php echo Text::_('A_MISC'); ?></legend>
			<?php echo $this->form->renderFieldset('misc'); ?>
		</fieldset>
	</div>
	<div class="col-12">
		<fieldset id="fieldset-notify" class="options-form">
			<legend><?php echo Text::_('A_NOTIFICATIONS'); ?></legend>
			<?php echo $this->form->renderFieldset('notifications'); ?>
		</fieldset>
	</div>
	<div class="col-12">
		<fieldset id="fieldset-reports" class="options-form">
			<legend><?php echo Text::_('A_REPORTS'); ?></legend>
			<?php echo $this->form->renderFieldset('reports'); ?>
		</fieldset>
	</div>
</div>
