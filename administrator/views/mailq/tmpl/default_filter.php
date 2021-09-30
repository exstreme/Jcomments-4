<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$filterSearch  = $this->escape($this->state->get('filter.search'));
$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirection = $this->escape($this->state->get('list.direction'));
$sortFields    = $this->getSortFields();
?>
<div id="filter-bar" class="btn-toolbar">
    <div class="filter-search btn-group pull-left">
        <label for="filter_search" class="element-invisible"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
            :</label>
        <input type="text" name="filter_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
               id="filter_search" value="<?php echo $filterSearch; ?>"
               title="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"/>
    </div>
    <div class="btn-group hidden-phone">
        <button class="btn tip hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i
                    class="icon-search"></i></button>
        <button class="btn tip hasTooltip" type="button"
                onclick="document.getElementById('filter_search').value='';this.form.submit();"
                title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="limit"
               class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
		<?php echo $this->pagination->getLimitBox(); ?>
    </div>
    <div class="btn-group pull-right hidden-phone">
        <label for="directionTable"
               class="element-invisible"><?php echo Text::_('JFIELD_ORDERING_DESC'); ?></label>
        <select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
            <option value=""><?php echo Text::_('JFIELD_ORDERING_DESC'); ?></option>
            <option value="asc" <?php if ($listDirection == 'asc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_ASCENDING'); ?></option>
            <option value="desc" <?php if ($listDirection == 'desc') echo 'selected="selected"'; ?>><?php echo Text::_('JGLOBAL_ORDER_DESCENDING'); ?></option>
        </select>
    </div>
    <div class="btn-group pull-right">
        <label for="sortTable" class="element-invisible"><?php echo Text::_('JGLOBAL_SORT_BY'); ?></label>
        <select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
            <option value=""><?php echo Text::_('JGLOBAL_SORT_BY'); ?></option>
			<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
        </select>
    </div>
</div>
<div class="clearfix"></div>
