<?php
/**
 * Frontend subscriptions controller.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

/**
 * Subscriptions frontend controller bridge.
 *
 * @since  5.1.0
 */
final class SubscriptionsController extends LegacyController
{
	/**
	 * Legacy controller segment.
	 *
	 * @var    string
	 * @since  5.1.0
	 */
	protected string $legacyController = 'subscriptions';
}
