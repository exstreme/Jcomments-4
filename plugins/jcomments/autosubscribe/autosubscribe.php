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

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Class to add support for auto-subscribe feature
 *
 * @since  2.5
 */
class PlgJcommentsAutosubscribe extends CMSPlugin
{
	/**
	 * Subscribes user for new comments notifications for an object
	 *
	 * @param   object|null  $comment  The comment object or null on error.
	 *
	 * @return  void
	 *
	 * @since  2.5
	 */
	public function onJCommentsCommentAfterAdd($comment)
	{
		$components = $this->params->get('object_group', 'com_content');

		if (!is_array($components))
		{
			$components = explode(',', $components);
		}

		// Check is comment's group enabled in plugin settings
		if (in_array($comment->object_group, $components))
		{
			// Get total comments count for an object
			$count = JComments::getCommentsCount($comment->object_id, $comment->object_group);

			// Check that this is first comment to the object
			if ($count <= 1)
			{
				// Get object's owner id
				$owner = JCommentsObject::getOwner($comment->object_id, $comment->object_group);

				// If owner id found - subscribe owner to new comments
				if ($owner > 0 && $owner != $comment->userid)
				{
					require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

					$model = new JcommentsModelSubscriptions;
					$model->subscribe($comment->object_id, $comment->object_group, $owner);
				}
			}
		}
	}
}
