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

class jtt_tpl_report_form extends JoomlaTuneTemplate
{
	public function render()
	{
		?>
		<form id="comments-report-form" name="comments-report-form" action="javascript:void();">
			<fieldset>
				<legend class="h6"><?php echo JText::_('REPORT_TO_ADMINISTRATOR'); ?></legend>
			<?php
			if ($this->getVar('isGuest', 1) == 1)
			{
				?>
				<div>
					<input id="comments-report-form-name" type="text" name="name" value="" maxlength="255" size="22" required/>
					<label for="comments-report-form-name"><?php echo JText::_('REPORT_NAME'); ?></label>
				</div>
				<?php
			}
			?>
				<div>
					<input id="comments-report-form-reason" type="text" name="reason" value="" maxlength="255" size="22" required/>
					<label for="comments-report-form-reason"><?php echo JText::_('REPORT_REASON'); ?></label>
				</div><br>
				<div id="comments-report-form-msg"></div>
				<div id="comments-report-form-buttons">
					<button type="submit" class="btn btn-danger btn-sm"
							onclick="jcomments.saveReport();return false;"><?php echo JText::_('REPORT_SUBMIT'); ?>
					</button>
					<button type="reset" class="btn btn-secondary btn-sm"
							onclick="jcomments.cancelReport();return false;"><?php echo JText::_('REPORT_CANCEL'); ?>
					</button>
					<div style="clear:both;"></div>
				</div>
				<input type="hidden" name="commentid" value="<?php echo $this->getVar('comment-id'); ?>"/>
			</fieldset>
		</form>
		<?php
	}
}
