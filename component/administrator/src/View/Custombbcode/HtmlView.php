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

namespace Joomla\Component\Jcomments\Administrator\View\Custombbcode;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

/**
 * View to edit a bbcode.
 *
 * @since  3.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The groups this user is assigned to
	 *
	 * @var     array
	 * @since   4.1
	 */
	protected $groups;

	/**
	 * The active item
	 *
	 * @var     object
	 * @since   4.1
	 */
	protected $item;

	/**
	 * The Form object
	 *
	 * @var     \Joomla\CMS\Form\Form
	 * @since   4.1
	 */
	protected $form;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		// Prevent user from modifying own group(s)
		$user = Factory::getApplication()->getIdentity();

		if ((int) $user->id != (int) $this->item->id || $user->authorise('core.admin'))
		{
			$groups = $this->form->getValue('button_acl');
			$this->groups = explode(',', $groups);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 *
	 * @throws  \Exception
	 */
	protected function addToolbar()
	{
		$app        = Factory::getApplication();
		$userId     = $app->getIdentity()->get('id');
		$canDo      = ContentHelper::getActions('com_jcomments', 'component');
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$isNew      = ($this->item->id == 0);
		$toolbar    = Toolbar::getInstance();

		$app->input->set('hidemainmenu', 1);
		ToolbarHelper::title(Text::_('A_CUSTOM_BBCODE_' . ($isNew ? 'NEW' : 'EDIT')), 'code');

		if ($isNew && $canDo->get('core.create'))
		{
			$toolbar->apply('custombbcode.apply');

			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure(
				function (Toolbar $childBar)
				{
					$childBar->save('custombbcode.save');
					$childBar->save2new('custombbcode.save2new');
				}
			);

			$toolbar->cancel('custombbcode.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			if (!$checkedOut && $itemEditable)
			{
				$toolbar->apply('custombbcode.apply');
			}

			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure(
				function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo)
				{
					// Can't save the record if it's checked out and editable
					if (!$checkedOut && $itemEditable)
					{
						$childBar->save('custombbcode.save');

						// We can save this record, but check the create permission to see if we can return to make a new one.
						if ($canDo->get('core.create'))
						{
							$childBar->save2new('custombbcode.save2new');
						}
					}

					// If checked out, we can still save
					if ($canDo->get('core.create'))
					{
						$childBar->save2copy('custombbcode.save2copy');
					}
				}
			);

			$toolbar->cancel('custombbcode.cancel');
		}
	}
}
