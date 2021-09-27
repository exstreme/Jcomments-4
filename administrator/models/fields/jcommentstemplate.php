<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class JFormFieldJCommentsTemplate extends ListField
{
	protected $type = 'JCommentsTemplate';

	protected function getInput()
	{
		$this->multiple = false;

		return parent::getInput();
	}

	protected function getOptions()
	{
		$options = array();

		$folders = Folder::folders(JPATH_ROOT . '/components/com_jcomments/tpl/');
		if (is_array($folders))
		{
			foreach ($folders as $folder)
			{
				$options[] = HTMLHelper::_('select.option', $folder, $folder);
			}
		}

		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
