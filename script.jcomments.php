<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\Archive\Zip;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

/**
 * Script file of Joomla CMS
 *
 * @since  4.0
 */
class com_jcommentsInstallerScript
{
	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string     $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   Installer  $installer  The class calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.7.0
	 */
	public function preflight($action, $installer)
	{
		if (!version_compare(JVERSION, '4.0.0', 'ge'))
		{
			echo "<h1>Unsupported Joomla! version</h1>";
			echo "<p>This component can only be installed on Joomla! 4.0 or later</p>";

			return false;
		}

		return true;
	}


	/**
	 * Called after any type of action
	 *
	 * @param   string            $action     Which action is happening (install|uninstall|discover_install|update)
	 * @param   InstallerAdapter  $installer  The class calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  Exception
	 * @since   4.0.0
	 */
	public function postflight($action, $installer)
	{
		if ($action === 'uninstall')
		{
			return true;
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, 'en-GB', true);
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, null, true);

		$messages                = array();
		$messages['content']     = Text::_('A_INSTALL_PLUGIN_CONTENT');
		$messages['search']      = Text::_('A_INSTALL_PLUGIN_SEARCH');
		$messages['system']      = Text::_('A_INSTALL_PLUGIN_SYSTEM');
		$messages['user']        = Text::_('A_INSTALL_PLUGIN_USER');
		$messages['editors-xtd'] = Text::_('A_INSTALL_PLUGIN_EDITORS_XTD');

		$data           = new stdClass;
		$data->title    = Text::_('A_INSTALL_LOG');
		$data->finish   = Text::_('A_INSTALL_COMPLETE');
		$data->next     = Uri::root() . 'administrator/index.php?option=com_jcomments&view=settings';
		$data->action   = $action;
		$data->messages = array();
		$data->plugins  = array();

		$src      = $installer->getParent()->getPath('source');
		$manifest = $installer->getParent()->getManifest();
		$plugins  = $manifest->xpath('plugins/plugin');

		foreach ($plugins as $plugin)
		{
			$name  = (string) $plugin->attributes()->plugin;
			$group = (string) $plugin->attributes()->group;
			$path  = $src . '/plugins/' . $group;

			if (Folder::exists($src . '/plugins/' . $group . '/' . $name))
			{
				$path = $src . '/plugins/' . $group . '/' . $name;
			}

			$_installer = new Installer;
			$result     = $_installer->install($path);

			$query = $db->getQuery(true)
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('enabled') . ' = 1')
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('element') . ' = ' . $db->quote($name))
				->where($db->quoteName('folder') . ' = ' . $db->quote($group));

			$db->setQuery($query);
			$db->execute();

			if (isset($messages[$group]))
			{
				$data->messages[] = array('text' => $messages[$group], 'result' => $result);
				unset($messages[$group]);
			}

			$data->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
		}

		// Extract plugins for integration with 3rd party extensions
		$source      = JPATH_SITE . '/components/com_jcomments/plugins/plugins.zip';
		$destination = JPATH_SITE . '/components/com_jcomments/plugins/';
		$zip         = new Zip;
		$zip->extract($source, $destination);
		File::delete($source);

		// Execute database updates
		$scripts = Folder::files(
			JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/updates', '\.sql',
			true,
			true
		);

		foreach ($scripts as $script)
		{
			// TODO Compare current and previous versions number
			$this->executeSQL($script);
		}

		// Load default custom bbcodes
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments_custom_bbcodes'));

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count == 0)
		{
			$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/default.custom_bbcodes.sql');
			$this->fixUsergroupsCustomBBCodes();
		}

		// Load default smilies
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments_smilies'));

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count == 0)
		{
			$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/default.smilies.sql');
		}

		// Load default access rules
		$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/default.access.sql');

		// Copy JomSocial rule
		$source      = JPATH_ROOT . '/administrator/components/com_jcomments/install/xml/jomsocial_rule.xml';
		$destination = JPATH_SITE . '/components/com_jcomments/jomsocial_rule.xml';

		if (!is_file($destination))
		{
			File::copy($source, $destination);
		}

		$this->setComponentParams();
		$this->cleanCache('com_jcomments');
		$this->displayResults($data);

		return true;
	}

	/**
	 * Method to update Joomla!
	 *
	 * @param   Installer  $installer  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function update($installer)
	{
		// Delete obsolete files and folders (from previous installations)
		$this->deleteObsoleteFiles();

		// Copy smilies from old folder to new one
		try
		{
			$oldPath = JPATH_SITE . '/media/com_jcomments/images/smiles';
			$newPath = JPATH_SITE . '/media/com_jcomments/images/smilies';

			if (is_dir($oldPath))
			{
				$files = Folder::files($oldPath);

				foreach ($files as $file)
				{
					if (!is_file($newPath . '/' . $file))
					{
						File::copy($oldPath . '/' . $file, $newPath . '/' . $file);
					}
				}

				Folder::delete($oldPath);
			}
		}
		catch (Exception $e)
		{
		}
	}

	/**
	 * Called on uninstall
	 *
	 * @param   InstallerAdapter  $installer  The class calling this method
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   4.0.0
	 */
	public function uninstall($installer)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, 'en-GB', true);
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, null, true);

		$data           = new stdClass;
		$data->title    = Text::_('A_UNINSTALL_LOG');
		$data->finish   = Text::_('A_UNINSTALL_COMPLETE');
		$data->action   = 'uninstall';
		$data->messages = array();

		if (Factory::getApplication()->get('caching') != 0)
		{
			$query = $db->getQuery(true)
				->select('DISTINCT(' . $db->quoteName('object_group') . ')')
				->from($db->quoteName('#__jcomments'));

			$db->setQuery($query);
			$extensions = $db->loadColumn();

			if (count($extensions))
			{
				$this->cleanCache($extensions);
				$data->messages[] = array('text' => Text::_('A_UNINSTALL_CLEAN_CACHE'), 'result' => true);
			}
		}

		$this->displayResults($data);
	}

	/**
	 * Clean cache after some actions.
	 *
	 * @param   array|string  $objects  Array this cache object names or string.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	private function cleanCache($objects)
	{
		if (is_array($objects))
		{
			foreach ($objects as $object)
			{
				/** @var CallbackController $cache */
				$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
					->createCacheController('callback', ['defaultgroup' => $object]);

				/** @var Cache $cache */
				$cache->clean();
			}
		}
		else
		{
			$this->cleanCache(array($objects));
		}
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string   $name     The name of the form.
	 * @param   string   $source   The form source. Can be XML string if file flag is set to false.
	 * @param   string   $xpath    An optional xpath to search for the fields.
	 *
	 * @return  Form
	 *
	 * @since   4.0.0
	 * @throws  Exception
	 */
	private function loadForm($name, $source = null, $xpath = null)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_jcomments/');

		return Form::getInstance($name, $source, array('control' => 'jform', 'load_data' => array()), true, $xpath);
	}

	/**
	 * Set up component parameters from config.xml
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	private function setComponentParams()
	{
		/** @var DatabaseDriver $db */
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$form   = $this->loadForm('com_jcomments.config', 'config', '/config');
		$params = array();

		// Get the fieldset names
		$nameFieldsets = array();

		foreach ($form->getFieldsets() as $fieldset)
		{
			$nameFieldsets[] = $fieldset->name;
		}

		foreach ($nameFieldsets as $fieldsetName)
		{
			foreach ($form->getFieldset($fieldsetName) as $field)
			{
				$fieldname = $field->getAttribute('name');
				$params[$fieldname] = $field->getAttribute('default');

				if ($field->getAttribute('type') == 'subform')
				{
					$params[$fieldname] = json_decode($field->getAttribute('default'));
				}
			}
		}

		// Set some special field values
		$params['captcha_engine']   = 'kcaptcha';
		$params['kcaptcha_credits'] = '';
		$params['badwords']         = '';
		unset($params['rules']);

		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('params') . " = '" . $db->escape(json_encode($params)) . "'")
			->where($db->quoteName('element') . " = 'com_jcomments'")
			->where($db->quoteName('type') . " = 'component'");

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Execute installation sql files.
	 *
	 * @param   string  $filename  Filename with sql.
	 *
	 * @return  boolean
	 *
	 * @since   4.0
	 */
	private function executeSQL($filename = '')
	{
		if (is_file($filename))
		{
			$buffer = file_get_contents($filename);

			if ($buffer === false)
			{
				return false;
			}

			/** @var DatabaseDriver $db */
			$db      = Factory::getContainer()->get('DatabaseDriver');
			$queries = $db->splitSql($buffer);

			if (count($queries))
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query[0] != '#')
					{
						try
						{
							$db->setQuery($query);
							$db->execute();
						}
						catch (RuntimeException $e)
						{
							Log::add($e->getMessage(), Log::EMERGENCY, 'com_jcomments');
						}
					}
				}
			}
		}

		return true;
	}

	private function fixUsergroupsCustomBBCodes()
	{
		$db             = Factory::getContainer()->get('DatabaseDriver');
		$groups         = $this->getUsergroups();
		$guestUsergroup = ComponentHelper::getParams('com_users')->get('guest_usergroup', 9);

		if (count($groups))
		{
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__jcomments_custom_bbcodes'));

			$where = array();

			foreach ($groups as $group)
			{
				$where[] = $db->quoteName('button_acl') . " LIKE " . $db->quote('%' . $group->title . '%');
			}

			if (count($where))
			{
				$query->where('(' . implode(' OR ', $where) . ')');
			}

			$db->setQuery($query);
			$rows = $db->loadObjectList();

			foreach ($rows as $row)
			{
				$values = explode(',', $row->button_acl);

				foreach ($groups as $group)
				{
					for ($i = 0, $n = count($values); $i < $n; $i++)
					{
						if ($values[$i] == $group->title)
						{
							$values[$i] = $group->id;
						}
					}
				}

				if ($guestUsergroup !== 1 && in_array(1, $values))
				{
					$values[] = $guestUsergroup;
				}

				$row->button_acl = implode(',', $values);

				$query = $db->getQuery(true)
					->update($db->quoteName('#__jcomments_custom_bbcodes'))
					->set($db->quoteName('button_acl') . ' = ' . $db->quote($row->button_acl))
					->where($db->quoteName('name') . ' = ' . $db->quote($row->name));

				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	private function getUsergroups()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('a.*, COUNT(DISTINCT b.id) AS level')
			->from($db->quoteName('#__usergroups') . ' AS a')
			->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
			->order('a.lft ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	private function deleteObsoleteFiles()
	{
		$files = array(
			'/components/com_jcomments/helpers/html.php'
		, '/components/com_jcomments/jcomments.config.php'
		, '/administrator/components/com_jcomments/admin.jcomments.blacklist.php'
		, '/administrator/components/com_jcomments/admin.jcomments.custombbcodes.php'
		, '/administrator/components/com_jcomments/admin.jcomments.html.php'
		, '/administrator/components/com_jcomments/admin.jcomments.installer.php'
		, '/administrator/components/com_jcomments/admin.jcomments.subscription.php'
		, '/administrator/components/com_jcomments/admin.jcomments.php'
		, '/administrator/components/com_jcomments/classes/objectinfo.php'
		, '/administrator/components/com_jcomments/install.jcomments.php'
		, '/administrator/components/com_jcomments/install/sql/custom_bbcodes.sql'
		, '/administrator/components/com_jcomments/install/sql/install.mysql.nonutf8.sql'
		, '/administrator/components/com_jcomments/install/sql/settings.sql'
		, '/administrator/components/com_jcomments/install/sql/custom_bbcodes.sql'
		, '/administrator/components/com_jcomments/install/xml/config.xm'
		, '/administrator/components/com_jcomments/install/xml/jomsocial_rule.xm'
		, '/administrator/components/com_jcomments/toolbar.jcomments.html.php'
		, '/administrator/components/com_jcomments/toolbar.jcomments.php'
		, '/administrator/components/com_jcomments/uninstall.jcomments.php'
		);

		$folders = array(
			'/components/com_jcomments/languages'
		, '/components/com_jcomments/libraries/convert'
		, '/components/com_jcomments/libraries/joomlatune/joomla'
		, '/administrator/components/com_jcomments/classes/button'
		, '/administrator/components/com_jcomments/elements'
		, '/administrator/components/com_jcomments/fields'
		, '/administrator/components/com_jcomments/install/helpers'
		, '/administrator/components/com_jcomments/install/plugins'
		);

		foreach ($files as $file)
		{
			if (File::exists(JPATH_ROOT . $file))
			{
				try
				{
					File::delete(JPATH_ROOT . $file);
				}
				catch (Exception $e)
				{
				}
			}
		}

		foreach ($folders as $folder)
		{
			if (Folder::exists(JPATH_ROOT . $folder))
			{
				try
				{
					Folder::delete(JPATH_ROOT . $folder);
				}
				catch (Exception $e)
				{
				}
			}
		}

		$files = Folder::files(
			JPATH_ROOT . '/media/com_jcomments/', '\.(png|gif|css|js)',
			false,
			true
		);

		foreach ($files as $file)
		{
			try
			{
				File::delete($file);
			}
			catch (Exception $e)
			{
			}
		}
	}

	private function displayResults($data)
	{
		?>
		<style>
			#jcomments-installer {
				margin: 10px auto;
				padding: 8px;
				width: 700px;
				min-height: 48px;
				background-color: #fff;
				border: 1px solid #ccc;
				-webkit-border-radius: 10px;
				-moz-border-radius: 10px;
				border-radius: 10px;
			}

			#jcomments-installer .status-error {
				color: red;
			}

			#jcomments-installer .status-ok {
				color: green;
			}

			#jcomments-installer .extension-copyright {
				color: #777;
				display: block;
				margin-top: 12px;
			}

			#jcomments-installer .extension-name {
				color: #FF9900;
				font-family: Arial, Helvetica, sans-serif;
				font-size: 16px;
				font-weight: bold;
			}

			#jcomments-installer .extension-date {
				color: #FF9900;
				font-family: Arial, Helvetica, sans-serif;
				font-size: 16px;
				font-weight: normal;
			}

			#jcomments-installer .installer-messages-header {
				margin: 10px 0;
				color: #FF9900;
				font-family: Arial, Helvetica, sans-serif;
				font-size: 16px;
				font-weight: bold;
			}

			#jcomments-installer ul {
				padding: 0 0 0 20px;
				margin: 0 0 10px 0;
			}

			#jcomments-installer table {
				padding: 0;
				margin: 0;
				border: none;
			}

			#jcomments-installer table td {
				vertical-align: top;
			}
		</style>
		<div id="jcomments-installer">
			<table class="table">
				<tbody>
				<tr>
					<?php if ($data->action !== 'uninstall'): ?>
					<td>
						<p style="margin: 1em;">
							<img src="<?php echo Uri::root(); ?>media/com_jcomments/images/icon-48-jcomments.png" alt="JComments"/>
						</p>
					</td>
					<?php endif; ?>
					<td>
						<?php if ($data->action !== 'uninstall'):
							require_once JPATH_ROOT . '/administrator/components/com_jcomments/version.php';
							$version = new JCommentsVersion;
						?>
						<div>
							<span class="extension-name"><?php echo $version->getLongVersion(); ?></span>
							<span class="extension-date">[<?php echo $version->getReleaseDate(); ?>]</span>
						</div>
						<?php endif; ?>

						<div class="extension-copyright">
							&copy; 2006-<?php echo date('Y'); ?> smart (<a
									href="http://www.joomlatune.ru">JoomlaTune.ru</a> | <a
									href="http://www.joomlatune.com">JoomlaTune.com</a>).
							<?php echo Text::_('A_ABOUT_COPYRIGHT'); ?>
						</div>

						<div class="installer-messages-header">
							<?php echo $data->title; ?>
						</div>

						<div>
							<ul>
								<?php if (count($data->messages)): ?>
									<?php foreach ($data->messages as $message):
										$class = $message['result'] ? 'status-ok' : 'status-error';
										$text = $message['result'] ? Text::_('A_INSTALL_STATE_OK') : Text::_('A_INSTALL_STATE_ERROR');
										?>
										<li>
											<?php echo $message['text']; ?>:
											<span class="<?php echo $class; ?>"><?php echo $text; ?></span>
										</li>
									<?php endforeach; ?>
								<?php endif; ?>
								<li>
									<span style="color: green"><strong><?php echo $data->finish; ?></strong></span>
								</li>
							</ul>
						</div>
						<?php if (!empty($data->next)): ?>
							<div>
								<div class="jcomments-installer-next">
									<a href="<?php echo $data->next; ?>" class="btn btn-success btn-sm">
										<?php echo Text::_('A_INSTALL_BUTTON_NEXT'); ?>
									</a>
								</div>
							</div>
						<?php endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}
