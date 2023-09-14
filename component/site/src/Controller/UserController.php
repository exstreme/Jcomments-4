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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * User main controller
 *
 * @since  4.1
 */
class UserController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = array())
	{
		if (Factory::getApplication()->getIdentity()->get('guest'))
		{
			$this->setRedirect('index.php', Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');

			return;
		}

		$element = $this->getTask();
		$view = $this->getView('User', 'Html', 'Site');

		switch ($element)
		{
			case 'subscriptions':
				$modelName = 'Subscriptions';
				break;
			case 'votes':
			case 'comments':
				$modelName = 'Comments';
				break;
			default: $modelName = '';
		}

		if (!$model = $this->getModel($modelName))
		{
			$this->setRedirect('index.php', Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');

			return;
		}

		$view->setModel($model, true);
		$view->display(strtolower($element));
	}
}
