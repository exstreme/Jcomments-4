<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;
?>
<div class="control-group">
	<div class="control-label">
		<?php echo $this->form->getLabel('pattern'); ?>
	</div>
	<div class="controls">
		<?php echo $this->form->getInput('pattern'); ?>
	</div>
	<div class="controls">
		<?php echo JHtml::_('custombbcodes.sample', '\[highlight\=([a-zA-Z0-9].?)\](*.?)\[\/highlight\]'); ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo $this->form->getLabel('replacement_html'); ?>
	</div>
	<div class="controls">
		<?php echo $this->form->getInput('replacement_html'); ?>
	</div>
	<div class="controls">
		<?php echo JHtml::_('custombbcodes.sample', '&lt;span style="color: ${1};"&gt;${2}&lt;/span&gt;'); ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo $this->form->getLabel('replacement_text'); ?>
	</div>
	<div class="controls">
		<?php echo $this->form->getInput('replacement_text'); ?>
	</div>
	<div class="controls">
		<?php echo JHtml::_('custombbcodes.sample', '${2}'); ?>
	</div>
</div>
