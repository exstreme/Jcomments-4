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

/** @var $this Joomla\Component\Jcomments\Site\View\Comments\HtmlView */

$input = Factory::getApplication()->input;
?>
<div class="commentsFormWrapper" style="overflow: hidden;">
	<?php echo HTMLHelper::_(
		'iframe',
		Route::_(
			'index.php?option=com_jcomments&view=form&tmpl=component&object_id=' . $input->getInt('id') . '&object_group=com_content'
		) . '&_=' . time(),
		bin2hex(random_bytes(5)),
		array(
			'width' => '100%',
			'onload' => 'iFrameHeight(this);',
			'style' => 'overflow: hidden;',
			'scrolling' => 'no',
			'class' => 'commentsFormFrame'
		)
	); ?>
</div>
