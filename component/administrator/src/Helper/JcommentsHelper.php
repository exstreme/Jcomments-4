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

namespace Joomla\Component\Jcomments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * JComments component helper.
 *
 * @since  1.6
 */
class JcommentsHelper extends \Joomla\CMS\Helper\ContentHelper
{
	public static function getSmiliesPath()
	{
		$config = ComponentHelper::getParams('com_jcomments');

		$smiliesPath = $config->get('smilies_path');
		$smiliesPath = str_replace(array('//', '\\\\'), '/', $smiliesPath);

		return trim($smiliesPath, '\\/') . '/';
	}
}
