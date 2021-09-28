<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 3.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
?>
<div class="row">
	<div class="col-lg-6">
		<fieldset class="options-form">
			<legend><?php echo Text::_('A_VIEW'); ?></legend>
			<?php foreach ($this->form->getFieldset('view') as $field) : ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $field->label; ?>
					</div>
					<div class="controls">
						<?php echo $field->input; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</fieldset>
	</div>
	<div class="col-lg-6">
		<fieldset class="options-form">
			<legend><?php echo Text::_('A_LIST_PARAMS'); ?></legend>
			<?php foreach ($this->form->getFieldset('list') as $field) : ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $field->label; ?>
					</div>
					<div class="controls">
						<?php echo $field->input; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset class="options-form">
			<legend><?php echo Text::_('A_FORM_PARAMS'); ?></legend>
			<?php foreach ($this->form->getFieldset('form') as $field) : ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $field->label; ?>
					</div>
					<div class="controls">
						<?php echo $field->input; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</fieldset>
	</div>
</div>
