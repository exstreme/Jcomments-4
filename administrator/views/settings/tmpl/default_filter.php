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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
?>
<?php if (empty($this->bootstrap)): ?>
<fieldset id="filter-bar">
	<?php if (!empty($this->filter) != '') : ?>
	<div class="filter-select fltlft">
		<label for="lang"><?php echo Text::_('JFIELD_LANGUAGE_LABEL'); ?></label>
		<?php echo $this->filter; ?>
	</div>
	<?php endif; ?>
</fieldset>
<?php endif; ?>
