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

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

/**
 * JComments Custom BBCode class
 *
 * @since  3.0
 */
class JcommentsCustombbcode
{
	/**
	 * Array of bbcodes
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $codes = array();

	/**
	 * Array of bbcode patterns
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $patterns = array();

	protected $filter_patterns = array();

	protected $html_replacements = array();

	protected $text_replacements = array();

	/**
	 * Initialize all custom bbcodes
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$db  = Factory::getContainer()->get('DatabaseDriver');
		$acl = JcommentsFactory::getACL();

		ob_start();

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'id', 'name', 'simple_pattern', 'simple_replacement_html', 'simple_replacement_text', 'pattern',
						'replacement_html', 'replacement_text', 'button_acl', 'button_open_tag', 'button_close_tag',
						'button_title', 'button_prompt', 'button_image', 'button_css', 'button_enabled'
					)
				)
			)
			->from($db->quoteName('#__jcomments_custom_bbcodes'))
			->where($db->quoteName('published') . ' = 1')
			->order($db->escape('ordering') . ' ASC');

		try
		{
			$db->setQuery($query);
			$codes = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Log::add($e->getMessage() . ' in ' . __METHOD__ . '#' . __LINE__, Log::ERROR, 'com_jcomments');

			return;
		}

		if (count($codes))
		{
			foreach ($codes as $code)
			{
				// Check button permission
				if ($acl->enableCustomBBCode($code->button_acl))
				{
					if ($code->button_image != '')
					{
						if (strpos($code->button_image, Uri::base()) === false)
						{
							$code->button_image = Uri::base() . trim($code->button_image, '/');
						}
					}

					$this->codes[] = $code;
				}
				else
				{
					$this->filter_patterns[] = '#' . $code->pattern . '#ismu';
				}

				$this->patterns[]          = '#' . $code->pattern . '#ismu';
				$this->html_replacements[] = $code->replacement_html;
				$this->text_replacements[] = $code->replacement_text;
			}
		}

		ob_end_clean();
	}

	public function getList()
	{
		return $this->codes;
	}

	/**
	 * Filter BBCode
	 *
	 * @param   string   $str         Comment text
	 * @param   boolean  $forceStrip  Force to delete the code.
	 *
	 * @return  string|null
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function filter(string $str, bool $forceStrip = false): ?string
	{
		if (count($this->filter_patterns))
		{
			ob_start();
			$filterReplacement = $this->text_replacements;
			$str               = preg_replace($this->filter_patterns, $filterReplacement, $str);
			ob_end_clean();
		}

		if ($forceStrip === true)
		{
			ob_start();
			$str = preg_replace($this->patterns, $this->text_replacements, $str);
			ob_end_clean();
		}

		return $str;
	}

	/**
	 * BBCode replacement with html
	 *
	 * @param   string   $str              Comment text
	 * @param   boolean  $textReplacement  Replace with HTML or text
	 *
	 * @return  string|null
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function replace(string $str, bool $textReplacement = false): ?string
	{
		if (count($this->patterns))
		{
			ob_start();
			$str = preg_replace(
				$this->patterns,
				($textReplacement ? $this->text_replacements : $this->html_replacements),
				$str
			);
			ob_end_clean();
		}

		return $str;
	}
}
