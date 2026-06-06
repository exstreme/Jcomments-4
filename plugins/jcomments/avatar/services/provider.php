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
				$bootstrap = JPATH_ROOT . '/components/com_jcomments/classes/bootstrap.php';

				if (is_file($bootstrap))
				{
					require_once $bootstrap;
					JCommentsLegacyBootstrap::register();
				}

				require_once __DIR__ . '/../avatar.php';

				$dispatcher = $container->get(DispatcherInterface::class);
				$plugin     = new PlgJcommentsAvatar($dispatcher, (array) PluginHelper::getPlugin('jcomments', 'avatar'));

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
