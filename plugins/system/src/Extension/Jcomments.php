<?php
/**
 * JComments system plugin - System plugin for attaching JComments.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\System\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Http\Exception\InvalidResponseCodeException;
use Joomla\Registry\Registry;

/**
 * System plugin for Jcomments
 *
 * @since 1.5
 */
final class Jcomments extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * Should I try to detect and register legacy event listeners, i.e. methods which accept unwrapped arguments? While
	 * this maintains a great degree of backwards compatibility to Joomla! 3.x-style plugins it is much slower. You are
	 * advised to implement your plugins using proper Listeners, methods accepting an AbstractEvent as their sole
	 * parameter, for best performance. Also bear in mind that Joomla! 5.x onwards will only allow proper listeners,
	 * removing support for legacy Listeners.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 *
	 * @deprecated  4.3 will be removed in 6.0
	 */
	protected $allowLegacyListeners = false;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return array(
			'onAfterRender'               => 'onAfterRender',
			'onBeforeCompileHead'         => 'onBeforeCompileHead',
			'onJcommentsShow'             => 'onJcommentsShow',
			'onJcommentsCount'            => 'onJcommentsCount',
			'onJcommentsCommentBeforeAdd' => 'onJcommentsCommentBeforeAdd'
		);
	}

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  $dispatcher  The object to observe
	 * @param   array                $config      An optional associative array of configuration settings.
	 *                                            Recognized key values include 'name', 'group', 'params', 'language'
	 *                                            (this list is not meant to be comprehensive).
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config = array())
	{
		parent::__construct($dispatcher, $config);

		$this->app = $this->getApplication() ?: $this->app;

		// Use this plugin only in site application.
		if (!$this->app->isClient('site'))
		{
			return;
		}

		if (!isset($this->params))
		{
			$this->params = new Registry('');
		}
	}

	/**
	 * After Render Event.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @noinspection PhpUnused
	 */
	public function onAfterRender()
	{
		// Use this plugin only in site application.
		if (!$this->app->isClient('site'))
		{
			return;
		}

		$buffer = $this->app->getBody();

		// Cleanup RSS from {jcomments}
		if ($this->params->get('clear_rss', 0) == 1
			&& $this->app->input->get('option') == 'com_content'
			&& $this->app->getDocument()->getType() === 'feed')
		{
			$buffer = preg_replace('#{jcomments\s+(off|on|lock)}#i', '', $buffer);
			$buffer = \Joomla\String\StringHelper::str_ireplace('{jcomments}', '', $buffer);
			$this->app->setBody($buffer);
		}
	}

	/**
	 * This event is triggered immediately before the framework has rendered the application.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 * @noinspection PhpUnused
	 */
	public function onBeforeCompileHead()
	{
		if ($this->app->getDocument()->getType() == 'html')
		{
			$option = $this->app->input->get('option');

			if ($this->app->isClient('site') && ($option == 'com_content' || $option == 'com_multicategories')
				&& $this->app->input->get('layout') != 'edit')
			{
				JcommentsComponentHelper::loadComponentAssets();
			}
		}
	}

	/**
	 * Do spam checks before comment add.
	 * Return event result as array(true) if not a spam.
	 *
	 * @param   Event  $event  The event
	 *
	 * @return  void
	 *
	 * @see     https://www.stopforumspam.com/usage
	 * @since   4.0.23
	 * @noinspection PhpUnused
	 */
	public function onJcommentsCommentBeforeAdd(Event $event): void
	{
		$params = ComponentHelper::getParams('com_jcomments');
		$data   = $event->getArgument('data');
		$ip     = $data['ip'];
		$result = true;

		if ($params->get('stopforumspam', 0) == 1)
		{
			try
			{
				$httpResponse = HttpFactory::getHttp()->get(
					'https://api.stopforumspam.org/api?ip=' . $ip . '&json',
					array(),
					$params->get('antispam_request_timeout', 3)
				);
				$responseBody = (string) $httpResponse->getBody();
				$responseBody = json_decode($responseBody);

				if ($responseBody->success)
				{
					// 1 - spam
					if ($responseBody->ip->appears == 1)
					{
						Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
						Log::add('Spammer(by StopForumSpam) from IP ' . $ip . ' is trying to send comment.', Log::ERROR, 'com_jcomments');

						$result = false;
					}
				}
			}
			catch (InvalidResponseCodeException $e)
			{
				Log::add('Invalid or undefined HTTP response code while fetching StopForumSpam API.', Log::ERROR, 'com_jcomments');
			}
		}

		$event->setArgument('result', array(array($result)));
	}

	/**
	 * Get total comments for object
	 *
	 * @param   EventInterface  $event  The event
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onJcommentsCount(EventInterface $event)
	{
		/** @var \Joomla\Component\Jcomments\Site\Model\CommentsModel $model */
		$model = $this->app->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Comments', 'Site', array('ignore_request' => true));

		$model->setState('object_id', $event->getArgument('object_id', $this->app->input->getInt('object_id')));
		$model->setState('object_group', $event->getArgument('object_group', $this->app->input->getCmd('object_group', 'com_content')));
		$model->setState('list.options.lang', $event->getArgument('lang'));

		$event->setArgument('result', array(array((int) $model->getTotal())));
	}

	/**
	 * Show comments with form.
	 *
	 * Example usage:
	 * $evt = Factory::getApplication()->getDispatcher()->dispatch(
	 *     'onJcommentsShow',
	 *     \Joomla\CMS\Event\AbstractEvent::create(
	 *         'onJcommentsShow',
	 *         array(
	 *             'eventClass' => 'Joomla\CMS\Event\Event',
	 *             'subject' => new \StdClass, 'object_id' => 2, 'object_group' => 'com_content'
	 *         )
	 *     )
	 * )->getArgument('result', array());
	 * echo trim(implode('', array_key_exists(0, $evt) ? $evt[0] : array()));
	 *
	 * @param   EventInterface  $event  The event
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function onJcommentsShow(EventInterface $event)
	{
		// Only one copy of JComments per page is allowed
		if (defined('JCOMMENTS_SHOW'))
		{
			return;
		}

		// Do not run component when edit form is active.
		if ($this->app->input->get('layout') == 'edit')
		{
			return;
		}

		JcommentsComponentHelper::loadComponentAssets();

		$basePath = JPATH_ROOT . '/components/com_jcomments';
		$view = JcommentsComponentHelper::getView(
			'Comments',
			'Site',
			'Html',
			// View config
			array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
			true,
			// Model config. NOTE! Do not set up `ignore_request` in this because component params will be empty in view when calling getState()
			array(
				'name'      => 'Comments',
				'prefix'    => 'Site',
				'base_path' => $basePath,
				'options'   => array(
					'object_id'    => $event->getArgument('object_id'),
					'object_group' => $event->getArgument('object_group'),
					'object_title' => $event->getArgument('object_title')
				)
			)
		);

		ob_start();

		$view->display();
		$output = ob_get_contents();

		ob_end_clean();

		define('JCOMMENTS_SHOW', 1);

		$event->setArgument('result', array(array($output)));
	}
}
