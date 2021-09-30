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
<?php foreach ($this->form->getFieldset('messages') as $field) : ?>
	<div class="control-group">
		<div class="control-label">
			<?php echo $field->label; ?>
		</div>
		<div class="controls">
			<?php echo $field->input; ?>
		</div>
	</div>
<?php endforeach; ?>
