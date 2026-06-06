<?php
/**
 * JComments component service provider.
 *
 * @package  JComments
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\Component\Jcomments\Administrator\Dispatcher\LegacyDispatcherFactory;
use Joomla\Component\Jcomments\Administrator\Extension\JcommentsComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface
{
	/**
	 * Register component services.
	 *
	 * @param   Container  $container  The dependency injection container.
	 *
	 * @return  void
	 *
	 * @since   4.1.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			ComponentDispatcherFactoryInterface::class,
			static fn () => new LegacyDispatcherFactory()
		);

		$container->set(
			ComponentInterface::class,
			static fn (Container $container) => new JcommentsComponent(
				$container->get(ComponentDispatcherFactoryInterface::class)
			)
		);
	}
};
