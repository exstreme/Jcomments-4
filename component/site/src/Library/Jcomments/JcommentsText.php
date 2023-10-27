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
use Joomla\CMS\Language\Text;

/**
 * JComments common text functions
 *
 * @since  3.0
 */
class JcommentsText
{
	/**
	 * Filter text from BBCode or HTML tags
	 *
	 * @param   string   $text              The input string.
	 * @param   boolean  $forceStrip        Force to delete the tag.
	 * @param   boolean  $forceStripCustom  Force to delete the custom tag.
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	public static function filterText($text, bool $forceStrip = false, bool $forceStripCustom = false)
	{
		$params = ComponentHelper::getParams('com_jcomments');

		if ($params->get('editor_format') == 'bbcode')
		{
			$text = JcommentsFactory::getBbcode()->filter($text, $forceStrip);

			if ((int) $params->get('enable_custom_bbcode'))
			{
				$text = JCommentsFactory::getCustomBBCode()->filter($text, $forceStripCustom);
			}
		}
		else
		{
			$text = ComponentHelper::filterText($text);
		}

		return $text;
	}

	/**
	 * Replaces newlines with HTML line breaks
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function nl2br($text)
	{
		$text = preg_replace(array('/\r/u', '/^\n+/u', '/\n+$/u'), '', $text);

		return str_replace("\n", '<br>', $text);
	}

	/**
	 * Replaces HTML line breaks with newlines
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function br2nl($text)
	{
		return str_replace(array('<br />', '<br>'), "\n", $text);
	}

	/**
	 * Escapes input string with slashes to use it in JavaScript
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since   3.0
	 */
	public static function jsEscape($text)
	{
		return addcslashes($text, "\\\\&\"\n\r<>'");
	}

	public static function url($s)
	{
		if (isset($s)
			&& preg_match('/^((http|https|ftp):\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,6}((:[0-9]{1,5})?\/.*)?$/i', $s))
		{
			$url = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $s);
			$url = str_replace(';//', '://', $url);

			if ($url != '')
			{
				$url = (!strstr($url, '://')) ? 'http://' . $url : $url;

				return preg_replace('/&([^#])(?![a-z]{2,8};)/', '&#038;$1', $url);
			}
		}

		return '';
	}

	public static function censor($text)
	{
		if (!empty($text))
		{
			ob_start();

			$config      = ComponentHelper::getParams('com_jcomments');
			$lang        = Factory::getApplication()->getLanguage();
			$words       = $config->get('badwords');
			$replaceWord = self::getCensorReplace($config->get('censor_replace_fields'), $lang->getTag());

			if (!empty($words))
			{
				$words = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $words));
				$words = explode(",", $words);

				if (is_array($words))
				{
					for ($i = 0, $n = count($words); $i < $n; $i++)
					{
						$word = trim($words[$i]);

						if ($word != '')
						{
							$word = str_replace('#', '\#', str_replace('\#', '#', $word));
							$txt  = trim(preg_replace('#' . $word . '#ismu', $replaceWord, $text));

							// Make safe from dummy bad words list
							if ($txt != '')
							{
								$text = $txt;
							}
						}
					}
				}
			}

			ob_end_clean();
		}

		return $text;
	}

	/**
	 * Cleans text of all formatting and scripting code
	 *
	 * @param   string  $text  The input string.
	 *
	 * @return  string  Returns the altered string.
	 *
	 * @since  3.0
	 */
	public static function cleanText(string $text): string
	{
		$text = JCommentsFactory::getBBCode()->filter($text, true);

		if ((int) ComponentHelper::getParams('com_jcomments')->get('enable_custom_bbcode'))
		{
			$text = JCommentsFactory::getCustomBBCode()->filter($text, true);
		}

		$text = str_replace('<br />', ' ', $text);
		$text = preg_replace('#(\s){2,}#imu', '\\1', $text);
		$text = preg_replace('#<script[^>]*>.*?</script>#ismu', '', $text);
		$text = preg_replace('#<a\s+.*?href="([^"]+)"[^>]*>([^<]+)</a>#ismu', '\2 (\1)', $text);
		$text = preg_replace('#<!--.+?-->#ismu', '', $text);
		$text = preg_replace('#&nbsp;|&amp;|&quot;#imu', ' ', $text);

		$text = strip_tags($text);
		$text = htmlspecialchars($text);

		return html_entity_decode($text);
	}

	/**
	 * Get language aware message strings for comment rules, no access rights for comment, comments closed, user banned.
	 *
	 * @param   object  $messages  Object in subform format. E.g. array(subform => array(form => value, ...))
	 * @param   string  $field     Field name to search.
	 * @param   string  $lang      Language tag.
	 *
	 * @return  string  Returns the string according to current frontend language.
	 *
	 * @since   4.0
	 */
	public static function getMessagesBasedOnLanguage(object $messages, string $field, string $lang = ''): string
	{
		$data = array();

		foreach ($messages as $_message)
		{
			$data[$_message->lang] = $_message;
		}

		if (empty($lang) || $lang == '*')
		{
			// Get messages for 'All' language
			$message = $data['*']->$field;
		}
		else
		{
			// Get messages for current item language
			if (array_key_exists($lang, $data))
			{
				$message = $data[$lang]->$field;
			}
			// If not found, fallback to 'All' language messages
			else
			{
				if (array_key_exists('*', $data))
				{
					$message = $data['*']->$field;
				}
				// Give up. User not defined messages for proper language in component settings.
				else
				{
					$message = Text::_('ERROR');
				}
			}
		}

		return $message;
	}

	/**
	 * Get replacement string for current language.
	 *
	 * @param   object  $replaces  Object in subform format. E.g. array(subform => array(form => value, ...))
	 * @param   string  $lang      Language tag.
	 *
	 * @return  string  Returns the string according to current frontend language.
	 *
	 * @since   4.0
	 */
	private static function getCensorReplace(object $replaces, string $lang): string
	{
		$data = array();

		foreach ($replaces as $replacement)
		{
			$data[$replacement->lang] = $replacement->censor_replace_word;
		}

		return (empty($lang) || $lang == '*' || !array_key_exists($lang, $data)) ? $data['*'] : $data[$lang];
	}
}
