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

/**
 * JComments smilies support
 *
 * @since  3.0
 */
class JcommentsSmilies
{
	/**
	 * @var   array  Associative array with smilies.
	 *
	 * @since 3.0
	 */
	protected $smilies = array();

	/**
	 * @var   array  Array with replacements to replace codes with image or clean up text from smilies.
	 *
	 * @since 3.0
	 */
	protected $replacements = array();

	/**
	 * Constructor
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		if (count($this->replacements) == 0)
		{
			$config = ComponentHelper::getParams('com_jcomments');
			$path   = Uri::root(true) . '/' . trim(str_replace('\\', '/', $config->get('smilies_path')), '/') . '/';

			/** @var \Joomla\Database\DatabaseDriver $db */
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
					$this->smilies[$smiley['code']] = $smiley['image'];
				}
			}

			$list = $this->smilies;
			uksort($list, array($this, 'compare'));

			foreach ($list as $code => $image)
			{
				$this->replacements['code'][] = '#(^|\s|\n|\r|\>)(' . preg_quote($code, '#') . ')(\s|\n|\r|\<|$)#ismu';
				$this->replacements['icon'][] = '\\1 \\2 \\3';
				$this->replacements['code'][] = '#(^|\s|\n|\r|\>)(' . preg_quote($code, '#') . ')(\s|\n|\r|\<|$)#ismu';
				$this->replacements['icon'][] = '\\1<img src="' . $path . $image . '" alt="' . htmlspecialchars($code) . '" />\\3';
			}
		}
	}

	public function compare($a, $b): int
	{
		if (strlen($a) == strlen($b))
		{
			return 0;
		}

		return (strlen($a) > strlen($b)) ? -1 : 1;
	}

	/**
	 * Get emoticons list
	 *
	 * @param   string   $listType     List type to return. Default all three lists will be returned.
	 *                                 Can be 'all', 'dropdown', 'more', 'hidden'.
	 * @param   integer  $firstLimit   Number of emoticons to be included in the dropdown.
	 * @param   integer  $secondLimit  Number of emoticons to be included in the more section.
	 *
	 * @return  object
	 *
	 * @since   3.0
	 */
	public function getList(string $listType = 'all', int $firstLimit = 15, int $secondLimit = 36)
	{
		$list = '';

		if (count($this->smilies) > 0)
		{
			$list = (object) array(
				'dropdown' => (object) array(),
				'more'     => (object) array(),
				'hidden'   => (object) array()
			);
			$i = 0;

			foreach ($this->smilies as $code => $icon)
			{
				if ((0 <= $i) && ($i <= $firstLimit))
				{
					$list->dropdown->$code = $icon;
				}
				elseif (($firstLimit <= $i) && ($i <= $secondLimit))
				{
					$list->more->$code = $icon;
				}
				else
				{
					$list->hidden->$code = $icon;
				}

				$i++;
			}
		}

		return $listType == 'all' ? $list : (property_exists($list, $listType) ? $list->$listType : $list);
	}

	/**
	 * Replace smilies' codes with images
	 *
	 * @param   string  $text  Comment text
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public function replace(string $text): string
	{
		if (count($this->replacements) > 0)
		{
			$text = preg_replace($this->replacements['code'], $this->replacements['icon'], $text);
		}

		return $text;
	}

	/**
	 * Remove smilies' codes from comment text
	 *
	 * @param   string  $str  Comment text
	 *
	 * @return  string
	 *
	 * @since   3.0
	 */
	public function strip(string $str): string
	{
		if (count($this->replacements) > 0)
		{
			$str = preg_replace($this->replacements['code'], '\\1\\3', $str);
		}

		return $str;
	}
}
