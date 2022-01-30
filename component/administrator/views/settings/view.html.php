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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

class JCommentsViewSettings extends HtmlView
{
	/**
	 * @var   \Joomla\CMS\Form\Form
	 * @since 3.0
	 */
	protected $form;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');

		$this->addToolbar();

		parent::display($tpl);
	}

	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_jcomments', 'component');

		ToolbarHelper::title(Text::_('A_SETTINGS'));

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::apply('settings.save');
			ToolbarHelper::cancel('settings.cancel');
			ToolbarHelper::custom('settings.saveConfig', 'download', 'download', 'A_SETTINGS_BUTTON_SAVECONFIG', false);
			ToolbarHelper::modal('fileModal', 'icon-upload', 'A_SETTINGS_BUTTON_RESTORECONFIG');
			ToolbarHelper::preferences('com_jcomments');
		}
	}
}
