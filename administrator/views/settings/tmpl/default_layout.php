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
	<div class="col-lg-6">
		<fieldset id="fieldset-view" class="options-form">
			<legend><?php echo Text::_('A_VIEW'); ?></legend>
			<?php echo $this->form->renderFieldset('view'); ?>
		</fieldset>
	</div>
	<div class="col-lg-6">
		<fieldset id="fieldset-list" class="options-form">
			<legend><?php echo Text::_('A_LIST_PARAMS'); ?></legend>
			<?php echo $this->form->renderFieldset('list'); ?>
		</fieldset>
	</div>
</div>
<div class="row">
	<div class="col-lg-6"></div>
	<div class="col-lg-6">
		<fieldset id="fieldset-form" class="options-form">
			<legend><?php echo Text::_('A_FORM_PARAMS'); ?></legend>
			<?php echo $this->form->renderFieldset('form'); ?>
		</fieldset>
	</div>
</div>
