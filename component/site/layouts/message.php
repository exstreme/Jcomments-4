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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/* @var $displayData array */
Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.alert');

$closeBtn = $displayData['close']
	? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . Text::_('JCLOSE') . '"></button>' : '';
$closeBtnClass = $displayData['close'] ? ' alert-dismissible' : '';
?>
<div class="jc-message alert alert-<?php echo $displayData['type']; ?> m-1 d-flex align-items-center fade show<?php echo $closeBtnClass; ?>" role="alert">
	<span class="pe-3 icon icon-<?php echo $displayData['icon']; ?>"></span> <div class="alert-wrapper"><?php echo $displayData['msg'] . $closeBtn; ?></div>
</div>
