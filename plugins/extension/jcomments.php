<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Jcomments master extension plugin.
 *
 * @since  4.1
 * @noinspection PhpUnused
 */
class PlgExtensionJcomments extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\SiteApplication
	 *
	 * @since  3.9.0
	 */
	protected $app;

	/**
	 * Change some values before save component parameters.
	 *
	 * @param   string             $context  com_config.component
	 * @param   \Joomla\CMS\Table  $table    Table
	 * @param   boolean            $isNew    New or not
	 *
	 * @return  boolean
	 *
	 * @since   3.2
	 * @see     Joomla\Component\Config\Administrator\Model\ComponentModel::save()
	 */
	public function onExtensionBeforeSave($context, $table, $isNew = false)
	{

		if ($context !== 'com_config.component' || $this->app->input->getCmd('component') != 'com_jcomments')
		{
			return true;
		}

		// Component params
		$params = json_decode($table->params, true);

		// Adjust some JComments settings before save.
		if (!empty($params['forbidden_names']))
		{
			$params['forbidden_names'] = preg_replace("#[\n|\r]+#", ',', $params['forbidden_names']);
			$params['forbidden_names'] = preg_replace("#,+#", ',', $params['forbidden_names']);
		}

		if (!empty($params['badwords']))
		{
			$params['badwords'] = preg_replace('#[\s|,]+#i', "\n", $params['badwords']);
			$params['badwords'] = preg_replace('#[\n|\r]+#i', "\n", $params['badwords']);

			$params['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $params['badwords']));
			$params['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $params['badwords']));
		}

		if ($params['comment_minlength'] > $params['comment_maxlength'])
		{
			$params['comment_minlength'] = 0;
		}

		$table->params = json_encode($params);

		return true;
	}
}
