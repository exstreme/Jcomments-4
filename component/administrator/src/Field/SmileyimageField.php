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

namespace Joomla\Component\Jcomments\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Administrator\Helper\JcommentsHelper;

/**
 * Form Field class for display smilies dropdown with image preview.
 *
 * @since  1.7.0
 * @noinspection  PhpUnused
 */
class SmileyimageField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'SmileyImage';

	protected static $initialised = false;

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		$smiliesPath = JcommentsHelper::getSmiliesPath();
		$livePath    = str_replace('\\', '/', $smiliesPath);

		if (!self::$initialised)
		{
			/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
			$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
			$wa->useScript('jcomments.admin.fields');

			self::$initialised = true;
		}

		$html = array();

		// Images list
		$listAttr = $this->element['class'] ? ' class="smiley-refresh ' . $this->element['class'] . '"' : 'smiley-refresh';
		$listAttr .= ' data-url="' . Uri::root() . $livePath . '"';

		$html[] = HTMLHelper::_(
			'select.genericlist',
			$this->getOptions($smiliesPath),
			$this->name,
			trim($listAttr),
			'value',
			'text',
			$this->value,
			$this->id
		);

		// Preview
		if ($this->value && file_exists(JPATH_ROOT . '/' . $smiliesPath . $this->value))
		{
			$src = Uri::root() . $livePath . $this->value;
		}
		else
		{
			$src = '';
		}

		$width  = isset($this->element['preview_width']) ? (int) $this->element['preview_width'] : 48;
		$height = isset($this->element['preview_height']) ? (int) $this->element['preview_height'] : 48;

		$style = ($width > 0) ? 'max-width: ' . $width . 'px;' : '';
		$style .= ($height > 0) ? 'max-height: ' . $height . 'px;' : '';

		$imgAttr = array(
			'id'    => $this->id . '_preview',
			'class' => 'media-preview',
			'style' => $style,
		);
		$img             = HTMLHelper::image($src, Text::_('JLIB_FORM_MEDIA_PREVIEW_ALT'), $imgAttr);
		$previewImg      = '<div id="' . $this->id . '_preview_img"' . ($src ? '' : ' style="display: none;"') . '>' . $img . '</div>';
		$previewImgEmpty = '<div id="' . $this->id . '_preview_empty"' . ($src ? ' style="display: none;"' : '') . '>'
			. Text::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

		$html[] = '<div class="media-preview add-on">';
		$html[] = ' ' . $previewImgEmpty;
		$html[] = ' ' . $previewImg;
		$html[] = '</div>';

		return implode("\n", $html);

	}

	/**
	 * Method to get the field options.
	 *
	 * @param   string  $directory  Smile directory
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   3.7.0
	 */
	protected function getOptions($directory)
	{
		$options   = array();
		$options[] = HTMLHelper::_('select.option', '', '');
		$files     = Folder::files(JPATH_ROOT . '/' . $directory);

		foreach ($files as $file)
		{
			if (preg_match("/(gif|jpg|png)/i", (string) $file))
			{
				$options[] = HTMLHelper::_('select.option', $file, $file);
			}
		}

		return $options;
	}
}
