<?php
/**
 * JComments - Enable auto-subscribe feature for authors of commented objects
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\Jcomments\Autosubscribe\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Jcomments\Site\Helper\ObjectHelper;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;

/**
 * Class to add support for auto-subscribe feature
 *
 * @since  2.5
 */
final class Autosubscribe extends CMSPlugin implements SubscriberInterface
{
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
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return array(
			'onJCommentsCommentAfterAdd' => 'onJCommentsCommentAfterAdd'
		);
	}

	/**
	 * Subscribes user for new comments notifications for an object
	 *
	 * @param   EventInterface  $event  The event
	 *
	 * @return  void
	 *
	 * @since  2.5
	 */
	public function onJCommentsCommentAfterAdd(EventInterface $event)
	{
		$components = $this->params->get('object_group', 'com_content');

		// Comment object
		$data = $event->getArgument('0');

		if (!is_array($components))
		{
			$components = explode(',', $components);
		}

		// Check is comment's group enabled in plugin settings
		if (in_array($data->object_group, $components))
		{
			// Get total comments count for an object
			if (isset($data->total_comments))
			{
				$count = (int) $data->total_comments;
			}
			else
			{
				$count = ObjectHelper::getTotalCommentsForObject($data->object_id, $data->object_group);
			}

			// Check that this is first comment to the object
			if ($count <= 1)
			{
				// Get object's owner id
				if (isset($data->object_owner))
				{
					$owner = (int) $data->object_owner;
				}
				else
				{
					$owner = ObjectHelper::getObjectField(null, 'object_owner', $data->object_id, $data->object_group);
				}

				// If owner ID found - subscribe owner to new comments
				if ($owner > 0 && $owner != $data->userid)
				{
					/** @var \Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
					$model = Factory::getApplication()->bootComponent('com_jcomments')->getMVCFactory()
						->createModel('Subscription', 'Site');
					$model->subscribe($data->object_id, $data->object_group, $owner);
				}
			}
		}
	}
}
