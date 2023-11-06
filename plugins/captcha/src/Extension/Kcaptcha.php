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

namespace Joomla\Plugin\Captcha\Kcaptcha\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Plugin\Captcha\Kcaptcha\Library\Kcaptcha as JKcaptcha;

/**
 * Kcaptcha Plugin
 *
 * @since  4.0
 */
final class Kcaptcha extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  4.0.0
	 */
	protected $app;

	/**
	 * Initialise the captcha
	 *
	 * @return  boolean  True on success, false otherwise
	 *
	 * @since   2.5
	 * @throws  \RuntimeException
	 */
	public function onInit()
	{
		// Load assets, the callback should be first
		$this->app->getDocument()->getWebAssetManager()
			->registerAndUseScript('plg_captcha_kcaptcha', 'plg_captcha_kcaptcha/kcaptcha.min.js');

		return true;
	}

	/**
	 * Generate and output captcha image via ajax request.
	 * BEWARE! Do not close application in this method.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   2.0
	 */
	public function onAjaxKcaptcha()
	{
		$session = $this->app->getSession();
		$captcha = new JKcaptcha($this->params->toArray());

		$captcha->render();

		$session->set('comments-captcha-code', $captcha->getKeyString());
	}

	/**
	 * Gets the challenge HTML
	 *
	 * @param   string  $name   The name of the field. Not Used.
	 * @param   string  $id     The id of the field.
	 * @param   string  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since  2.5
	 */
	public function onDisplay($name = null, $id = '', $class = '')
	{
		$dom = new \DOMDocument;
		$div = $dom->createElement('div');

		$div->setAttribute('class', ((trim($class) == '') ? 'captcha-container' : ($class . ' captcha-container')));

		$dom->appendChild($div);

		ob_start();
		$layout = LayoutHelper::render(
			'element',
			(object) array('params' => $this->params, 'id' => $id, 'name' => $name),
			dirname(__DIR__, 2) . '/layouts'
		);

		// Fix ISO-8859-1 in loadHTML() and convert to UTF-8
		$layout = mb_convert_encoding($layout, 'HTML-ENTITIES', 'UTF-8');
		ob_end_clean();

		$dom->loadHTML($layout, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

		return $dom->saveHTML($dom->documentElement);
	}

	/**
	 * Calls an HTTP POST function to verify if the user's guess was correct
	 *
	 * @param   string  $code  Answer provided by user.
	 *
	 * @return  boolean  True if the answer is correct, false otherwise
	 *
	 * @since   2.5
	 */
	public function onCheckAnswer($code = null)
	{
		$session = $this->app->getSession();

		if ($code != '' && $code == $session->get('comments-captcha-code'))
		{
			$result = true;
			$session->remove('comments-captcha-code');
		}
		else
		{
			$result = false;
		}

		return $result;
	}
}
