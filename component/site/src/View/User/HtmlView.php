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

namespace Joomla\Component\Jcomments\Site\View\User;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML View class for the Jcomments component
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * @var  \stdClass[]  The subscriptions array
	 * @since  4.1
	 */
	protected $items = null;

	/**
	 * @var  \Joomla\CMS\Pagination\Pagination  The pagination object.
	 * @since  4.1
	 */
	protected $pagination = null;

	/**
	 * @var    \Joomla\Registry\Registry  Component params
	 * @since  4.1
	 */
	protected $params = null;

	/**
	 * @var    integer  Total items
	 * @since  4.1
	 */
	protected $total = 0;

	/**
	 * Execute and display a template script.
	 *
	 * @param   mixed  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$app               = Factory::getApplication();
		$user              = $app->getIdentity();
		$state             = $this->get('State');
		$this->params      = $state->get('params');
		$this->objectID    = $state->get('object_id');
		$this->objectGroup = $state->get('object_group');
		$task              = $app->input->getCmd('task');

		// Document not exist in Application until dispatch happen.
		$this->document = $app->getDocument();

		$this->setDocumentTitle(Text::_(strtoupper($task) . '_LIST'));

		// Set up model state before calling getItems()
		$state->set('list.options.object_info', true);

		if ($task == 'votes')
		{
			$state->set('list.options.userid', $user->get('id'));
		}
		elseif ($task == 'comments')
		{
			$state->set('list.options.userid', $user->get('id'));
			$state->set('object_group', '');
			$state->set('list.options.lang', null);
			$state->set('list.options.votes', 0);
			$state->set('list.options.comment_lang', true);
		}

		$this->items = $this->get('Items');

		/** @var \Joomla\CMS\Pagination\Pagination $pagination */
		$this->pagination = $this->get('Pagination');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		if ($task != 'subscriptions')
		{
			$this->pagination->prefix = 'jc_';
		}

		$this->pagination->hideEmptyLimitstart = true;
		$this->total = $this->get('Total');

		parent::display($tpl);
	}
}
