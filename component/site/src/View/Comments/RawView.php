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

namespace Joomla\Component\Jcomments\Site\View\Comments;

defined('_JEXEC') or die;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsTree;

/**
 * Raw View class for the Jcomments component
 *
 * @since  4.0
 * @noinspection PhpUnused
 */
class RawView extends BaseHtmlView
{
	/**
	 * @var  \stdClass[]  The comments array
	 * @since  4.1
	 */
	protected $items = null;

	/**
	 * @var  \Joomla\CMS\Pagination\Pagination  The pagination object.
	 * @since  4.1
	 */
	protected $pagination = null;

	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.1
	 */
	protected $params;

	/**
	 * @var    string  Option value from request
	 * @since  4.1
	 */
	protected $objectGroup = '';

	/**
	 * @var    integer  Object ID
	 * @since  4.1
	 */
	protected $objectID = 0;

	/**
	 * @var    string  Comments list template view
	 * @since  4.1
	 */
	protected $templateView;

	/**
	 * @var    integer  Total comments for object
	 * @since  4.1
	 */
	protected $totalComments = 0;

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

		// Set model state before calling getItems()
		$state->set('list.options.object_info', true);

		// Set default template. Because this view loaded through plugin event the default template is 'blog'.
		$this->templateView = $this->params->get('template_view');
		$this->setLayout('default');

		// Import plugins due the view called not from controller but from other plugin.
		PluginHelper::importPlugin('jcomments');

		$rows = $this->get('Items');

		if (count($errors = $this->get('Errors')))
		{
			echo new JsonResponse(null, implode("\n", $errors), true);

			$app->close();
		}

		if (count($rows))
		{
			$dispatcher = $this->getDispatcher();
			$dispatcher->dispatch(
				'onJCommentsCommentsPrepare',
				AbstractEvent::create(
					'onJCommentsCommentsPrepare',
					array('subject' => new \stdClass, array($rows))
				)
			);

			if ($user->authorise('comment.avatar', 'com_jcomments'))
			{
				$dispatcher->dispatch(
					'onPrepareAvatars',
					AbstractEvent::create(
						'onPrepareAvatars',
						array('subject' => new \stdClass, $rows)
					)
				);
			}

			if ($this->templateView == 'tree')
			{
				$tree  = new JcommentsTree($rows);
				$items = $tree->get();

				foreach ($rows as $row)
				{
					// Run autocensor, replace quotes, smilies and other pre-view processing
					JcommentsContentHelper::prepareComment($row);

					if (isset($items[$row->id]))
					{
						$row->commentData->set('number', '');
					}
				}

				$this->totalComments = empty($items) ? 0 : count($items);
			}
			else
			{
				/** @var \Joomla\CMS\Pagination\Pagination $pagination */
				$pagination      = $this->get('Pagination');
				$items           = array();
				$limitstart      = $state->get('list.start');
				$commentsPerPage = $state->get('list.limit');
				$totalComments   = $this->get('Total');

				$pagination->hideEmptyLimitstart = true;
				$pagination->prefix = 'jc_';
				$pagination->setAdditionalUrlParam('option', $this->objectGroup);
				$pagination->setAdditionalUrlParam('id', $this->objectID);

				if ($this->params->get('comments_list_order') == 'DESC')
				{
					if ($commentsPerPage > 0)
					{
						$page = $pagination->pagesCurrent;
						$i = $totalComments - ($commentsPerPage * ($page > 0 ? $page - 1 : 0));
					}
					else
					{
						$i = $totalComments;
					}
				}
				else
				{
					$i = $limitstart + 1;
				}

				foreach ($rows as $row)
				{
					// Run autocensor, replace quotes, smilies and other pre-view processing
					JcommentsContentHelper::prepareComment($row);

					if (isset($row->_number))
					{
						$row->commentData->set('number', $row->_number);
					}
					else
					{
						if ($this->params->get('comments_list_order') == 'DESC')
						{
							$row->commentData->set('number', $i--);
						}
						else
						{
							$row->commentData->set('number', $i++);
						}
					}

					$items[$row->id] = $row;
				}

				$this->pagination = &$pagination;
				$this->totalComments = $totalComments;
			}

			$this->items = &$items;

			unset($rows);
		}

		ob_start();

		parent::display($this->templateView);
		$output = ob_get_contents();

		ob_end_clean();

		echo new JsonResponse(array('total' => $this->totalComments, 'html' => $output));

		$app->close();
	}
}
