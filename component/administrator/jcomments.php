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

$app = Factory::getApplication();

if (!$app->getIdentity()->authorise('core.manage', 'com_jcomments'))
{
	throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 404);
}

// Define component path. This constant will be removed without replacement in J5.
if (!defined('JPATH_COMPONENT'))
{
	define('JPATH_COMPONENT', JPATH_BASE . '/components/com_jcomments');
}

require_once JPATH_ROOT . '/components/com_jcomments/classes/bootstrap.php';

[$controller, $task] = JCommentsLegacyBootstrap::createController('JComments', JPATH_COMPONENT);
$controller->execute($task);
$controller->redirect();
