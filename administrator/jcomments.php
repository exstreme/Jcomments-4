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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

$app = Factory::getApplication();

if (!$app->getIdentity()->authorise('core.manage', 'com_jcomments'))
{
	throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 404);
}

// Define component path. This constatnt will be removed without replacement in J5.
if (!defined('JPATH_COMPONENT'))
{
	define('JPATH_COMPONENT', JPATH_BASE . '/components/com_jcomments');
}

// TODO Shoud be removed after checks.
$language = $app->getLanguage();
$language->load('com_jcomments', JPATH_ROOT . '/administrator', 'en-GB', true);
$language->load('com_jcomments', JPATH_ROOT . '/administrator', null, true);

require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.legacy.php');
require_once(JPATH_ROOT . '/components/com_jcomments/jcomments.class.php');

JLoader::register('JCommentsControllerForm', JPATH_COMPONENT . '/controllers/controllerform.php');
JLoader::register('JCommentsControllerList', JPATH_COMPONENT . '/controllers/controllerlist.php');
JLoader::register('JCommentsModelLegacy', JPATH_COMPONENT . '/models/model.php');
JLoader::register('JCommentsModelForm', JPATH_COMPONENT . '/models/modelform.php');
JLoader::register('JCommentsModelList', JPATH_COMPONENT . '/models/modellist.php');
JLoader::register('JCommentsViewLegacy', JPATH_COMPONENT . '/views/view.php');

$controller = BaseController::getInstance('JComments');
$controller->execute($app->input->get('task'));
$controller->redirect();
