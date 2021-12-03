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

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;

/**
 * JComments package installer class
 *
 * @since  4.0
 */
class pkg_jcommentsInstallerScript
{
	/**
	 * Component logo in data:image/png format
	 *
	 * @var    string
	 * @since  4.0
	 */
	private $logo;

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
		if ($action === 'remove' || $action === 'uninstall')
		{
			return true;
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, 'en-GB', true);
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, null, true);

		$this->getLogo();
		$componentXML = Installer::parseXMLInstallFile(
			Path::clean(JPATH_ROOT . '/administrator/components/com_jcomments/jcomments.xml')
		);

		$data           = new stdClass;
		$data->finish   = Text::_('A_INSTALL_COMPLETE');
		$data->next     = Uri::root() . 'administrator/index.php?option=com_jcomments&view=settings';
		$data->action   = $action;
		$data->xml      = $componentXML;

		// Load default custom bbcodes
		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments_custom_bbcodes'));

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count == 0)
		{
			$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/mysql/default.custom_bbcodes.sql');
			$this->fixUsergroupsCustomBBCodes();
		}

		// Load default smilies
		$query = $db->getQuery(true)
			->select('COUNT(id)')
			->from($db->quoteName('#__jcomments_smilies'));

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count == 0)
		{
			$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/mysql/default.smilies.sql');
		}

		// Load default access rules
		$this->executeSQL(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/mysql/default.access.sql');

		// Copy JomSocial rule
		$source      = JPATH_ROOT . '/administrator/components/com_jcomments/install/xml/jomsocial_rule.xml';
		$destination = JPATH_SITE . '/components/com_jcomments/jomsocial_rule.xml';

		if (!is_file($destination))
		{
			File::copy($source, $destination);
		}

		// Enable plugins
		$this->enablePlugins(1);

		$this->setComponentParams();
		$this->cleanCache('com_jcomments');
		$this->displayResults($data);

		return true;
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

		// Disable plugins
		$this->enablePlugins(0);

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, 'en-GB', true);
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, null, true);

		$this->getLogo();
		$componentXML = Installer::parseXMLInstallFile(
			Path::clean(JPATH_ROOT . '/administrator/components/com_jcomments/jcomments.xml')
		);

		$data           = new stdClass;
		$data->finish   = Text::_('A_UNINSTALL_COMPLETE');
		$data->action   = 'uninstall';
		$data->xml      = $componentXML;
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
	 * Enable or disable component plugins.
	 *
	 * @param   integer  $state  Item state.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	private function enablePlugins($state)
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = ' . (int) $state)
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->extendWhere(
				'AND',
				array(
					$db->quoteName('element') . ' = ' . $db->quote('jcomments'),
					$db->quoteName('element') . ' = ' . $db->quote('jcommentson'),
					$db->quoteName('element') . ' = ' . $db->quote('jcommentsoff')
				),
				'OR'
			);

		$db->setQuery($query);
		$db->execute();
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
	 * Get component logo.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	private function getLogo()
	{
		$logo = file_get_contents(JPATH_ROOT . '/media/com_jcomments/images/icon-48-jcomments.png');

		$this->logo = 'data:image/png;base64,' . base64_encode($logo);
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
				elseif (!is_null($field->getAttribute('multiple')))
				{
					$params[$fieldname] = explode(',', $field->getAttribute('default'));
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

	private function displayResults($data)
	{
		?>
		<style>
			.jcomments .text-success {
				margin: 1rem 0;
			}

			.jcomments .text-success strong {
				font-size: 1.5em;
			}
		</style>
		<div class="jcomments">
			<table class="table">
				<tbody>
				<tr>
					<td>
						<p style="margin: 1em;">
							<img src="<?php echo $this->logo; ?>" alt="JComments"/>
						</p>
					</td>
					<td>
						<span class="text-warning"><strong>JComments <?php echo $data->xml['version']; ?></strong></span>
						<span class="text-warning">[<?php echo $data->xml['creationDate']; ?>]</span>
						<br><br>
						<div>
							<?php
							preg_match('(\d+\.\d+)', $data->xml['version'], $matches);
							echo Text::sprintf(
								'A_ABOUT_JCOMMENTS_GITHUB_PROJECT',
								$matches[0]
							); ?>
						</div>
						<div class="text-secondary"><?php echo Text::sprintf('A_ABOUT_COPYRIGHTS', date('Y')); ?></div>
						<div class="text-success"><strong><?php echo $data->finish; ?></strong></div>
						<?php if (!empty($data->next)): ?>
							<div>
								<a href="<?php echo $data->next; ?>" class="btn btn-success">
									<?php echo Text::_('JNEXT'); ?>
								</a>
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
