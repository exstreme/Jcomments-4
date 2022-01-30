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

use Joomla\CMS\Language\Text;

/** @var JoomlaTuneTemplate $displayData */
?>
<form id="comments-report-form" name="comments-report-form" action="javascript:void();" autocomplete="off">
	<fieldset>
		<legend class="h6"><?php echo Text::_('REPORT_TO_ADMINISTRATOR'); ?></legend>
		<?php if ($displayData->getVar('isGuest', 1) == 1): ?>
			<div class="row align-items-center">
				<div class="col-5">
					<input id="comments-report-form-name" class="form-control form-control-sm" type="text" name="name"
						   value="" maxlength="255" size="22" aria-label="<?php echo Text::_('REPORT_NAME'); ?>"
						   required/>
				</div>
				<div class="col-auto">
					<label for="comments-report-form-name" class="form-label"><?php echo Text::_('REPORT_NAME'); ?></label>
				</div>
			</div>
		<?php endif; ?>
		<div class="row align-items-center">
			<div class="col-5">
				<input id="comments-report-form-reason" class="form-control form-control-sm" type="text" name="reason"
					   value="" maxlength="255" size="22" aria-label="<?php echo Text::_('REPORT_REASON'); ?>"
					   required/>
			</div>
			<div class="col-auto">
				<label for="comments-report-form-reason" class="form-label"><?php echo Text::_('REPORT_REASON'); ?></label>
			</div>
		</div>
		<br>
		<div id="comments-report-form-msg"></div>
		<div id="comments-report-form-buttons">
			<button type="submit" class="btn btn-danger btn-sm"
					onclick="jcomments.saveReport();return false;"><?php echo Text::_('REPORT_SUBMIT'); ?>
			</button>
			<button type="reset" class="btn btn-secondary btn-sm"
					onclick="jcomments.cancelReport();return false;"><?php echo Text::_('REPORT_CANCEL'); ?>
			</button>
			<div style="clear:both;"></div>
		</div>
		<input type="hidden" name="commentid" value="<?php echo $displayData->getVar('comment-id'); ?>"/>
	</fieldset>
</form>
