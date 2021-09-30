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

class jtt_tpl_report_form extends JoomlaTuneTemplate
{
	function render() 
	{
?>
<span><?php echo JText::_('REPORT_TO_ADMINISTRATOR'); ?></span>
<form id="comments-report-form" name="comments-report-form" action="javascript:void(null);">
<?php
		if ($this->getVar('isGuest', 1) == 1) {
?>
<p>
	<span>
		<input id="comments-report-form-name" type="text" name="name" value="" maxlength="255" size="22" />
		<label for="comments-report-form-name"><?php echo JText::_('REPORT_NAME'); ?></label>
	</span>
</p>
<?php
		}
?>
<p>
	<span>
		<input id="comments-report-form-reason" type="text" name="reason" value="" maxlength="255" size="22" />
		<label for="comments-report-form-reason"><?php echo JText::_('REPORT_REASON'); ?></label>
	</span>
</p>
<div id="comments-report-form-buttons">
	<div class="btn"><div><a href="#" onclick="jcomments.saveReport();return false;" title="<?php echo JText::_('REPORT_SUBMIT'); ?>"><?php echo JText::_('REPORT_SUBMIT'); ?></a></div></div>
	<div class="btn"><div><a href="#" onclick="jcomments.cancelReport();return false;" title="<?php echo JText::_('REPORT_CANCEL'); ?>"><?php echo JText::_('REPORT_CANCEL'); ?></a></div></div>
	<div style="clear:both;"></div>
</div>
<input type="hidden" name="commentid" value="<?php echo $this->getVar('comment-id'); ?>" />
</form>
<?php
	}
}