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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;

/**
 * JComments BBCode
 *
 * @since  3.0
 */
class JcommentsBbcode
{
	/**
	 * Array of bbcodes
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $codes = array();

	/**
	 * Initialize all bbcodes
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		ob_start();

		$this->registerCode('b');
		$this->registerCode('i');
		$this->registerCode('u');
		$this->registerCode('s');
		$this->registerCode('sub');
		$this->registerCode('sup');
		$this->registerCode('separator1');
		$this->registerCode('left');
		$this->registerCode('center');
		$this->registerCode('right');
		$this->registerCode('justify');
		$this->registerCode('separator2');
		$this->registerCode('font');
		$this->registerCode('size');
		$this->registerCode('color');
		$this->registerCode('removeformat');
		$this->registerCode('separator3');
		$this->registerCode('cut');
		$this->registerCode('copy');
		$this->registerCode('paste');
		$this->registerCode('pastetext');
		$this->registerCode('separator4');
		$this->registerCode('list');
		$this->registerCode('orderedlist');
		$this->registerCode('indent');
		$this->registerCode('outdent');
		$this->registerCode('separator5');
		$this->registerCode('table');
		$this->registerCode('code');
		$this->registerCode('quote');
		$this->registerCode('separator6');
		$this->registerCode('horizontalrule');
		$this->registerCode('img');
		$this->registerCode('email');
		$this->registerCode('url');
		$this->registerCode('unlink');
		$this->registerCode('emoticon');
		$this->registerCode('separator7');
		$this->registerCode('youtube');
		$this->registerCode('date');
		$this->registerCode('time');
		$this->registerCode('separator8');
		$this->registerCode('ltr');
		$this->registerCode('rtl');
		$this->registerCode('separator9');
		$this->registerCode('print');
		$this->registerCode('maximize');
		$this->registerCode('source');
		$this->registerCode('hide');
		$this->registerCode('spoiler');

		ob_end_clean();
	}

	/**
	 * @param   string  $str  Bbcode name
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function registerCode(string $str)
	{
		$this->codes[$str] = Factory::getApplication()->getIdentity()->authorise('comment.bbcode.' . $str, 'com_jcomments');
	}

	/**
	 * Get registered bbcodes, optionally filtered by key.
	 *
	 * @param   mixed  $keys  Keys to filter by. String separated by comma or array of values.
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	public function get($keys = null): array
	{
		if (!empty($keys))
		{
			$keys = is_array($keys) ? $keys : explode(',', $keys);

			return array_keys(array_intersect_key($this->codes, array_flip($keys)));
		}

		return array_keys($this->codes);
	}

	public function enabled()
	{
		static $enabled = null;

		if ($enabled == null)
		{
			foreach ($this->codes as $code => $_enabled)
			{
				if ($_enabled == 1 && $code != 'quote')
				{
					$enabled = $_enabled;
					break;
				}
			}
		}

		return $enabled;
	}

	/**
	 * Check if user can see and use bbcode.
	 *
	 * @param   string  $str  BBcode. E.g. 'list' or 'b' or 's'
	 *
	 * @return  boolean
	 *
	 * @since   3.0
	 */
	public function canUse(string $str): bool
	{
		return (bool) $this->codes[$str];
	}

	public function filter($str, $forceStrip = false)
	{
		ob_start();
		$patterns     = array();
		$replacements = array();

		// Empty tags
		foreach ($this->codes as $code => $enabled)
		{
			$patterns[]     = '/\[' . $code . '\]\[\/' . $code . '\]/iu';
			$replacements[] = '';
		}

		// B
		if ((!$this->canUse('b')) || ($forceStrip))
		{
			$patterns[]     = '/\[b\](.*?)\[\/b\]/iu';
			$replacements[] = '\\1';
		}

		// I
		if ((!$this->canUse('i')) || ($forceStrip))
		{
			$patterns[]     = '/\[i\](.*?)\[\/i\]/iu';
			$replacements[] = '\\1';
		}

		// U
		if ((!$this->canUse('u')) || ($forceStrip))
		{
			$patterns[]     = '/\[u\](.*?)\[\/u\]/iu';
			$replacements[] = '\\1';
		}

		// S
		if ((!$this->canUse('s')) || ($forceStrip))
		{
			$patterns[]     = '/\[s\](.*?)\[\/s\]/iu';
			$replacements[] = '\\1';
		}

		// Sub
		if ((!$this->canUse('sub')) || ($forceStrip))
		{
			$patterns[]     = '/\[sub\](.*?)\[\/sub\]/iu';
			$replacements[] = '\\1';
		}

		// Sup
		if ((!$this->canUse('sup')) || ($forceStrip))
		{
			$patterns[]     = '/\[sup\](.*?)\[\/sup\]/iu';
			$replacements[] = '\\1';
		}

		// URL
		if ((!$this->canUse('url')) || ($forceStrip))
		{
			$patterns[]     = '/\[url\](.*?)\[\/url\]/iu';
			$replacements[] = '\\1';
			$patterns[]     = '/\[url=([^\s<\"\'\]]*?)\](.*?)\[\/url\]/iu';
			$replacements[] = '\\2: \\1';
		}

		// IMG
		if ((!$this->canUse('img')) || ($forceStrip))
		{
			$patterns[]     = '/\[img\](.*?)\[\/img\]/iu';
			$replacements[] = '\\1';
		}

		// HIDE
		if ((!$this->canUse('hide')) || ($forceStrip))
		{
			$patterns[] = '/\[hide\](.*?)\[\/hide\]/iu';

			if (Factory::getApplication()->getIdentity()->get('id'))
			{
				$replacements[] = '\\1';
			}
			else
			{
				$replacements[] = '';
			}
		}

		// CODE
		if ($forceStrip)
		{
			$codePattern    = '#\[code\=?([a-z0-9]*?)\](.*?)\[\/code\]#ismu';
			$patterns[]     = $codePattern;
			$replacements[] = '\\2';
		}

		$str = preg_replace($patterns, $replacements, $str);

		// LIST
		if ((!$this->canUse('list')) || ($forceStrip))
		{
			$matches    = array();
			$matchCount = preg_match_all('/\[list\](<br\s?\/?\>)*(.*?)(<br\s?\/?\>)*\[\/list\]/isu', $str, $matches);

			for ($i = 0; $i < $matchCount; $i++)
			{
				$textBefore = preg_quote($matches[2][$i]);
				$textAfter  = preg_replace('#(<br\s?\/?\>)*\[\*\](<br\s?\/?\>)*#isu', '<br />', $matches[2][$i]);
				$textAfter  = preg_replace('#^<br />#isu', '', $textAfter);
				$textAfter  = preg_replace('#(<br\s?\/?\>)+#isu', '<br />', $textAfter);
				$str        = preg_replace(
					'#\[list\](<br\s?\/?\>)*' . $textBefore . '(<br\s?\/?\>)*\[/list\]#isu',
					"\n$textAfter\n",
					$str
				);
			}

			$matches    = array();
			$matchCount = preg_match_all(
				'#\[list=(a|A|i|I|1)\](<br\s?\/?\>)*(.*?)(<br\s?\/?\>)*\[\/list\]#isu',
				$str,
				$matches
			);

			for ($i = 0; $i < $matchCount; $i++)
			{
				$textBefore = preg_quote($matches[3][$i]);
				$textAfter  = preg_replace('#(<br\s?\/?\>)*\[\*\](<br\s?\/?\>)*#isu', '<br />', $matches[3][$i]);
				$textAfter  = preg_replace('#^<br />#u', "", $textAfter);
				$textAfter  = preg_replace('#(<br\s?\/?\>)+#u', '<br />', $textAfter);
				$str        = preg_replace(
					'#\[list=(a|A|i|I|1)\](<br\s?\/?\>)*' . $textBefore . '(<br\s?\/?\>)*\[/list\]#isu',
					"\n$textAfter\n",
					$str
				);
			}
		}

		if ($forceStrip)
		{
			// QUOTE
			$quotePattern = '#\[quote\s?name=\"([^\"\'\<\>\(\)]+)+\"\](<br\s?\/?\>)*(.*?)(<br\s?\/?\>)*\[\/quote\]#iu';
			$quoteReplace = ' ';

			while (preg_match($quotePattern, $str))
			{
				$str = preg_replace($quotePattern, $quoteReplace, $str);
			}

			$quotePattern = '#\[quote[^\]]*?\](<br\s?\/?\>)*([^\[]+)(<br\s?\/?\>)*\[\/quote\]#iu';
			$quoteReplace = ' ';

			while (preg_match($quotePattern, $str))
			{
				$str = preg_replace($quotePattern, $quoteReplace, $str);
			}

			$str = preg_replace(
				'#\[\/?(b|strong|i|em|u|s|del|sup|sub|url|img|list|quote|code|hide)\]#isu',
				'',
				$str
			);
		}

		$str = trim(preg_replace('#( ){2,}#iu', '\\1', $str));

		ob_end_clean();

		return $str;
	}

	/**
	 * BBCode replacement with html
	 *
	 * @param   string  $str  Comment text
	 *
	 * @return  string|null
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function replace(string $str): ?string
	{
		ob_start();

		$patterns     = array();
		$replacements = array();

		// B
		$patterns[]     = '/\[b\](.*?)\[\/b\]/iu';
		$replacements[] = '<strong>\\1</strong>';

		// I
		$patterns[]     = '/\[i\](.*?)\[\/i\]/iu';
		$replacements[] = '<em>\\1</em>';

		// U
		$patterns[]     = '/\[u\](.*?)\[\/u\]/iu';
		$replacements[] = '<u>\\1</u>';

		// S
		$patterns[]     = '/\[s\](.*?)\[\/s\]/iu';
		$replacements[] = '<span class="text-decoration-line-through">\\1</span>';

		// SUP
		$patterns[]     = '/\[sup\](.*?)\[\/sup\]/iu';
		$replacements[] = '<sup>\\1</sup>';

		// SUB
		$patterns[]     = '/\[sub\](.*?)\[\/sub\]/iu';
		$replacements[] = '<sub>\\1</sub>';

		// URL (local)
		$liveSite = Uri::base();

		$patterns[]     = '#\[url\](' . preg_quote($liveSite, '#') . '[^\s<\"\']*?)\[\/url\]#iu';
		$replacements[] = '<a href="\\1" target="_blank">\\1</a>';

		$patterns[]     = '#\[url=(' . preg_quote($liveSite, '#') . '[^\s<\"\'\]]*?)\](.*?)\[\/url\]#iu';
		$replacements[] = '<a href="\\1" target="_blank">\\2</a>';

		$patterns[]     = '/\[url=(\#|\/)([^\s<\"\'\]]*?)\](.*?)\[\/url\]/iu';
		$replacements[] = '<a href="\\1\\2" target="_blank">\\3</a>';

		// URL (external)
		$patterns[]     = '#\[url\](http:\/\/)?([^\s<\"\']*?)\[\/url\]#iu';
		$replacements[] = '<a href="http://\\2" rel="external nofollow" target="_blank">\\2</a>'; // TODO Support https

		$patterns[]     = '/\[url=([a-z]*\:\/\/)([^\s<\"\'\]]*?)\](.*?)\[\/url\]/iu';
		$replacements[] = '<a href="\\1\\2" rel="external nofollow" target="_blank">\\3</a>';

		$patterns[]     = '/\[url=([^\s<\"\'\]]*?)\](.*?)\[\/url\]/iu';
		$replacements[] = '<a href="http://\\1" rel="external nofollow" target="_blank">\\2</a>'; // TODO Support https

		$patterns[]     = '#\[url\](.*?)\[\/url\]#iu';
		$replacements[] = '\\1';

		// EMAIL
		$patterns[]     = '~\[email=(.*?)\](.*?)\[\/email\]~isu';
		$replacements[] = '\\1';
		$patterns[]     = '~\[email\]([^\s\<\>\(\)\"\'\[\]]*?)\[\/email\]~isu';
		$replacements[] = '\\1';

		// IMG
		$patterns[]     = '#\[img\]([a-z]*\:\/\/)([^\s\<\>\(\)\"\']*?)\[\/img\]#iu';
		$replacements[] = '<img class="img" src="\\1\\2" alt="" style="border:none;" />';

		$patterns[]     = '#\[img\](.*?)\[\/img\]#iu';
		$replacements[] = '\\1';

		// HIDE
		$patterns[] = '/\[hide\](.*?)\[\/hide\]/iu';

		if (!Factory::getApplication()->getIdentity()->get('guest'))
		{
			$replacements[] = '<span class="badge text-bg-light hide">\\1</span>';
		}
		else
		{
			$replacements[] = '<span class="badge text-bg-light hide">' . Text::_('BBCODE_MESSAGE_HIDDEN_TEXT') . '</span>';
		}

		/*
		 * CODE
		 * Match programming language name in lower case and can contain symbols: #, ., +, !, --, ++, *, /.
		 * See https://en.wikipedia.org/wiki/List_of_programming_languages
		*/
		$codePattern    = '#\[code=?([\p{L}0-9\#\.\+\!\-\-\+\+\*\/]*?)](.*?)\[/code]#ismu';
		$patterns[]     = $codePattern;
		$replacements[] = '<figure class="codeblock">
			<figcaption class="code">' . Text::_('COMMENT_TEXT_CODE') . '</figcaption>
			<pre class="card card-body p-2"><code class="lang-\\1">\\2</code></pre>
		</figure>';

		$str = preg_replace_callback(
			$codePattern,
			function ($matches)
			{
				$text = htmlspecialchars(trim($matches[0]));
				$text = str_replace("\r", '', $text);

				return str_replace("\n", '<br />', $text);
			},
			$str
		);
		$str = preg_replace($patterns, $replacements, $str);

		// QUOTE
		// Extended quote with authors name
		$quotePattern = '#\[quote\s?name=\"([^\"\<\>\(\)]*?)\"](<br\s?/?>)*?(.*?)(<br\s?/?>)*\[/quote](<br\s?/?>)*?#ismu';
		$quoteReplace = '<blockquote class="blockquote">
			<span class="cite d-block">' . Text::_('COMMENT_TEXT_QUOTE') . '<span class="author fst-italic fw-semibold">\\1</span></span>\\3
		</blockquote>';

		while (preg_match($quotePattern, $str))
		{
			// TODO Добавить поддержку значения аттрибута name вида author_name;comment_id
			$str = preg_replace($quotePattern, $quoteReplace, $str);
		}

		// Simple quote
		$quotePattern = '#\[quote[^]]*?](<br\s?/?>)*([^\[]+)(<br\s?/?>)*\[/quote](<br\s?/?>)*?#ismUu';
		$quoteReplace = '<blockquote class="blockquote">\\2</blockquote>';

		while (preg_match($quotePattern, $str))
		{
			$str = preg_replace($quotePattern, $quoteReplace, $str);
		}

		// LIST
		$matches    = array();
		$matchCount = preg_match_all('#\[list](<br\s?/?>)*(.*?)(<br\s?/?>)*\[/list]#iu', $str, $matches);

		for ($i = 0; $i < $matchCount; $i++)
		{
			$textBefore = preg_quote($matches[2][$i]);
			$textAfter  = preg_replace('#(<br\s?/?>)*\[\*\](<br\s?/?>)*#isu', "</li><li>", $matches[2][$i]);
			$textAfter  = preg_replace('#^</?li>#u', '', $textAfter);
			$textAfter  = str_replace("\n</li>", "</li>", $textAfter . "</li>");
			$str        = preg_replace('#\[list](<br\s?/?>)*' . $textBefore . '(<br\s?/?>)*\[/list]#isu', "<ul>$textAfter</ul>", $str);
		}

		$matches    = array();
		$matchCount = preg_match_all('#\[list=(a|A|i|I|1)\](<br\s?/?>)*(.*?)(<br\s?/?>)*\[/list]#isu', $str, $matches);

		for ($i = 0; $i < $matchCount; $i++)
		{
			$textBefore = preg_quote($matches[3][$i]);
			$textAfter  = preg_replace('#(<br\s?/?>)*\[\*\](<br\s?/?>)*#isu', "</li><li>", $matches[3][$i]);
			$textAfter  = preg_replace('#^</?li>#u', '', $textAfter);
			$textAfter  = str_replace("\n</li>", "</li>", $textAfter . "</li>");
			$str        = preg_replace(
				'#\[list=(a|A|i|I|1)\](<br\s?\/?\>)*' . $textBefore . '(<br\s?\/?\>)*\[/list\]#isu',
				"<ol type=\\1>$textAfter</ol>",
				$str
			);
		}

		mt_srand(ComponentHelper::makeSeed());

		// Spoiler tag
		$str = preg_replace_callback(
			'~\[spoiler((=)(.*?))?](<br\s?/?>)*?(.*?)(<br\s?/?>)*\[/spoiler](<br\s?/?>)*?~ismu',
			function ($matches)
			{
				if (empty($matches[5]))
				{
					return '';
				}

				$linkText  = !empty($matches[3]) ? $matches[3] : 'Spoiler title';
				$randValue = rand(0, 1000);
				$spoilerId = 'spoiler' . $randValue;

				return '<div class="my-1 spoiler">
					<a class="my-1 text-start btn btn-sm btn-light d-block spoiler-link" data-bs-toggle="collapse"
					   href="#' . $spoilerId . '" role="button" aria-expanded="false"
					   aria-controls="' . $spoilerId . '">' . $linkText . '</a>
					<div class="spoiler-card collapse" id="' . $spoilerId . '"><div class="card card-body">' . $matches[5] . '</div></div>
				</div>';
			},
			$str
		);

		$str = preg_replace('~\[/?(' . implode('|', $this->get()) . ')]~iu', '', $str);
		ob_end_clean();

		return $str;
	}

	public function removeQuotes($text)
	{
		$text = preg_replace(array('#\n?\[quote.*?].+?\[/quote]\n?#isu', '#\[/quote]#isu'), '', $text);

		return preg_replace('#<br\s?/?>+#is', '', $text);
	}

	public function removeHidden($text)
	{
		$text = preg_replace('#\[hide](.*?)\[/hide]#isu', '', $text);

		return preg_replace('#<br\s?/?>+#is', '', $text);
	}
}
