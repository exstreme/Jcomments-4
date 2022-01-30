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

use Joomla\CMS\Component\ComponentHelper;

class JCommentsHelper
{
	public static function getSmiliesPath()
	{
		$config = ComponentHelper::getParams('com_jcomments');

		$smiliesPath = $config->get('smilies_path', 'components/com_jcomments/images/smilies/');
		$smiliesPath = str_replace(array('//', '\\\\'), '/', $smiliesPath);
		$smiliesPath = trim($smiliesPath, '\\/') . '/';

		return $smiliesPath;
	}
}
