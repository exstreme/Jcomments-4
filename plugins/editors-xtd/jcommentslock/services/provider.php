<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				require_once __DIR__ . '/../jcommentslock.php';

				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin     = new PlgButtonJCommentsLock($dispatcher, (array) PluginHelper::getPlugin('editors-xtd', 'jcommentslock'));

				$plugin->setApplication(Factory::getApplication());

				if (method_exists($plugin, 'setDatabase'))
				{
					$plugin->setDatabase($container->get(DatabaseInterface::class));
				}

				return $plugin;
			}
		);
	}
};
