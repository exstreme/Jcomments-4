<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

echo HTMLHelper::_(
	'bootstrap.renderModal',
	'reportModal',
	array(
		'title'  => Text::_('REPORT_TO_ADMINISTRATOR'),
		'backdrop' => 'static',
		'height' => '100%',
		'width'  => '100%',
		'footer' => ''
	),
	'<div class="d-flex align-items-center report-loader">
		<div class="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true"></div>&nbsp;
		<span role="status">' . Text::_('LOADING') . '</span>
	</div>
	<iframe width="100%" style="overflow: hidden;" class="reportFormFrame" id="reportFormFrame" name="' . bin2hex(random_bytes(4)) . '"></iframe>'
);
