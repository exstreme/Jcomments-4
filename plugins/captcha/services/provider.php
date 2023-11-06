<?php
/**
 * Kcaptcha image plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2022 Vladimir Globulopolis. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url         https://xn--80aeqbhthr9b.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Captcha\Kcaptcha\Extension\Kcaptcha;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.3.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container)
			{
				$subject = $container->get(DispatcherInterface::class);
				$plugin = new Kcaptcha(
					$subject,
					(array) PluginHelper::getPlugin('captcha', 'kcaptcha')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
