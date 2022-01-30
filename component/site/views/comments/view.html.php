<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * Comments View class
 *
 * @since  4.0
 */
class JCommentsViewComments extends HtmlView
{
	protected $items = null;

	protected $params = null;

	protected $menu = null;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  false|void
	 *
	 * @throws  Exception
	 * @since  4.0
	 */
	public function display($tpl = null)
	{
		$app          = Factory::getApplication();
		$this->items  = $this->get('Items');
		$this->menu   = $app->getMenu();
		$this->params = ComponentHelper::getParams('com_jcomments');
		$this->itemid = $app->input->get('Itemid', 0, 'int');
		$user         = $app->getIdentity();

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		if ($user->get('guest'))
		{
			$app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'warning');

			return false;
		}

		$this->prepareDocument();

		parent::display('list');
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  4.0
	 */
	protected function prepareDocument()
	{
		$app     = Factory::getApplication();
		$pathway = $app->getPathway();
		$title   = Text::_('COMMENTS_LIST_HEADER');

		// Get profile menu object
		$menu = $this->menu->getItems('link', 'index.php?option=com_users&view=profile', true);

		// Create a new pathway object
		$path = array(
			(object) array(
				'name' => $menu->title,
				'link' => 'index.php?option=com_users&view=profile'
			),
			(object) array(
				'name' => Text::_('COMMENTS_LIST_HEADER'),
				'link' => 'index.php?option=com_jcomments&view=comments&Itemid=' . $this->itemid
			)
		);

		if ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$pathway->setPathway($path);
		$this->document->setTitle($title);
	}
}
