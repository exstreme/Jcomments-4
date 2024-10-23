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
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsTree;
use Joomla\Event\DispatcherInterface;

/**
 * Raw View class for the Jcomments component
 *
 * @since  4.0
 * @noinspection PhpUnused
 */
class RawView extends BaseHtmlView
{
	/**
	 * @var  \stdClass[]  The comments
	 * @since  4.1
	 */
	protected $items = null;

	/**
	 * @var  \stdClass[]  The comments array with pinned comments
	 * @since  4.1
	 */
	protected $pinnedItems = null;

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
	 * @var    object  Object info
	 * @since  4.1
	 */
	protected $objectInfo = null;

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
		$dispatcher        = $this->getDispatcher();

		// Set default template. Because this view loaded through plugin event the default template is 'blog'.
		$this->templateView = $this->params->get('template_view');
		$this->setLayout('default');

		// Import plugins due the view called not from controller but from other plugin.
		PluginHelper::importPlugin('jcomments', null, true, $dispatcher);

		$this->objectInfo = ObjectHelper::getObjectInfo($this->objectID, $this->objectGroup);
		$rows             = $this->get('Items');
		$returnUrl        = base64_encode(
			Uri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port'))
			. $this->objectInfo->object_link
		);

		if (count($errors = $this->get('Errors')))
		{
			echo new JsonResponse(null, implode("\n", $errors), true);

			$app->close();
		}

		if (!in_array($this->objectInfo->object_access, $user->getAuthorisedViewLevels()))
		{
			echo new JsonResponse(null, ucfirst(Text::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE')), true);

			$app->close();
		}

		if (count($rows))
		{
			$this->dispatchSomeEvents($dispatcher, array('user' => $user, 'rows' => $rows));

			if ($this->templateView == 'tree')
			{
				$pinnedComments  = array();
				$tree            = new JcommentsTree($rows);
				$items           = $tree->get();
				$allowPinnedList = $this->params->get('allow_pinned_list');

				foreach ($rows as $row)
				{
					// Run autocensor, replace quotes, smilies and other pre-view processing
					JcommentsContentHelper::prepareComment($row);

					if (isset($items[$row->id]))
					{
						$row->commentData->set('number', '');
					}

					$row->returnUrl = $returnUrl;

					if ($allowPinnedList && $row->pinned == 1 && $row->deleted == 0)
					{
						$pinnedComments[] = $row;
					}

					JcommentsContentHelper::dispatchContentEvents($dispatcher, $row);
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
					$row->returnUrl = $returnUrl;

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

					JcommentsContentHelper::dispatchContentEvents($dispatcher, $row);

					$items[$row->id] = $row;
				}

				$this->pagination = &$pagination;
				$this->totalComments = $totalComments;

				if ($this->params->get('allow_pinned_list'))
				{
					$model = $app->bootComponent('com_jcomments')->getMVCFactory()->createModel('Comments', 'Site', array('ignore_request' => true));
					$model->setState('list.limit', '0');
					$model->setState('list.start', '0');
					$model->setState('list.options.votes', $this->params->get('enable_voting', 0));
					$model->setState('list.options.lang', $app->getLanguage()->getTag());
					$model->setState('list.options.labels', true);
					$model->setState('list.options.blacklist', true);
					$model->setState('list.options.comment_lang', true);
					$model->setState('list.options.filter', 'c.pinned = 1 AND c.deleted = 0');
					$pinnedRows = $model->getItems();
					$pinnedComments = array();

					$this->dispatchSomeEvents($dispatcher, array('user' => $user, 'rows' => $pinnedRows));

					foreach ($pinnedRows as $k => $v)
					{
						// Run autocensor, replace quotes, smilies and other pre-view processing
						JcommentsContentHelper::prepareComment($v);
						$v->returnUrl = $returnUrl;

						if (isset($items[$v->id]))
						{
							$v->commentData->set('number', '');
						}

						JcommentsContentHelper::dispatchContentEvents($dispatcher, $v);

						$pinnedComments[$k] = $v;
					}
				}
			}

			$this->items = &$items;
			$this->pinnedItems = &$pinnedComments;

			unset($rows);
			unset($pinnedRows);
		}

		ob_start();

		parent::display($this->templateView);
		$output = ob_get_contents();

		ob_end_clean();

		echo new JsonResponse(
			array(
				'total' => $this->totalComments,
				'html' => $output,
				// Used in jcomments.core.js/Jcomments.loadComments() to dynamically switch comments list type.
				'type' => $this->params->get('template_view')
			)
		);

		$app->close();
	}

	/**
	 * Dispatch some events for each comment item.
	 * Do not use inside loops.
	 *
	 * @param   DispatcherInterface  $dispatcher  The event dispatcher through which to launch the event.
	 * @param   array                $data        Array with user object and comments
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	private function dispatchSomeEvents(DispatcherInterface $dispatcher, array $data)
	{
		$dispatcher->dispatch(
			'onJCommentsCommentsPrepare',
			AbstractEvent::create(
				'onJCommentsCommentsPrepare',
				array('subject' => new \stdClass, array($data['rows']))
			)
		);

		if ($data['user']->authorise('comment.avatar', 'com_jcomments'))
		{
			$dispatcher->dispatch(
				'onPrepareAvatars',
				AbstractEvent::create(
					'onPrepareAvatars',
					array('subject' => new \stdClass, 'items' => $data['rows'])
				)
			);
		}
	}
}
