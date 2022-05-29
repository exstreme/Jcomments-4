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

namespace Joomla\Component\Jcomments\Site\Library\Jcomments;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

/**
 * JComments smilies support
 */
class JcommentsSmilies
{
	protected $_smilies = array();
	protected $_replacements = array();

	public function __construct()
	{
		if (count($this->_replacements) == 0)
		{
			$config = ComponentHelper::getParams('com_jcomments');
			$path   = Uri::root(true) . '/' . trim(str_replace('\\', '/', $config->get('smilies_path')), '/') . '/';

			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			// Get smilies from database.
			$query = $db->getQuery(true)
				->select($db->quoteName(array('code', 'image', 'name')))
				->from($db->quoteName('#__jcomments_smilies'))
				->where($db->quoteName('published') . ' = 1');

			$db->setQuery($query);
			$smilies = $db->loadAssocList();

			if (!empty($smilies))
			{
				foreach ($smilies as $smiley)
				{
					$this->_smilies[$smiley['code']] = $smiley['image'];
				}
			}

			$list = $this->_smilies;
			uksort($list, array($this, 'compare'));

			foreach ($list as $code => $image)
			{
				$this->_replacements['code'][] = '#(^|\s|\n|\r|\>)(' . preg_quote($code, '#') . ')(\s|\n|\r|\<|$)#ismu';
				$this->_replacements['icon'][] = '\\1 \\2 \\3';
				$this->_replacements['code'][] = '#(^|\s|\n|\r|\>)(' . preg_quote($code, '#') . ')(\s|\n|\r|\<|$)#ismu';
				$this->_replacements['icon'][] = '\\1<img src="' . $path . $image . '" alt="' . htmlspecialchars($code) . '" />\\3';
			}
		}
	}

	public function compare($a, $b)
	{
		if (strlen($a) == strlen($b))
		{
			return 0;
		}

		return (strlen($a) > strlen($b)) ? -1 : 1;
	}

	public function getList()
	{
		return $this->_smilies;
	}

	/**
	 * @param   string  $str  Comment text
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public function replace($str)
	{
		if (count($this->_replacements) > 0)
		{
			$str = preg_replace($this->_replacements['code'], $this->_replacements['icon'], $str);
		}

		return $str;
	}

	/**
	 * @param   string  $str  Comment text
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public function strip($str)
	{
		if (count($this->_replacements) > 0)
		{
			$str = preg_replace($this->_replacements['code'], '\\1\\3', $str);
		}

		return $str;
	}
}
