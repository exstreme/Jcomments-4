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

use Joomla\CMS\Factory;

ob_start();

$app = Factory::getApplication();

if ($app->input->get('option') !== 'com_jcomments')
{
	$app->getLanguage()->load('com_jcomments', JPATH_SITE);
}

ob_end_clean();

JLoader::registerPrefix('JComments', JPATH_ROOT . '/components/com_jcomments/classes/');
JLoader::registerPrefix('JComments', JPATH_ROOT . '/components/com_jcomments/helpers/');
