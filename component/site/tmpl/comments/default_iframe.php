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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var Joomla\Component\Jcomments\Site\View\Comments\HtmlView $this */

$input  = Factory::getApplication()->input;
$return = base64_encode(Joomla\CMS\Uri\Uri::getInstance());
$url    = 'index.php?option=com_jcomments&view=form&tmpl=component&object_id=' . $this->objectID
	. '&object_group=' . $this->objectGroup . '&return=' . $return;
?>
<div class="commentsFormWrapper" style="overflow: hidden;">
	<?php echo HTMLHelper::_(
		'iframe',
		Route::_($url) . '&_=' . time(),
		bin2hex(random_bytes(5)),
		array(
			'width'     => '100%',
			'onload'    => 'Jcomments.iFrameHeight(this);',
			'style'     => 'overflow: hidden;',
			'scrolling' => 'no',
			'class'     => 'commentsFormFrame',
			'id'        => 'addcomments'
		)
	); ?>
</div>
