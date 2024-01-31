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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Factory;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;

/**
 * Cache helper class
 *
 * @since  4.1
 */
class CacheHelper
{
	/**
	 * Remove item from cache group or remove cache group
	 *
	 * @param   string       $id      The cache id. If empty, when delete cache group.
	 * @param   string|null  $group   The cache group. Required to delete cache group.
	 * @param   string       $event   Event name
	 * @param   string       $option  Option e.g. component system name
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 * @since   4.1
	 */
	public static function removeCachedItem(string $id, ?string $group, string $event = 'onJcommentsCleanCache', string $option = 'com_jcomments')
	{
		$app = Factory::getApplication();

		// Joomla create directory with defaultgroup name even if caching is turned off. Avoid this brhavior here.
		if (!$app->get('caching'))
		{
			return;
		}

		$options = [
			'defaultgroup' => $group ?: ($option ?? $app->getInput()->get('option')),
			'cachebase'    => $app->get('cache_path', JPATH_CACHE),
			'result'       => true,
		];

		try
		{
			/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('callback', $options);

			if (empty($id) && !empty($group))
			{
				$cache->clean($group);
			}
			else
			{
				$cache->remove($id, $group);
			}
		}
		catch (CacheExceptionInterface $exception)
		{
			$options['result'] = false;
		}

		// Trigger the onJcommentsCleanCache event.
		/** @var DispatcherInterface $dispatcher */
		$dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
		$dispatcher->dispatch($event, new Event($event, $options));
	}
}
