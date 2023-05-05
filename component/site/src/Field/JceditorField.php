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
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;
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

			$buttons = (string) $this->element['buttons'];
			$hide = (string) $this->element['hide'];

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

			$this->hide = !empty($hide) ? explode(',', (string) $this->element['hide']) : [];
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
		$doc    = $app->getDocument();
		$params = ComponentHelper::getParams('com_jcomments');

		/** @var WebAssetManager $wa */
		$wa        = $doc->getWebAssetManager();
		$langTag   = $app->getLanguage()->getTag();
		$langSpec  = array('en-US', 'pt-BR');
		$_lang     = in_array($langTag, $langSpec) ? $langTag : strtolower(substr($langTag, -2));
		$bbcodes   = JcommentsFactory::getBbcode();
		$emoticons = JcommentsFactory::getSmilies()->getList();

		$wa->registerAndUseStyle('jceditor.theme', 'media/com_jcomments/editor/themes/square.css')
			->registerAndUseStyle('jceditor.theme.custom', 'media/com_jcomments/editor/themes/custom.css')
			->registerAndUseScript('jceditor.core', 'media/com_jcomments/editor/sceditor.js')
			->registerAndUseScript('jceditor.bbcode', 'media/com_jcomments/editor/formats/bbcode.js')
			->registerAndUseScript('jceditor.plg.autoyoutube', 'media/com_jcomments/editor/plugins/autoyoutube.js')
			->registerAndUseScript('jceditor.plg.undo', 'media/com_jcomments/editor/plugins/undo.js')
			->registerAndUseScript('jceditor.icons', 'media/com_jcomments/editor/icons/monocons.js')
			->registerAndUseScript('jceditor.lang', 'media/com_jcomments/editor/languages/' . $_lang . '.js')
			->registerAndUseScript('jceditor.init', 'media/com_jcomments/editor/init.js');

		$_bbcodes = array();

		foreach ($bbcodes->get() as $key => $code)
		{
			if (!$bbcodes->canUse($code) && strpos($code, 'separator') === false)
			{
				continue;
			}

			// Convert bbcodes to editor toolbar codes
			switch ($code)
			{
				case 'b':
					$_bbcodes[$key] = 'bold';
					break;
				case 'i':
					$_bbcodes[$key] = 'italic';
					break;
				case 'u':
					$_bbcodes[$key] = 'underline';
					break;
				case 's':
					$_bbcodes[$key] = 'strike';
					break;
				case 'sub':
					$_bbcodes[$key] = 'subscript';
					break;
				case 'sup':
					$_bbcodes[$key] = 'superscript';
					break;
				case 'list':
					$_bbcodes[$key] = 'bulletlist';
					break;
				case 'url':
					$_bbcodes[$key] = 'link';
					break;
				case 'img':
					$_bbcodes[$key] = 'image';
					break;
				default:
					$_bbcodes[$key] = $code;
			}
		}

		Text::script('COMMENT_TEXT_CODE');
		Text::script('COMMENT_TEXT_QUOTE');
		Text::script('FORM_BBCODE_HIDE');

		$editorConfig = array(
			'format'        => 'bbcode',
			'style'         => 'media/com_jcomments/editor/themes/content/jc-default.css',
			'width'         => $this->width,
			'height'        => $this->height,
			'icons'         => 'monocons',
			'locale'        => $langTag,
			'toolbar'       => $bbcodes->enabled() ? preg_replace('@,separator\d,@ixU', '|', implode(',', $_bbcodes)) : '',
			'emoticonsRoot' => $params->get('smilies_path')
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
		$this->maxlength = $params->get('comment_maxlength');

		/*echo '<pre>';
		print_r(JcommentsFactory::getSmilies()->getList());
		echo '</pre>';*/

		return parent::getInput();
	}
}
