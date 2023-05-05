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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Utility\Utility;

/**
 * Form Field class for display config backup and restore html.
 *
 * @since  1.7.0
 * @noinspection  PhpUnused
 */
class ConfigrestoreField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'ConfigRestore';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->getRegistry()->addExtensionRegistryFile('com_jcomments');
		$wa->useScript('jcomments.admin.fields');

		$maxSize = HTMLHelper::_('number.bytes', Utility::getMaxUploadSize());

		return '<div class="mb-4">
			<a href="' . Route::_('index.php?option=com_jcomments&task=settings.backup&format=raw') . '" class="btn btn-success" target="blank">
				<span class="fa fa-download"></span> ' . Text::_('A_SETTINGS_BUTTON_SAVECONFIG') . '
			</a>
		</div>
		<div class="input-group">
			<input type="file" name="upload_config" id="upload_config" aria-labelledby="upload" class="form-control"
				   data-url="' . Route::_('index.php?option=com_jcomments&task=settings.restore&format=json') . '">
			<button type="button" class="btn btn-primary" id="upload">
				<span class="fa fa-upload"></span> ' . Text::_('A_SETTINGS_BUTTON_RESTORECONFIG') . '
			</button>
		</div>
		<small class="form-text">' . Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', '&#x200E;' . $maxSize) . '</small>';
	}
}
