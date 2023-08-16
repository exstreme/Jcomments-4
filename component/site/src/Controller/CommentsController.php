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

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Comments Controller
 *
 * @since  4.1
 */
class CommentsController extends BaseController
{
	/**
	 * Remove selected user votes from profile.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function removeVotes()
	{
		$this->checkToken();

		$return = Route::_(JcommentsFactory::getReturnPage(), false);

		if (!$this->app->getIdentity()->authorise('comment.vote', 'com_jcomments'))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($return, false));
		}

		$cid = (array) $this->input->get('cid', [], 'int');
		$cid = array_filter($cid);

		if (empty($cid))
		{
			$this->app->getLogger()->warning(Text::_('COM_JCOMMENTS_NO_ITEM_SELECTED'), ['category' => 'jerror']);
		}
		else
		{
			/** @var \Joomla\Component\Jcomments\Site\Model\CommentsModel $model */
			$model = $this->getModel();

			// Remove the items.
			if ($model->deleteVotes($cid))
			{
				$this->setMessage(Text::plural('COM_JCOMMENTS_N_ITEMS_DELETED', count($cid)));
			}
			else
			{
				$this->setMessage($model->getError(), 'error');
			}
		}

		$this->setRedirect(Route::_($return, false));
	}

	/**
	 * Method to get the object page with limistarts by comment ID.
	 *
	 * @param   mixed  $id  Comment ID. Optional.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public function goto($id = null)
	{
		$params        = ComponentHelper::getParams('com_jcomments');
		$limit         = $params->get('list_limit');
		$id            = $this->input->getInt('id', $id);
		$excludeFields = array(
			'c.parent', 'c.userid', 'c.name', 'c.username', 'c.title', 'c.comment', 'c.email', 'c.homepage', 'c.date',
			'c.ip', 'c.isgood', 'c.ispoor', 'c.checked_out', 'c.checked_out_time'
		);

		/** @var \Joomla\Component\Jcomments\Site\Model\CommentsModel $model */
		$model = $this->getModel('Comments', 'Site', array('ignore_request' => true, 'exclude_fields' => $excludeFields));

		$model->setState('list.limit', 0);
		$model->setState('list.start', 0);
		$model->setState('object_group', $this->input->getWord('object_group'));
		$model->setState('object_id', $this->input->getInt('object_id'));
		$model->setState('list.options.object_info', true);
		$model->setState('list.options.labels', false);

		// Get all items for certain object to find the item position.
		$items = $model->getItems();

		// Position of the element in array.
		$rowIndex = null;

		foreach ($items as $i => $item)
		{
			if ($item->id == $id)
			{
				$rowIndex = $i;
				break;
			}
		}

		// Row not found
		if (is_null($rowIndex))
		{
			$this->setRedirect('index.php', Text::_('ERROR_NOT_FOUND'), 'error');

			return;
		}

		$limitstart    = (int) (ceil(($rowIndex + 1) / $limit) - 1) * $limit;
		$limitstart    = max($limitstart, 0);
		$limitstartVar = $limitstart > 0 ? '&jc_limitstart=' . $limitstart : '';
		$url           = $items[$rowIndex]->object_link;
		$pluginParams  = new Registry(PluginHelper::getPlugin('content', 'jcomments')->params);

		if ($pluginParams->get('show_comments_event') == 'onContentBeforeDisplay')
		{
			// TODO Тут может быть любой объект любого компонента
			$article = (new \Joomla\Component\Content\Site\Model\ArticleModel)->getItem($items[$rowIndex]->object_id);

			// Find {jcomments} tag in whole article(w/o page breaks) and display only on the page where tag was found.
			if (StringHelper::strpos($article->introtext, '{jcomments}') !== false)
			{
				// Find tag in article with page break
				if (StringHelper::strpos($article->fulltext, '{jcomments}') !== false)
				{
					//$article->text = str_replace('{jcomments}', '', $article->text) . $output;
				}
			}
			// Display comments only on latest page
			else
			{
				/*$text       = preg_split('#<hr(.*)class="system-pagebreak"(.*)\/?>#iU', $article->fulltext);
				$pages      = count($text);
				$limitstart = $this->app->input->getInt('limitstart');
				$showAll    = $this->app->input->getBool('showall');

				if ($limitstart == $pages - 1 || $showAll)
				{
					$article->text .= $output;
				}*/
			}

			// TODO Найти limitstart для материала
			echo '<pre>';
			echo $url . $limitstartVar . '#comment-item-' . $id;
			print_r($item);
			print_r($items);
		}
		elseif ($pluginParams->get('show_comments_event') == 'onContentAfterDisplay')
		{
			if ($params->get('template_view') == 'tree')
			{
				$this->setRedirect($url . '#comment-item-' . $id);
			}
			else
			{
				$this->setRedirect($url . $limitstartVar . '#comment-item-' . $id);
			}
		}
		else
		{
			$this->setRedirect('index.php', Text::_('ERROR'), 'error');
		}
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.5
	 */
	public function getModel($name = 'Comments', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
