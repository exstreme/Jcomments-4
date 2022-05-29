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

define('JCOMMENTS_SHOW', 1);

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * HTML View class for the Jcomments component
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * @var    \Joomla\Registry\Registry
	 * @since  4.0.0
	 */
	protected $params;

	/**
	 * @var    \Joomla\Component\Jcomments\Site\Library\Jcomments\JCommentsAcl|null
	 * @since  4.0.0
	 */
	protected $acl;

	/**
	 * Indicate if user can subscribe
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $canSubscribe = false;

	/**
	 * Check if user is subscribed
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $isSubscribed = false;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function display($tpl = null)
	{
		$app                = Factory::getApplication();
		$this->acl          = JcommentsFactory::getACL();
		$this->params       = $app->getParams('com_jcomments');
		$this->objectGroup  = $app->input->getCmd('option', 'com_content');
		$this->objectID     = $app->input->getInt('id', 0);
		$this->canSubscribe = $this->acl->canSubscribe();
		$this->document     = $app->getDocument();

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		if ($this->canSubscribe)
		{
			$user = $app->getIdentity();
			$subscriptionModel = $app->bootComponent('com_jcomments')->getMVCFactory()
				->createModel('Subscription', 'Site', array('ignore_request' => true));

			/** @see \Joomla\Component\Jcomments\Site\Model\SubscriptionModel::isSubscribed() */
			$this->isSubscribed = $subscriptionModel->isSubscribed(
				$app->input->getInt('id', 0),
				$app->input->getCmd('option', 'com_content'),
				$user->get('id')
			);
		}

		parent::display($tpl);
	}
}
