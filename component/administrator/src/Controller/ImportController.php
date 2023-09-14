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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class ImportController extends BaseController
{
	public function modal()
	{
		$source   = $this->input->get('source', '', 'string');
		$language = $this->input->get('language', '', 'string');

		if (empty($language))
		{
			$languages = LanguageHelper::getLanguages();
			$language  = isset($languages[0]->lang_code) ? $languages[0]->lang_code : '';
		}

		$this->input->set('view', 'import');
		$this->input->set('layout', 'modal');

		$model = $this->getModel();
		$model->setState($model->getName() . '.source', $source);
		$model->setState($model->getName() . '.language', $language);

		$view = $this->getView('import', 'html');
		$view->setModel($model, true);
		$view->setLayout('modal');
		$view->modal();
	}

	public function ajax()
	{
		$source   = $this->input->get('source');
		$language = $this->input->get('language');
		$start    = $this->input->getInt('start', 0);
		$limit    = $this->input->getInt('limit', 100);

		if (!empty($source))
		{
			$model  = $this->getModel();
			$return = $model->import($source, $language, $start, $limit);

			if ($return !== false)
			{
				$count   = $model->getState($model->getName() . '.count');
				$total   = $model->getState($model->getName() . '.total');
				$percent = ceil(($count / max($total, 1)) * 100);
				$percent = min($percent, 100);

				$start = min($start + $limit, $total);

				$data = array('count'    => (int) $count,
							  'total'    => (int) $total,
							  'percent'  => $percent,
							  'start'    => (int) $start,
							  'source'   => $source,
							  'language' => $language,
				);

				if ($count == $total)
				{
					$data['message'] = Text::sprintf('A_IMPORT_DONE', $count);
				}

				echo json_encode($data);
			}
			else
			{
				$data['message'] = Text::_('A_IMPORT_FAILED');
				echo json_encode($data);
			}
		}

		Factory::getApplication()->close();
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
	public function getModel($name = 'Import', $prefix = 'Administrator', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
