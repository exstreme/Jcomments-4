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

namespace Joomla\Component\Jcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

/**
 * Basic controller class for handle objects refresh actions.
 *
 * @since  4.1
 */
class ObjectsController extends BaseController
{
	/**
	 * Method to start objects refresh actions
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public function refresh()
	{
		if (!$this->checkToken('post', false))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			$this->app->close();
		}

		/** @var \Joomla\Component\Jcomments\Site\Model\ObjectsModel $model */
		$model = $this->getModel();
		$step  = $this->app->input->getInt('step', 0);
		$data = array('total' => 25252/*$model->countObjectsWithoutInfo()*/); // TODO Посчитать кол-во объектов для обновления

		// Delete data from objects table.
		if ($step == 0)
		{
			if ($model->cleanObjectsTable())
			{
				$data['step'] = 1;
				$data['percent'] = 1;
				$data['log'] = Text::sprintf('A_REFRESH_OBJECTS_TABLE', Text::_('A_REFRESH_OBJECTS_TABLE_SUCCESS'));
			}
			else
			{
				$data['step'] = 0;
				$data['percent'] = 0;
				$data['log'] = Text::sprintf('A_REFRESH_OBJECTS_TABLE', Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
			}

			echo new JsonResponse($data);
		}
		// Add rows with objects info.
		elseif ($step == 1)
		{
			$result = $model->refreshObjectsData();

			$data['step'] = 2;
			$data['percent'] = 80;
			$data['log'] = '1';

			echo new JsonResponse($data);
		}
		// Delete objects cache.
		elseif ($step == 2)
		{
			$this->app->getLanguage()->load('com_cache');
			$model->cleanObjectsCache();

			$data['step'] = 3;
			$data['percent'] = 100;
			$data['log'] = ($this->app->get('caching') == 0)
				? Text::sprintf('A_REFRESH_OBJECTS_CACHE', Text::_('COM_CACHE_QUICKICON_SRONLY_NOCACHE'))
				: Text::sprintf('A_REFRESH_OBJECTS_CACHE', Text::_('COM_CACHE_MSG_ALL_CACHE_GROUPS_CLEARED'));

			echo new JsonResponse($data);
		}
		else
		{
			echo new JsonResponse(null, 'Ok');
		}

		$this->app->close();
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Objects', $prefix = 'Site', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
