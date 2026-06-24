<?php
/**
 * JComments component service provider.
 *
 * @package  JComments
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
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
		$namespace = '\\Joomla\\Component\\Jcomments';

		$container->registerServiceProvider(new MVCFactory($namespace));
		$container->registerServiceProvider(new ComponentDispatcherFactory($namespace));

		$container->set(
			ComponentInterface::class,
			static function (Container $container): JcommentsComponent {
				$component = new JcommentsComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));

				return $component;
			}
		);
	}
};
