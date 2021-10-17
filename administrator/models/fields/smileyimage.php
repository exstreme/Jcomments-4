<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JFormFieldSmileyImage extends FormField
{
	protected $type = 'SmileyImage';

	protected static $initialised = false;

	protected function getInput()
	{
		require_once JPATH_COMPONENT . '/helpers/jcomments.php';

		$smiliesPath = JCommentsHelper::getSmiliesPath();
		$livePath    = str_replace('\\', '/', $smiliesPath);

		if (!self::$initialised)
		{
			$script   = array();
			$script[] = '	function JCommentsSmileyRefreshPreview(id) {';
			$script[] = '		var value = document.getElementById(id).value;';
			$script[] = '		var img = document.getElementById(id + "_preview");';
			$script[] = '		if (img) {';
			$script[] = '			if (value) {';
			$script[] = '				img.src = "' . Uri::root() . $livePath . '" + value;';
			$script[] = '				document.getElementById(id + "_preview_empty").setStyle("display", "none");';
			$script[] = '				document.getElementById(id + "_preview_img").setStyle("display", "");';
			$script[] = '			} else { ';
			$script[] = '				img.src = ""';
			$script[] = '				document.getElementById(id + "_preview_empty").setStyle("display", "");';
			$script[] = '				document.getElementById(id + "_preview_img").setStyle("display", "none");';
			$script[] = '			} ';
			$script[] = '		} ';
			$script[] = '	}';

			Factory::getApplication()->getDocument()->addScriptDeclaration(implode("\n", $script));

			self::$initialised = true;
		}

		$html = array();

		// Images list
		$listAttr = '';
		$listAttr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$listAttr .= ' onchange="JCommentsSmileyRefreshPreview(this.getAttribute(\'id\'))"';

		$html[] = HTMLHelper::_('select.genericlist', (array) $this->getOptions($smiliesPath), $this->name, trim($listAttr), 'value', 'text', $this->value, $this->id);


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

		$style = '';
		$style .= ($width > 0) ? 'max-width:' . $width . 'px;' : '';
		$style .= ($height > 0) ? 'max-height:' . $height . 'px;' : '';

		$imgAttr         = array(
			'id'    => $this->id . '_preview',
			'class' => 'media-preview',
			'style' => $style,
		);
		$img             = HTMLHelper::image($src, Text::_('JLIB_FORM_MEDIA_PREVIEW_ALT'), $imgAttr);
		$previewImg      = '<div id="' . $this->id . '_preview_img"' . ($src ? '' : ' style="display:none"') . '>' . $img . '</div>';
		$previewImgEmpty = '<div id="' . $this->id . '_preview_empty"' . ($src ? ' style="display:none"' : '') . '>'
			. Text::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

		$html[] = '<div class="media-preview add-on">';
		$html[] = ' ' . $previewImgEmpty;
		$html[] = ' ' . $previewImg;
		$html[] = '</div>';

		return implode("\n", $html);

	}

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
