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

namespace Joomla\Component\Jcomments\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ToolbarHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\String\StringHelper;

/**
 * Jcomments Editor Field class.
 *
 * @since  4.1
 */
class JceditorField extends TextareaField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  4.1
	 */
	protected $type = 'Jceditor';

	/**
	 * The height of the editor.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $height;

	/**
	 * The width of the editor.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $width;

	/**
	 * CSS for custom bbcode buttons.
	 *
	 * @var    string
	 * @since  4.1
	 */
	protected $customButtonsCSS = '';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     FormField::setup()
	 * @since   3.2
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$result = parent::setup($element, $value, $group);

		if ($result === true)
		{
			$this->height = $this->element['height'] ? (string) $this->element['height'] : '500';
			$this->width = $this->element['width'] ? (string) $this->element['width'] : '100%';
		}

		return $result;
	}

	/**
	 * Method to get the textarea field input markup.
	 * Use the rows and columns attributes to specify the dimensions of the area.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$doc    = $app->getDocument();
		$params = ComponentHelper::getParams('com_jcomments');

		/** @var WebAssetManager $wa */
		$wa          = $doc->getWebAssetManager();
		$langTag     = $app->getLanguage()->getTag();
		$_lang       = in_array($langTag, array('en-US', 'pt-BR')) ? $langTag : strtolower(substr($langTag, -2));
		$emoticons   = JcommentsFactory::getSmilies()->getList();
		$format      = $params->def('editor_format', 'bbcode');
		$plugins     = array('autoyoutube', 'undo', 'emoji');
		$buttons     = (string) $this->element['buttons'];
		$editorTheme = File::makeSafe($params->get('editor_theme'));
		$editorIcons = File::makeSafe($params->get('editor_theme_icons'));
		$themeUrl    = Uri::root() . 'media/com_jcomments/images/tmpl/editor/' . $editorTheme;

		// Override standart buttons list with buttons from xml attribute.
		if (!empty($buttons))
		{
			$buttons = (object) array_map(
				function ($value)
				{
					return (object) array('btn' => $value);
				},
				explode(',', $buttons)
			);
		}
		else
		{
			$buttons = $params->get('editor_buttons');
		}

		$wa->registerAndUseStyle(
			'jceditor.theme.square',
			'media/com_jcomments/css/editor/' . $editorTheme . '.css',
			array('version' => '4.1.0')
		)->addInlineStyle(
			'.sceditor-button div { background-image: url("' . $themeUrl . '/famfamfam.png"); }
			.sceditor-button-hide div { background-image: url("' . $themeUrl . '/hide.png"); }
			.sceditor-button-spoiler div { background-image: url("' . $themeUrl . '/spoiler.png"); }
			.sceditor-button-emoji div { background-image: url("' . $themeUrl . '/emoji.png"); }'
		)->registerAndUseStyle('jceditor.theme.square.custom', 'media/com_jcomments/css/editor/' . $editorTheme . '-custom.css')
			->useScript('jceditor.core');

		if ($format == 'bbcode')
		{
			$wa->useScript('jceditor.format.bbcode')
				->useScript('jceditor.plg.alternativelist');
			$plugins[] = 'alternative-lists';
		}
		else
		{
			$wa->useScript('jceditor.format.html');
		}

		$wa->useScript('jceditor.plg.autoyoutube')
			->useScript('jceditor.plg.undo')
			->useScript('jceditor.plg.emoji');

		if ($editorIcons !== '')
		{
			$wa->useScript('jceditor.icons.' . $editorIcons);
		}

		$wa->registerAndUseScript('jceditor.lang', 'media/com_jcomments/js/editor/languages/' . $_lang . '.js')
			->registerAndUseScript('jceditor.lang.plugins', 'media/com_jcomments/js/editor/languages/plugins/' . $_lang . '.js');

		$js = $this->generateCustomButtonsJs(JcommentsFactory::getBbcode()->getCustomBbcodesList()['raw'], $editorIcons);

		if ($js > '')
		{
			$wa->addInlineScript(
				"document.addEventListener('DOMContentLoaded', function () {" . $js . "});",
				['name' => 'jceditor.format.custom', 'position' => 'before'],
				[],
				['jceditor.init']
			);
		}

		if ($this->customButtonsCSS > '')
		{
			$wa->addInlineStyle($this->customButtonsCSS);
		}

		$wa->useScript('jceditor.init')
			->registerAndUseScript('twemoji', 'media/com_jcomments/js/twemoji.js', ['version' => '14.1.2']);

		Text::script('COMMENT_TEXT_CODE');
		Text::script('COMMENT_TEXT_QUOTE');
		Text::script('FORM_BBCODE_HIDE');
		Text::script('FORM_BBCODE_SPOILER');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_SHORT');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

		$editorConfig = array(
			'format'        => $format,
			'style'         => Uri::root() . 'media/com_jcomments/css/editor/content/' . File::makeSafe($params->get('custom_css', 'frontend-style') . '.css'),
			'width'         => $this->width,
			'height'        => $this->height,
			'icons'         => $editorIcons === '' ? null : $editorIcons,
			'locale'        => $langTag,
			'toolbar'       => ToolbarHelper::buildToolbar($buttons),
			'emoticonsRoot' => Uri::root() . $params->get('smilies_path'),
			'plugins'       => implode(',', $plugins),
			'autoUpdate'    => true,
			'minlength'     => $user->get('isRoot') ? 0 : $params->get('comment_minlength'),
			'maxlength'     => $user->get('isRoot') ? 0 : $params->get('comment_maxlength'),
			'emoji'         => (object) array(
				'enable' => true,
				//'excludeEmojis' => '1FAE8,1FA77,1FA75,1FA76,1FAF7,1FAF8,1FACE,1FACF,1FABD,1FABF,1FABC,1FABB,1FADA,1FADB,1FAAD,1FAAE,1FA87,1FA88,1FAAF,1F6DC',
				//'closeAfterSelect' => false,
				/*'twemoji' => (object) array(
					'base' => Uri::base() . 'media/com_jcomments/images/',
					'folder' => 'svg',
					'ext' => '.svg'
				)*/
			)
		);

		if (!empty($emoticons))
		{
			$editorConfig['emoticons'] = $emoticons;
		}
		else
		{
			// Delete emoticon button from editor toolbar
			$editorConfig['toolbar'] = str_replace(array('emoticon,', 'emoticon|'), '', $editorConfig['toolbar']);
		}

		$doc->addScriptOptions('jceditor', $editorConfig);

		return parent::getInput();
	}

	/**
	 * Generate javascript for cutom bbcode buttons
	 *
	 * @param   array   $buttons   Array with buttons
	 * @param   string  $iconFile  Filename with icon file
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private function generateCustomButtonsJs(array $buttons, string $iconFile): string
	{
		ob_start();

		$params = ComponentHelper::getParams('com_jcomments');
		$js = '';

		foreach ($buttons as $button)
		{
			if (!$button->button_enabled)
			{
				continue;
			}

			$button->tagName        = strip_tags($button->tagName);
			$button->buttonTitle    = addslashes($button->button_title);
			$button->buttonOpenTag  = strip_tags($button->button_open_tag);
			$button->buttonCloseTag = strip_tags($button->button_close_tag);

			if ($button->button_image > '')
			{
				$js .= "";

				if (strpos($button->button_image, '{editor_theme}') !== false)
				{
					$button->button_image = StringHelper::str_ireplace('{editor_theme}', $params->get('editor_theme'), $button->button_image);
				}

				$this->customButtonsCSS .= '.sceditor-button-' . $button->tagName . ' div { background: url("' . $button->button_image . '") no-repeat center center !important; }';
			}
			else
			{
				// Fix icon position for different icons theme.
				if ($iconFile == 'material')
				{
					$js .= "sceditor.icons.material.icons." . $button->tagName . " = '<text x=\"12\" y=\"16\" fill=\"#000000\" font-size=\"1em\" text-anchor=\"middle\" style=\"line-height:0\" xml:space=\"preserve\">" . StringHelper::substr($button->buttonTitle, 0, 2) . "</text>';";
				}
				elseif ($iconFile == 'monocons')
				{
					$js .= "sceditor.icons.monocons.icons." . $button->tagName . " = '<text x=\"8\" y=\"12\" fill=\"#000000\" font-size=\"1em\" text-anchor=\"middle\" style=\"line-height:0\" xml:space=\"preserve\">" . StringHelper::substr($button->buttonTitle, 0, 2) . "</text>';";
				}
			}

			// TODO exec: должно вставлять HTML? Или тегом?
			if ($params->get('editor_format') == 'bbcode')
			{
				$js .= "\n\t\t\tsceditor.command.set('" . $button->tagName . "', {
					exec: function () {
						this.insertText('" . $button->buttonOpenTag . "', '" . $button->buttonCloseTag . "');
					},
					txtExec: function () {
						this.insertText('" . $button->buttonOpenTag . "', '" . $button->buttonCloseTag . "');
					},
					tooltip: '" . $button->buttonTitle . "'
				});\n";
			}
		}

		ob_end_clean();

		return $js;
	}
}
