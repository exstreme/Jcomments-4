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

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ToolbarHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\String\StringHelper;

/**
 * Jcomments Editor Field class.
 *
 * @since  4.1
 * @noinspection PhpUnused
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
	 * The Editor object.
	 *
	 * @var    Editor
	 * @since  3.2
	 */
	protected $editor;

	/**
	 * The editorType of the editor.
	 *
	 * @var    string[]
	 * @since  3.2
	 */
	protected $editorType;

	/**
	 * The assetField of the editor.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $assetField;

	/**
	 * The authorField of the editor.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $authorField;

	/**
	 * The asset of the editor.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $asset;

	/**
	 * The buttons of the editor.
	 *
	 * @var    mixed
	 * @since  3.2
	 */
	protected $buttons;

	/**
	 * The hide of the editor.
	 *
	 * @var    string[]
	 * @since  3.2
	 */
	protected $hide;

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
			$this->height      = $this->element['height'] ? (string) $this->element['height'] : '500';
			$this->width       = $this->element['width'] ? (string) $this->element['width'] : '100%';
			$this->assetField  = $this->element['asset_field'] ? (string) $this->element['asset_field'] : 'asset_id';
			$this->authorField = $this->element['created_by_field'] ? (string) $this->element['created_by_field'] : 'username';
			$this->asset       = $this->form->getValue($this->assetField) ?: (string) $this->element['asset_id'];

			$buttons    = (string) $this->element['buttons'];
			$hide       = (string) $this->element['hide'];
			$editorType = (string) $this->element['editor'];
			$params     = JcommentsComponentHelper::getParams('com_jcomments');

			// Disable editor-xtd buttons for Joomla editor. These buttons only available for superuser, because
			// plugins do not have an access rules.
			if ($params->get('editor_type') == 'joomla' && $params->get('editor_xtd_buttons') == 0
				&& !Factory::getApplication()->getIdentity()->get('isRoot'))
			{
				$this->buttons = false;
			}
			else
			{
				if ($buttons === 'true' || $buttons === 'yes' || $buttons === '1')
				{
					$this->buttons = true;
				}
				elseif ($buttons === 'false' || $buttons === 'no' || $buttons === '0')
				{
					$this->buttons = false;
				}
				else
				{
					$this->buttons = !empty($hide) ? explode(',', $buttons) : [];
				}
			}

			$this->hide        = !empty($hide) ? explode(',', (string) $this->element['hide']) : [];
			$this->editorType  = !empty($editorType) ? explode('|', trim($editorType)) : [];
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
		$app           = Factory::getApplication();
		$doc           = $app->getDocument();
		$params        = JcommentsComponentHelper::getParams('com_jcomments');
		$format        = $params->def('editor_format', 'bbcode');
		$commentLength = JcommentsComponentHelper::getAllowedCommentsLength();
		$editorConfig  = array(
			'field'       => $this->id,
			'editor_type' => $params->get('editor_type'),
			'editor'      => $app->get('editor'),
			'format'      => $format
		);

		// Set up minlength in data attribute 'cause Joomla textarea layout have no such attribute.
		$this->dataAttributes['minlength'] = $commentLength['min'] === 0
			? null : ($app->getIdentity()->get('isRoot') ? null : $commentLength['min']);
		$this->maxlength = $commentLength['max'] === 0 ? null : ($app->getIdentity()->get('isRoot') ? null : $commentLength['max']);

		Text::script('ERROR_YOUR_COMMENT_IS_TOO_SHORT');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = $doc->getWebAssetManager();

		/** @var \Joomla\CMS\WebAsset\WebAssetRegistry $wr */
		$wr = $wa->getRegistry();
		$wr->addRegistryFile('media/com_jcomments/joomla.asset.json');

		$wa->useScript('jcomments.form');
		$wa->useScript('editors');

		if ($format == 'xhtml' && $params->get('editor_type') == 'joomla')
		{
			$editor = $this->getJoomlaEditor();
			$params = array(
				'autofocus' => $this->autofocus,
				'readonly'  => $this->readonly || $this->disabled,
				'syntax'    => (string) $this->element['syntax']
			);
			$doc->addScriptOptions('jcomments', array('editor' => $editorConfig));

			return $editor->display(
				$this->name,
				htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8'),
				$this->width,
				$this->height,
				$this->columns,
				$this->rows,
				$this->buttons ? (is_array($this->buttons) ? array_merge($this->buttons, $this->hide) : $this->hide) : false,
				$this->id,
				$this->asset,
				$this->form->getValue($this->authorField),
				$params
			);
		}

		$langTag     = $app->getLanguage()->getTag();
		$_lang       = in_array($langTag, array('en-US', 'pt-BR')) ? $langTag : strtolower(substr($langTag, -2));
		$emoticons   = JcommentsFactory::getSmilies()->getList();
		$plugins     = array('autoyoutube', 'undo');
		$buttons     = (string) $this->element['buttons'];
		$editorTheme = File::makeSafe($params->get('editor_theme'));
		$editorIcons = $params->get('editor_theme_icons') == '' ? null : File::makeSafe($params->get('editor_theme_icons'));
		$themeUrl    = Uri::root() . 'media/com_jcomments/images/tmpl/editor/' . $editorTheme;

		// Override standart buttons list with buttons from xml attribute.
		if (!empty($buttons) && !in_array($buttons, array('yes', 'no', 'true', 'false', '1', '0')))
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

		$emojiEnabled = in_array('emoji', ToolbarHelper::getStandardButtons($buttons));

		$wa->registerAndUseStyle(
			'jceditor.theme.' . strtolower($editorTheme),
			'media/com_jcomments/css/editor/' . $editorTheme . '.css',
			array('version' => '4.1.0')
		)->addInlineStyle(
			'.sceditor-button div { background-image: url("' . $themeUrl . '/famfamfam.png"); }
			.sceditor-button-hide div { background-image: url("' . $themeUrl . '/hide.png"); }
			.sceditor-button-spoiler div { background-image: url("' . $themeUrl . '/spoiler.png"); }
			' . ($emojiEnabled ? '.sceditor-button-emoji div { background-image: url("' . $themeUrl . '/emoji.png"); }' : '')
		)->registerAndUseStyle(
			'jceditor.theme.' . strtolower($editorTheme) . '.custom',
			'media/com_jcomments/css/editor/' . $editorTheme . '-custom.css'
		)->useScript('jceditor.core');

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
			->useScript('jceditor.plg.undo');

		if ($emojiEnabled)
		{
			$plugins[] = 'emoji';

			$wa->useScript('jceditor.plg.emoji')
				->useStyle('jceditor.plg.emoji.styles');
		}

		if ($editorIcons)
		{
			$wa->useScript('jceditor.icons.' . $editorIcons);
		}

		$wa->registerAndUseScript('jceditor.lang', 'media/com_jcomments/js/editor/languages/' . $_lang . '.js')
			->registerAndUseScript('jceditor.lang.plugins', 'media/com_jcomments/js/editor/languages/plugins/' . $_lang . '.js');

		$js = $this->generateCustomButtonsJs(JcommentsFactory::getBbcode()->getCustomBbcodesList()['raw'], $editorIcons);

		if ($js > '')
		{
			$wa->addInlineScript(
				"document.addEventListener('DOMContentLoaded', function () {
					" . $js . "
				});",
				['name' => 'jceditor.format.custom', 'position' => 'before'],
				[],
				['jceditor.init']
			);
		}

		if ($this->customButtonsCSS > '')
		{
			$wa->addInlineStyle($this->customButtonsCSS);
		}

		$wa->useScript('jceditor.init');
		//$wa->registerAndUseScript('twemoji', 'media/com_jcomments/js/twemoji.js', ['version' => '14.1.2']);

		Text::script('COMMENT_TEXT_CODE');
		Text::script('COMMENT_TEXT_QUOTE');
		Text::script('FORM_BBCODE_HIDE');
		Text::script('FORM_BBCODE_SPOILER');

		$editorConfig['style']         = Uri::root() . 'media/com_jcomments/css/editor/content/'
			. File::makeSafe($params->get('custom_css', 'frontend-style') . '.css');
		$editorConfig['width']         = $this->width;
		$editorConfig['height']        = $this->height;
		$editorConfig['icons']         = $editorIcons;
		$editorConfig['locale']        = $langTag;
		$editorConfig['toolbar']       = ToolbarHelper::buildToolbar($buttons);
		$editorConfig['emoticonsRoot'] = Uri::root() . $params->get('smilies_path');
		$editorConfig['plugins']       = implode(',', $plugins);
		$editorConfig['autoUpdate']    = true;

		if ($emojiEnabled)
		{
			$editorConfig['emoji'] = (object) array(
				'enable' => true,
				//'subgroupTitle' => false
				//'excludeEmojis' => '1F3C1,1F6A9,1F38C,1F3F4,1F3F3,1F3F3-FE0F-200D-1F308,1F3F3-FE0F-200D-26A7-FE0F,1F3F4 200D 2620 FE0F',
				//'closeAfterSelect' => false,
				/*'twemoji' => (object) array(
					'base' => Uri::base() . 'media/com_jcomments/images/',
					'folder' => 'svg',
					'ext' => '.svg'
				)*/
			);
		}

		if (!empty($emoticons))
		{
			$editorConfig['emoticons'] = $emoticons;
		}
		else
		{
			// Delete emoticon button from editor toolbar
			$editorConfig['toolbar'] = str_replace(array('emoticon,', 'emoticon|'), '', $editorConfig['toolbar']);
		}

		$doc->addScriptOptions('jcomments', array('editor' => $editorConfig));

		return '<joomla-editor-sceditor>' . parent::getInput() . '</joomla-editor-sceditor>';
	}

	/**
	 * Generate javascript for cutom bbcode buttons
	 *
	 * @param   array        $buttons   Array with buttons
	 * @param   string|null  $iconFile  Filename with icon file
	 *
	 * @return  string
	 *
	 * @since   4.1
	 */
	private function generateCustomButtonsJs(array $buttons, ?string $iconFile): string
	{
		ob_start();

		$params = JcommentsComponentHelper::getParams('com_jcomments');
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
			elseif ($params->get('editor_format') == 'xhtml')
			{
				// TODO exec: должно вставлять HTML? Или тегом?
			}
		}

		ob_end_clean();

		return $js;
	}

	/**
	 * Method to get an Editor object based on the form field.
	 *
	 * @return  Editor  The Editor object.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	private function getJoomlaEditor()
	{
		// Only create the editor if it is not already created.
		if (empty($this->editor))
		{
			$editor = null;

			if ($this->editorType)
			{
				// Get the list of editor types.
				$types = $this->editorType;

				// Get the database object.
				$db = $this->getDatabase();

				// Build the query.
				$query = $db->getQuery(true)
					->select($db->quoteName('element'))
					->from($db->quoteName('#__extensions'))
					->where(
						[
							$db->quoteName('element') . ' = :editor',
							$db->quoteName('folder') . ' = ' . $db->quote('editors'),
							$db->quoteName('enabled') . ' = 1',
						]
					);

				// Declare variable before binding.
				$element = '';
				$query->bind(':editor', $element);
				$query->setLimit(1);

				// Iterate over the types looking for an existing editor.
				foreach ($types as $element)
				{
					// Check if the editor exists.
					$db->setQuery($query);
					$editor = $db->loadResult();

					// If an editor was found stop looking.
					if ($editor)
					{
						break;
					}
				}
			}

			// Create the JEditor instance based on the given editor.
			if ($editor === null)
			{
				$editor = Factory::getApplication()->get('editor');
			}

			$this->editor = Editor::getInstance($editor);
		}

		return $this->editor;
	}
}
