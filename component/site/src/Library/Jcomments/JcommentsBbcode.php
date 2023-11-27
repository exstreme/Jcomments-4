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
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;
use Joomla\Filter\InputFilter;
use Joomla\String\StringHelper;

/**
 * JComments BBCode
 *
 * @since  3.0
 */
class JcommentsBbcode
{
	/**
	 * Array of allowed bbcode attributes for some tags
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $allowedAttrs = array(
		'img' => array('width', 'height', 'class')
	);

	/**
	 * Array of bbcodes
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $codes = array();

	/**
	 * Array of custom bbcodes
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $customCodes = array();

	/**
	 * Array of custom filter patterns
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $customFilterPatterns = array();

	/**
	 * Array of custom html replacements
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $customHtmlReplacements = array();

	/**
	 * Array of custom bbcode patterns
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $customPatterns = array();

	/**
	 * Array of custom text replacements
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $customTextReplacements = array();

	/**
	 * Array of bbcode patterns
	 *
	 * @var    array
	 * @since  4.1
	 */
	protected $patterns = array(
		'b'       => '~\[b](.*?)\[/b]~iu',
		'i'       => '~\[i](.*?)\[/i]~iu',
		'u'       => '~\[u](.*?)\[/u]~iu',
		's'       => '~\[s](.*?)\[/s]~iu',
		'sup'     => '~\[sup](.*?)\[/sup]~iu',
		'sub'     => '~\[sub](.*?)\[/sub]~iu',
		'left'    => '~\[left](.*?)\[/left]~iu',
		'center'  => '~\[center](.*?)\[/center]~iu',
		'right'   => '~\[right](.*?)\[/right]~iu',
		'justify' => '~\[justify](.*?)\[/justify]~iu',
		'font'    => '~\[font=(.*?)](.*?)\[/font]~iu',
		'size'    => '~\[size=(\d+)](.*?)\[/size]~iu',
		'color'   => '~\[color=(\#[\da-f]{3}|\#[\da-f]{6}|'
			. 'rgba\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|'
			. 'hsla\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|'
			. 'rgb\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)|'
			. 'hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\))](.*?)\[/color]~iu',
		'hr'      => '~\[hr]~iu',
		'ltr'     => '~\[ltr](.*?)\[/ltr]~iu',
		'rtl'     => '~\[rtl](.*?)\[/rtl]~iu',
		'email'   => array('~\[email=(.*?)](.*?)\[/email]~isu', '~\[email]([^\s\<\>\(\)\"\'\[\]]*?)\[/email]~isu'),
		'hide'    => '~\[hide](.*?)\[/hide]~isu',
		'code'    => '~\[code=?([\p{L}0-9\#\.\+\!\-\-\+\+\*\/]*?)](.*?)\[/code]~ismu',
		'img'     => '~\[img(.*?)?](https?|ftp|www)(.*?)\[/img]~iu',
		'url'     => '~\[url(?|=[\'"]?([^]"\']+)[\'"]?]([^[]+)|](([^[]+)))\[/url]~isu',
		'list'    => array(
			'type1' => '~\[list](<br\s?/?>)*(.*?)(<br\s?/?>)*\[/list]~iu',
			'type2' => '~\[list=(a|A|i|I|1)](<br\s?/?>)*(.*?)(<br\s?/?>)*\[/list]~isu',
			'li'    => '~(<br\s?/?>)*\[\*](<br\s?/?>)*~iu'
		),
		'table'   => '~\[table(\s+.*?)?](.*?)\[/table](<br\s?/?>)*?~isu',
		'spoiler' => '~\[spoiler((=)(.*?))?](<br\s?/?>)*?(.*?)(<br\s?/?>)*\[/spoiler](<br\s?/?>)*?~ismu',
		'quote'   => '~\[quote\s?(name=\"?(.*?)\"?)?](<br\s?/?>)*?(.*?)(<br\s?/?>)*?\[/quote](<br\s?/?>)*?~ismu',
		'youtube' => '~\[youtube\]https\:\/\/www\.youtube\.com\/watch\?v\=([A-Za-z0-9-_]+)([A-Za-z0-9\%\&\=\#]*?)\[\/youtube\]~ismu'
	);

	/**
	 * Pattern to validate URL
	 *
	 * @var    string
	 * @since  4.1
	 * @see    https://stackoverflow.com/a/41132408
	 */
	protected $urlPattern = "/^([a-z][a-z0-9+.-]*):(?:\\/\\/((?:(?=((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9A-F]{2})*))(\\3)@)?(?=(\\[[0-9A-F:.]{2,}\\]|(?:[a-z0-9-._~!$&'()*+,;=]|%[0-9A-F]{2})*))\\5(?::(?=(\\d*))\\6)?)(\\/(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/]|%[0-9A-F]{2})*))\\8)?|(\\/?(?!\\/)(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/]|%[0-9A-F]{2})*))\\10)?)(?:\\?(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/?]|%[0-9A-F]{2})*))\\11)?(?:#(?=((?:[a-z0-9-._~!$&'()*+,;=:@\\/?]|%[0-9A-F]{2})*))\\12)?$/iu";

	/**
	 * Initialize all bbcodes
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		$this->codes = self::getStandardCodes();
		$this->getCustomBbcodes();
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
	 * @todo    Validate or sanitize tag attributes value
	 */
	public function filter(string $str, bool $forceStrip = false): ?string
	{
		ob_start();

		$filter       = new InputFilter;
		$patterns     = array();
		$replacements = array();

		// Empty tags
		foreach ($this->codes as $code => $enabled)
		{
			$patterns[]     = '/\[' . $code . '\]\[\/' . $code . '\]/iu';
			$replacements[] = '';
		}

		// B - [b][/b]
		if ((!$this->codes['b']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['b'];
			$replacements[] = '\\1';
		}

		// I - [i][/i]
		if ((!$this->codes['i']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['i'];
			$replacements[] = '\\1';
		}

		// U - [u][/u]
		if ((!$this->codes['u']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['u'];
			$replacements[] = '\\1';
		}

		// S - [s][/s]
		if ((!$this->codes['s']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['s'];
			$replacements[] = '\\1';
		}

		// SUP - [sup][/sup]
		if ((!$this->codes['sup']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['sup'];
			$replacements[] = '\\1';
		}

		// SUB - [sub][/sub]
		if ((!$this->codes['sub']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['sub'];
			$replacements[] = '\\1';
		}

		// LEFT - [left][/left]
		if ((!$this->codes['left']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['left'];
			$replacements[] = '\\1';
		}

		// CENTER - [center][/center]
		if ((!$this->codes['center']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['center'];
			$replacements[] = '\\1';
		}

		// RIGHT - [right][/right]
		if ((!$this->codes['right']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['right'];
			$replacements[] = '\\1';
		}

		// JUSTIFY - [justify][/justify]
		if ((!$this->codes['justify']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['justify'];
			$replacements[] = '\\1';
		}

		// FONT - [font=font name][/font]
		if ((!$this->codes['font']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['font'];
			$replacements[] = '\\2';
		}

		// SIZE - [size=value][/size]
		if ((!$this->codes['size']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['size'];
			$replacements[] = '\\2';
		}

		// YOUTUBE - [youtube][/youtube]
		if ((!$this->codes['youtube']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['youtube'];
			$replacements[] = 'https://www.youtube.com/watch?v=\\1';
		}

		/**
		 * COLOR - [color=value][/color] - where value can be,
		 * i.e. #RGB, #RRGGBB, hsl(0, 100%, 100%), rgba(170,221,255,0.59), hsla(208, 56%, 46%, 1).
		 * Source: https://stackoverflow.com/a/43706299
		 */
		if ((!$this->codes['color']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['color'];
			$replacements[] = '\\32';
		}

		// Horizontal rule - [hr]
		if ((!$this->codes['hr']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['hr'];
			$replacements[] = '';
		}

		// LTR - [ltr][/ltr]
		if ((!$this->codes['ltr']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['ltr'];
			$replacements[] = '\\1';
		}

		// RTL - [rtl][/rtl]
		if ((!$this->codes['rtl']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['rtl'];
			$replacements[] = '\\1';
		}

		// EMAIL - [email=user@domain]title[/email] or [email]user@domain[/email]
		if ((!$this->codes['email']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['email'][0];
			$replacements[] = '\\1';
			$patterns[]     = $this->patterns['email'][1];
			$replacements[] = '\\1';
		}

		// HIDE - [hide][/hide]
		if ((!$this->codes['hide']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['hide'];
			$replacements[] = Factory::getApplication()->getIdentity()->get('id') ? '\\1' : '';
		}

		/*
		 * CODE - [code=language][/code]
		 * Match programming language name in lower case and can contain symbols: #, ., +, !, --, ++, *, /.
		 * See https://en.wikipedia.org/wiki/List_of_programming_languages
		*/
		if ((!$this->codes['code']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['code'];
			$replacements[] = '\\2';
		}

		// IMG - [img]image link[/img], [img attribue=value attribute1=value1]image link[/img]
		if ((!$this->codes['img']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['img'];
			$replacements[] = PunycodeHelper::urlToPunycode('\\2\\3');
		}

		// URLs - [url][/url], [url=link][/url]
		if ((!$this->codes['url']) || ($forceStrip))
		{
			$patterns[]     = $this->patterns['url'];
			$replacements[] = '\\2: ' . PunycodeHelper::urlToPunycode('\\1');
		}

		$str = preg_replace($patterns, $replacements, $str);

		// LIST
		if ((!$this->codes['list']) || ($forceStrip))
		{
			/**
			 * LISTs
			 * Unordered list - [list][*]list[*]list[/list]
			 * Ordered list   - [list=A][*]list[*]list[/list]
			 * Nested lists   - [list][*]list[*]list[*][list=1][*]list[*]list[/list][/list]
			 */
			$matches    = array();
			$matchCount = preg_match_all($this->patterns['list']['type1'], $str, $matches);

			for ($i = 0; $i < $matchCount; $i++)
			{
				$textBefore = preg_quote($matches[2][$i]);
				$textAfter  = preg_replace($this->patterns['list']['li'], '<br />', $matches[2][$i]);
				$textAfter  = preg_replace('#^<br />#iu', '', $textAfter);
				$textAfter  = preg_replace('#(<br\s?/?>)+#iu', '<br />', $textAfter);
				$str        = preg_replace(
					'#\[list](<br\s?/?>)*' . $textBefore . '(<br\s?/?>)*\[/list]#isu',
					"\n$textAfter\n",
					$str
				);
			}

			// Typed LISTs
			$matches    = array();
			$matchCount = preg_match_all($this->patterns['list']['type2'], $str, $matches);

			for ($i = 0; $i < $matchCount; $i++)
			{
				$textBefore = preg_quote($matches[3][$i]);
				$textAfter  = preg_replace($this->patterns['list']['li'], '<br />', $matches[3][$i]);
				$textAfter  = preg_replace('#^<br />#u', "", $textAfter);
				$textAfter  = preg_replace('#(<br\s?/?>)+#u', '<br />', $textAfter);
				$str        = preg_replace(
					'#\[list=(a|A|i|I|1)](<br\s?/?>)*' . $textBefore . '(<br\s?/?>)*\[/list]#isu',
					"\n$textAfter\n",
					$str
				);
			}
		}

		// Tables - [table][tr][td]row cell[/td][/tr][/table]
		if ((!$this->codes['table']) || ($forceStrip))
		{
			$tablePattern = $this->patterns['table'];

			while (preg_match($tablePattern, $str))
			{
				$str = preg_replace_callback(
					$tablePattern,
					function ($matches) use ($filter) {
						return preg_replace_callback(
							'~\[tr](.*?)\[/tr]~isux',
							function ($match)
							{
								return preg_replace('~\[td](.*?)\[/td]~isux', "\\1\n", $match[1]);
							},
							$matches[2]
						);
					},
					$str
				);
			}
		}

		// Spoiler tag - [spoiler][/spoiler] or [spoiler=title]text[/spoiler]
		if ((!$this->codes['spoiler']) || ($forceStrip))
		{
			$spoilerPattern = $this->patterns['spoiler'];

			while (preg_match($spoilerPattern, $str))
			{
				$str = preg_replace_callback(
					$spoilerPattern,
					function ($matches)
					{
						return empty($matches[5]) ? '' : $matches[5];
					},
					$str
				);
			}
		}

		if ($forceStrip)
		{
			// Quote - [quote][/quote], [quote name=author][/quote], [quote name=author;postid][/quote]
			$quotePattern = $this->patterns['quote'];
			$quoteReplace = ' ';

			while (preg_match($quotePattern, $str))
			{
				$str = preg_replace($quotePattern, $quoteReplace, $str);
			}

			// Remove starting and/or ending bbcode tags in nested tags
			$str = preg_replace(
				'~\[/?(' . implode('|', array_keys($this->codes)) . '|tr|td)]~iu',
				'',
				$str
			);
		}

		$str = trim(preg_replace('#( ){4,}#iu', '\\1', $str));

		ob_end_clean();

		return $str;
	}

	/**
	 * Filter custom BBCode
	 *
	 * @param   string   $str         Comment text
	 * @param   boolean  $forceStrip  Force to delete the code.
	 *
	 * @return  string|null
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function filterCustom(string $str, bool $forceStrip = false): ?string
	{
		if (count($this->customFilterPatterns))
		{
			ob_start();
			$str = preg_replace($this->customFilterPatterns, $this->customTextReplacements, $str);
			ob_end_clean();
		}

		if ($forceStrip === true)
		{
			ob_start();
			$str = preg_replace($this->customPatterns, $this->customTextReplacements, $str);
			ob_end_clean();
		}

		return $str;
	}

	/**
	 * Getter for custom bbcodes.
	 *
	 * @return  array
	 *
	 * @since   4.1
	 */
	public function getCustomBbcodesList(): array
	{
		$codes = array();

		foreach ($this->customCodes as $code)
		{
			$codes[] = $code->tagName;
		}

		return array('codes' => array_filter(array_unique($codes)), 'raw' => $this->customCodes);
	}

	/**
	 * Get all builtin bbcodes with or without separator.
	 *
	 * @return  array  array('code_name' => 'acl value', ...)
	 *
	 * @since   4.1
	 */
	public function getStandardCodes(): array
	{
		$user   = Factory::getApplication()->getIdentity();
		$_codes = array_keys($this->patterns);
		$codes  = array();

		foreach ($_codes as $code)
		{
			$canUse = $user->authorise('comment.bbcode.' . $code, 'com_jcomments');

			if ($canUse)
			{
				$codes[$code] = $canUse;
			}
		}

		return $codes;
	}

	/**
	 * Remove hidden text
	 *
	 * @param   string  $text  Text to clean
	 *
	 * @return  string
	 *
	 * @since   2.5
	 */
	public function removeHidden(string $text): string
	{
		$text = preg_replace($this->patterns['hide'], '', $text);

		return preg_replace('#<br\s?/?>+#i', '', $text);
	}

	/**
	 * Remove nested quotes from input text
	 *
	 * @param   string  $text  Text to clean
	 *
	 * @return  string
	 *
	 * @since   2.5
	 */
	public function removeQuotes(string $text): string
	{
		$text = preg_replace(array('#\n?\[quote.*?].+?\[/quote]\n?#isu', '#\[/quote]#iu'), '', $text);

		return preg_replace('#<br\s?/?>+#i', '', $text);
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

		$input        = Factory::getApplication()->input;
		$filter       = new InputFilter;
		$patterns     = array();
		$replacements = array();

		// B - [b][/b]
		$patterns[]     = $this->patterns['b'];
		$replacements[] = '<strong>\\1</strong>';

		// I - [i][/i]
		$patterns[]     = $this->patterns['i'];
		$replacements[] = '<em>\\1</em>';

		// U - [u][/u]
		$patterns[]     = $this->patterns['u'];
		$replacements[] = '<u>\\1</u>';

		// S - [s][/s]
		$patterns[]     = $this->patterns['s'];
		$replacements[] = '<span class="text-decoration-line-through">\\1</span>';

		// SUP - [sup][/sup]
		$patterns[]     = $this->patterns['sup'];
		$replacements[] = '<sup>\\1</sup>';

		// SUB - [sub][/sub]
		$patterns[]     = $this->patterns['sub'];
		$replacements[] = '<sub>\\1</sub>';

		// LEFT - [left][/left]
		$patterns[]     = $this->patterns['left'];
		$replacements[] = '<p class="text-start">\\1</p>';

		// CENTER - [center][/center]
		$patterns[]     = $this->patterns['center'];
		$replacements[] = '<p class="text-center">\\1</p>';

		// RIGHT - [right][/right]
		$patterns[]     = $this->patterns['right'];
		$replacements[] = '<p class="text-end">\\1</p>';

		// JUSTIFY - [justify][/justify]
		$patterns[]     = $this->patterns['justify'];
		$replacements[] = '<p style="text-align:justify;">\\1</p>';

		// FONT - [font=font name][/font]
		$patterns[]     = $this->patterns['font'];
		$replacements[] = '<span style="font-family:\'\\1\', sans-serif;" class="f-face">\\2</span>';

		// SIZE - [size=value][/size]
		$patterns[]     = $this->patterns['size'];
		$replacements[] = '<span class="fs-\\1">\\2</span>';

		// YOUTUBE - [youtube][/youtube]
		$patterns[]     = $this->patterns['youtube'];
		$replacements[] = '<iframe width="425" height="350" src="//www.youtube.com/embed/\\1?rel=0" frameborder="0" allowfullscreen></iframe>';

		/**
		 * COLOR - [color=value][/color] - where value can be,
		 * i.e. #RGB, #RRGGBB, hsl(0, 100%, 100%), rgba(170,221,255,0.59), hsla(208, 56%, 46%, 1).
		 * Source: https://stackoverflow.com/a/43706299
		 */
		$patterns[]     = $this->patterns['color'];
		$replacements[] = '<span style="color:\\1;">\\32</span>';

		// Horizontal rule - [hr]
		$patterns[]     = $this->patterns['hr'];
		$replacements[] = '<hr>';

		// LTR - [ltr][/ltr]
		$patterns[]     = $this->patterns['ltr'];
		$replacements[] = '<div style="direction: ltr;">\\1</div>';

		// RTL - [rtl][/rtl]
		$patterns[]     = $this->patterns['rtl'];
		$replacements[] = '<div style="direction: rtl;">\\1</div>';

		// EMAIL - [email=user@domain]title[/email] or [email]user@domain[/email]
		$patterns[]     = $this->patterns['email'][0];
		$replacements[] = '\\1';
		$patterns[]     = $this->patterns['email'][1];
		$replacements[] = '\\1';

		// HIDE - [hide][/hide]
		$patterns[] = $this->patterns['hide'];

		if (!Factory::getApplication()->getIdentity()->get('guest'))
		{
			$replacements[] = '<span class="badge text-bg-light hide">\\1</span>';
		}
		else
		{
			$replacements[] = '<span class="badge text-bg-light hide">' . Text::_('BBCODE_MESSAGE_HIDDEN_TEXT') . '</span>';
		}

		/*
		 * CODE - [code=language][/code]
		 * Match programming language name in lower case and can contain symbols: #, ., +, !, --, ++, *, /.
		 * See https://en.wikipedia.org/wiki/List_of_programming_languages
		*/
		$codePattern    = $this->patterns['code'];
		$patterns[]     = $codePattern;
		$replacements[] = '<figure class="codeblock">
			<figcaption class="code">' . Text::_('COMMENT_TEXT_CODE') . '</figcaption>
			<pre class="line-numbers card card-body p-2"><code class="lang-\\1">\\2</code></pre>
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

		/*
		 * IMG - [img]image link[/img], [img=WIDTHxHEIGHT]image link[/img]
		 *
		 * [img attribue=value attribute1=value1]image link[/img] bbcode is parsed but not supported by the editor.
		*/
		$str = preg_replace_callback(
			$this->patterns['img'],
			function ($matches) use ($filter) {
				$url = PunycodeHelper::urlToPunycode($matches[2] . $matches[3]);

				if (!preg_match($this->urlPattern, $url))
				{
					return $filter->clean($url);
				}
				else
				{
					preg_match_all('~(\w+)=["|\']?(\w+)["|\']?~', $matches[1], $match);
					$attrs = array();

					// Attribute found
					if (!empty($match[1]))
					{
						// Loop through attributes and sanitize their values.
						foreach ($match[1] as $key => $attrName)
						{
							if (!empty($attrName) && in_array($attrName, $this->allowedAttrs['img']))
							{
								$attrs[] = trim($attrName) . '="' . trim($filter->clean($match[2][$key])) . '"';
							}
						}
					}
					// Non-standard value =WIDTHxHEIGHT
					else
					{
						preg_match('~=["|\']?(\d+)x(\d+)["|\']?~', $matches[1], $match);

						if (!empty($match[1]))
						{
							$attrs[] = 'width="' . (int) $match[1] . '"';
						}

						if (!empty($match[2]))
						{
							$attrs[] = 'height="' . (int) $match[2] . '"';
						}
					}

					return '<img src="' . $url . '"' . (!empty($attrs) ? ' ' . implode(' ', $attrs) : '') . '>';
				}
			},
			$str
		);

		// URLs - [url][/url], [url=link][/url]
		$str = preg_replace_callback(
			$this->patterns['url'],
			function ($matches) use ($filter) {
				$url = PunycodeHelper::urlToPunycode($matches[1]);

				if (!preg_match($this->urlPattern, $url))
				{
					// Return url as string.
					return $filter->clean($matches[1]);
				}
				else
				{
					return '<a href="' . $url . '" target="_blank">' . $matches[2] . '</a>';
				}
			},
			$str
		);

		/**
		 * LISTs
		 * Unordered list - [list][*]list[*]list[/list]
		 * Ordered list   - [list=A][*]list[*]list[/list]
		 * Nested lists   - [list][*]list[*]list[*][list=1][*]list[*]list[/list][/list]
		 */
		$matches    = array();
		$matchCount = preg_match_all($this->patterns['list']['type1'], $str, $matches);

		for ($i = 0; $i < $matchCount; $i++)
		{
			$textBefore = preg_quote($matches[2][$i]);
			$textAfter  = preg_replace($this->patterns['list']['li'], "</li><li>", $matches[2][$i]);
			$textAfter  = preg_replace('#^</?li>#u', '', $textAfter);
			$textAfter  = str_replace("\n</li>", "</li>", $textAfter . "</li>");
			$str        = preg_replace('#\[list](<br\s?/?>)*' . $textBefore . '(<br\s?/?>)*\[/list]#isu', "<ul>$textAfter</ul>", $str);
		}

		// Typed LISTs
		$matches    = array();
		$matchCount = preg_match_all($this->patterns['list']['type2'], $str, $matches);

		for ($i = 0; $i < $matchCount; $i++)
		{
			$textBefore = preg_quote($matches[3][$i]);
			$textAfter  = preg_replace($this->patterns['list']['li'], "</li><li>", $matches[3][$i]);
			$textAfter  = preg_replace('#^</?li>#u', '', $textAfter);
			$textAfter  = str_replace("\n</li>", "</li>", $textAfter . "</li>");
			$str        = preg_replace(
				'#\[list=(a|A|i|I|1)](<br\s?/?>)*' . $textBefore . '(<br\s?/?>)*\[/list]#isu',
				"<ol type=\\1>$textAfter</ol>",
				$str
			);
		}

		// Tables - [table][tr][td]row cell[/td][/tr][/table]
		$tablePattern = $this->patterns['table'];

		while (preg_match($tablePattern, $str))
		{
			$str = preg_replace_callback(
				$tablePattern,
				function ($matches) use ($filter) {
					$tr = preg_replace_callback(
						'~\[tr](.*?)\[/tr]~isux',
						function ($match)
						{
							$td = preg_replace('~\[td](.*?)\[/td]~isux', '<td>\\1</td>', $match[1]);

							return '<tr>' . $td . '</tr>';
						},
						$matches[2]
					);

					preg_match('~^\s+?class=[\'"]?([^]"\']+)[\'"]?$~', $matches[1], $className);
					$className = !empty($className[1]) ? ' ' . $className[1] : '';

					return '<table class="table' . $filter->clean($className) . '">' . $tr . '</table>';
				},
				$str
			);
		}

		mt_srand(ComponentHelper::makeSeed());

		// Spoiler tag - [spoiler][/spoiler] or [spoiler=title]text[/spoiler]
		$spoilerPattern = $this->patterns['spoiler'];

		while (preg_match($spoilerPattern, $str))
		{
			$str = preg_replace_callback(
				$spoilerPattern,
				function ($matches) use ($filter) {
					if (empty($matches[5]))
					{
						return '';
					}

					$title     = $filter->clean($matches[3]);
					$title     = !empty($title) ? $title : Text::_('BBCODE_MESSAGE_SPOLIER');
					$randValue = rand(0, 1000);
					$spoilerId = 'spoiler' . $randValue;

					return '<div class="my-1 spoiler">
						<a class="my-1 text-start btn btn-sm btn-outline-info d-block spoiler-link" data-bs-toggle="collapse"
						   href="#' . $spoilerId . '" role="button" aria-expanded="false" aria-controls="' . $spoilerId . '"
						   title="' . Text::_('BBCODE_MESSAGE_SPOLIER') . '">' . $title . '</a>
						<div class="spoiler-card border rounded collapse" id="' . $spoilerId . '">
							<div class="p-2">' . $matches[5] . '</div>
						</div>
					</div>';
				},
				$str
			);
		}

		// Quote - [quote][/quote], [quote name=author][/quote], [quote name=author;postid][/quote]
		$quotePattern = $this->patterns['quote'];
		$view = $input->getWord('view');

		while (preg_match($quotePattern, $str))
		{
			$str = preg_replace_callback(
				$quotePattern,
				function ($matches) use ($view) {
					$parentLink = '';
					$dataQuoted = '';

					if (!empty($matches[2]))
					{
						$separatorPos = mb_strripos($matches[2], ';');

						if ($separatorPos !== false)
						{
							$name = StringHelper::substr($matches[2], 0, $separatorPos);
							$quoteId = (int) StringHelper::substr($matches[2], $separatorPos + 1);
						}
						else
						{
							$name = $matches[2];
							$quoteId = null;
						}

						if (!empty($quoteId))
						{
							// Custom html is required for editor and comment views
							if ($view != 'form')
							{
								if (!empty($matches[4]))
								{
									$parentLink = '&nbsp;<a href="' . Route::_('index.php?option=com_jcomments&task=comments.goto&object_id=2&object_group=com_content&id=' . $quoteId, false) . '#comment-item-' . $quoteId . '"
										class="quote-parent-link"
										data-id="' . $quoteId . '"><span class="fa icon-arrow-right-4" aria-hidden="true"></span></a>';
								}
							}

							$dataQuoted = ' data-quoted="' . $quoteId . '"';
						}

						return '<blockquote class="blockquote"' . $dataQuoted . '>
							<span class="cite d-block">' . Text::_('COMMENT_TEXT_QUOTE') . '<span class="author fst-italic fw-semibold">' . $name . '</span>' . $parentLink . '</span>' . $matches[4] . '
						</blockquote>';
					}
					else
					{
						return '<blockquote class="blockquote">' . $matches[4] . '</blockquote>';
					}
				},
				$str
			);
		}

		// Remove the codes from the list of standard codes that are present in the list of additional codes.
		$deleteCodes = array_udiff(array_keys($this->codes), self::getCustomBbcodesList()['codes'], 'strcasecmp');

		// Remove starting and/or ending bbcode tags.
		$str = preg_replace('#\[/?(' . implode('|', array_values($deleteCodes)) . '|tr|td)]#iu', '', $str);

		ob_end_clean();

		return $str;
	}

	/**
	 * BBCode replacement with html for custom bbcodes
	 *
	 * @param   string   $str              Comment text
	 * @param   boolean  $textReplacement  Replace with HTML or text
	 *
	 * @return  string|null
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function replaceCustom(string $str, bool $textReplacement = false): ?string
	{
		if (count($this->customPatterns))
		{
			ob_start();
			$str = preg_replace(
				$this->customPatterns,
				($textReplacement ? $this->customTextReplacements : $this->customHtmlReplacements),
				$str
			);
			ob_end_clean();
		}

		return $str;
	}

	/**
	 * Get all custom bbcodes filtered by ACL rules
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	private function getCustomBbcodes()
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$acl    = JcommentsFactory::getACL();
		$params = ComponentHelper::getParams('com_jcomments');

		if (!$params->get('enable_custom_bbcode'))
		{
			return;
		}

		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'id', 'name', 'simple_pattern', 'simple_replacement_html', 'simple_replacement_text', 'pattern',
						'replacement_html', 'replacement_text', 'button_acl', 'button_open_tag', 'button_close_tag',
						'button_title', 'button_image', 'button_enabled'
					)
				)
			)
			->from($db->quoteName('#__jcomments_custom_bbcodes'))
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('ordering') . ' ASC');

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

		foreach ($codes as $code)
		{
			$canUse        = $acl->enableCustomBBCode($code->button_acl);
			$code->canUse  = $canUse;
			$code->tagName = strtolower(str_replace(array('[', ']'), '', $code->button_open_tag));

			// Check code permission
			if ($canUse)
			{
				if ($code->button_image != '')
				{
					if (strpos($code->button_image, Uri::base()) === false)
					{
						$code->button_image = Uri::base() . trim($code->button_image, '/');
					}
				}

				$this->customCodes[] = $code;
			}
			else
			{
				$this->customFilterPatterns[] = '#' . $code->pattern . '#ismu';
			}

			$this->customPatterns[]         = '#' . $code->pattern . '#ismu';
			$this->customHtmlReplacements[] = $code->replacement_html;
			$this->customTextReplacements[] = $code->replacement_text;
		}
	}
}
