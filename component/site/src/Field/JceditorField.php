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
use Joomla\CMS\Form\Field\TextareaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;
use Joomla\Component\Jcomments\Site\Helper\ToolbarHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

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
		$wa        = $doc->getWebAssetManager();
		$langTag   = $app->getLanguage()->getTag();
		$_lang     = in_array($langTag, array('en-US', 'pt-BR')) ? $langTag : strtolower(substr($langTag, -2));
		$emoticons = JcommentsFactory::getSmilies()->getList();
		$format    = $params->def('editor_format', 'bbcode');
		$plugins   = array('autoyoutube', 'undo', 'emoji');
		$buttons   = (string) $this->element['buttons'];

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

		$wa->registerAndUseStyle('jceditor.theme', 'media/com_jcomments/editor/themes/square.css')
			->registerAndUseStyle('jceditor.theme.custom', 'media/com_jcomments/editor/themes/custom.css')
			->registerAndUseScript('jceditor.core', 'media/com_jcomments/editor/sceditor.js');

		if ($format == 'bbcode')
		{
			$wa->registerAndUseScript('jceditor.bbcode', 'media/com_jcomments/editor/formats/bbcode.js')
				->registerAndUseScript('jceditor.plg.alternative.lists', 'media/com_jcomments/editor/plugins/alternative-lists.js');
			$plugins[] = 'alternative-lists';
		}
		else
		{
			$wa->registerAndUseScript('jceditor.bbcode', 'media/com_jcomments/editor/formats/xhtml.js');
		}

		$wa->registerAndUseScript('jceditor.plg.autoyoutube', 'media/com_jcomments/editor/plugins/autoyoutube.js')
			->registerAndUseScript('jceditor.plg.undo', 'media/com_jcomments/editor/plugins/undo.js')
			->registerAndUseScript('jceditor.plg.emoji', 'media/com_jcomments/editor/plugins/emoji.js')
			->registerAndUseScript('jceditor.icons', 'media/com_jcomments/editor/icons/monocons.js')
			->registerAndUseScript('jceditor.lang', 'media/com_jcomments/editor/languages/' . $_lang . '.js')
			->registerAndUseScript('jceditor.lang.plugins', 'media/com_jcomments/editor/languages/plugins/' . $_lang . '.js')
			->registerAndUseScript('jceditor.init.bbcode', 'media/com_jcomments/editor/init.js')
			->registerAndUseScript('twemoji', 'media/com_jcomments/js/twemoji.js');

		Text::script('COMMENT_TEXT_CODE');
		Text::script('COMMENT_TEXT_QUOTE');
		Text::script('FORM_BBCODE_HIDE');
		Text::script('FORM_BBCODE_SPOILER');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_SHORT');
		Text::script('ERROR_YOUR_COMMENT_IS_TOO_LONG');

		$editorConfig = array(
			'format'        => $format,
			'style'         => Uri::root() . 'media/com_jcomments/editor/themes/content/' . $params->get('custom_css', 'frontend-style') . '.css',
			'width'         => $this->width,
			'height'        => $this->height,
			'icons'         => 'monocons',
			'locale'        => $langTag,
			'toolbar'       => ToolbarHelper::prepareToolbar($buttons),
			'emoticonsRoot' => Uri::root() . $params->get('smilies_path'),
			'plugins'       => implode(',', $plugins),
			'autoUpdate'    => true,
			'minlength'     => $user->get('isRoot') ? 0 : $params->get('comment_minlength'),
			'maxlength'     => $user->get('isRoot') ? 0 : $params->get('comment_maxlength'),
			'emoji'         => (object) array(
				'enable' => true,
				'excludeEmojis' => '1FAE8,1FA77,1FA75,1FA76,1FAF7,1FAF8,1FACE,1FACF,1FABD,1FABF,1FABC,1FABB,1FADA,1FADB,1FAAD,1FAAE,1FA87,1FA88,1FAAF,1F6DC',
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
		$this->dataAttributes['data-config'] = json_encode($editorConfig);

		return parent::getInput();
	}
}
