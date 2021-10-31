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
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class com_jcommentsInstallerScript
{
	public function preflight($type, $parent)
	{
		if (!version_compare(JVERSION, '4.0.0', 'ge'))
		{
			echo "<h1>Unsupported Joomla! version</h1>";
			echo "<p>This component can only be installed on Joomla! 4.0 or later</p>";

			return false;
		}

		return true;
	}

	public function postflight($type, $parent)
	{
	    if($type === 'uninstall') {
	        return;
        }
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
		$data->messages = array();
		$data->plugins  = array();

		$src      = $parent->getParent()->getPath('source');
		$manifest = $parent->getParent()->manifest;
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

			$installer = new Installer;
			$result    = $installer->install($path);

			$query = $db->getQuery(true)
			    ->update($db->quoteName('#__extensions'))
			    ->set($db->quoteName('enabled') . ' = 1')
			    ->where($db->quoteName('type') . ' = ' . $db->Quote('plugin'))
			    ->where($db->quoteName('element') . ' = ' . $db->Quote($name))
			    ->where($db->quoteName('folder') . ' = ' . $db->Quote($group));
			    
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
		$zip         = new Zip();
		$zip->extract($source, $destination);
		File::delete($source);

		// Execute database updates
		$scripts = Folder::files(JPATH_ROOT . '/administrator/components/com_jcomments/install/sql/updates', '\.sql',
			true, true);

		foreach ($scripts as $script)
		{
			// TODO Compare current and previous versions number
			$this->executeSQL($script);
		}

		// Fix default guest usergroup in com_users parameters
		// $this->fixGuestUsergroup();

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

		// Some fixes
		$this->fixComponentName();

		// Copy JomSocial rule
		$source      = JPATH_ROOT . '/administrator/components/com_jcomments/install/xml/jomsocial_rule.xml';
		$destination = JPATH_SITE . '/components/com_jcomments/jomsocial_rule.xml';

		if (!is_file($destination))
		{
			File::copy($source, $destination);
		}

		$cache = Factory::getCache('com_jcomments');
		$cache->clean();

		$this->displayResults($data);
	}

	public function update()
	{
		// Delete obsolete files and folders (from previous installations)
		$this->deleteObsoleteFiles();

		// Fix default guest usergroup in com_users parameters
		$this->fixGuestUsergroup();

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

	public function uninstall($parent)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$language = Factory::getApplication()->getLanguage();
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, 'en-GB', true);
		$language->load('com_jcomments', JPATH_ADMINISTRATOR, null, true);

		$messages                = array();
		$messages['content']     = Text::_('A_UNINSTALL_PLUGIN_CONTENT');
		$messages['search']      = Text::_('A_UNINSTALL_PLUGIN_SEARCH');
		$messages['system']      = Text::_('A_UNINSTALL_PLUGIN_SYSTEM');
		$messages['editors-xtd'] = Text::_('A_UNINSTALL_PLUGIN_EDITORS_XTD');

		$data           = new stdClass;
		$data->title    = Text::_('A_UNINSTALL_LOG');
		$data->finish   = Text::_('A_UNINSTALL_COMPLETE');
		$data->messages = array();

		$manifest = $parent->getParent()->manifest;
		$plugins  = $manifest->xpath('plugins/plugin');

		foreach ($plugins as $plugin)
		{
			$name  = (string) $plugin->attributes()->plugin;
			$group = (string) $plugin->attributes()->group;

			$query = $db->getQuery(true)
			    ->select($db->quoteName('extension_id'))
			    ->from($db->quoteName('#__extensions'))
			    ->where($db->quoteName('type') . ' = ' . $db->Quote('plugin'))
			    ->where($db->quoteName('element') . ' = ' . $db->Quote($name))
			    ->where($db->quoteName('folder') . ' = ' . $db->Quote($group));

			$db->setQuery($query);
			$extensions = $db->loadColumn();

			if (count($extensions))
			{
				$result = false;
				foreach ($extensions as $id)
				{
					$installer = new Installer;
					$result    = $installer->uninstall('plugin', $id);
				}

				if (isset($messages[$group]))
				{
					$data->messages[] = array('text' => $messages[$group], 'result' => $result);
					unset($messages[$group]);
				}
			}
		}

		if (Factory::getApplication()->getCfg('caching') != 0)
		{
			$query = $db->getQuery(true)
			    ->select('DISTINCT(' . $db->quoteName('object_group') . ')')
			    ->from($db->quoteName('#__jcomments'));

			$db->setQuery($query);
			$extensions = $db->loadColumn();

			if (count($extensions))
			{
				foreach ($extensions as $extension)
				{
					$cache = Factory::getCache($extension);
					$cache->clean();
				}

				$data->messages[] = array('text' => Text::_('A_UNINSTALL_CLEAN_CACHE'), 'result' => true);
			}
		}

		$this->displayResults($data);
	}

	private function executeSQL($filename = '')
	{
		if (is_file($filename))
		{
			$buffer = file_get_contents($filename);

			if ($buffer === false)
			{
				return false;
			}

			$queries = JDatabaseDriver::splitSql($buffer);

			if (count($queries))
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
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
							error_log($e->getMessage());
						}
					}
				}
			}
		}

		return true;
	}

	private function fixComponentName()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
		    ->update($db->quoteName('#__extensions'))
		    ->set($db->quoteName('name') . ' = ' . $db->Quote('com_jcomments'))
		    ->where($db->quoteName('element') . ' = ' . $db->Quote('com_jcomments'));

		$db->setQuery($query);
		$db->execute();
	}

	private function fixUsergroupsCustomBBCodes()
	{
		$db              = Factory::getContainer()->get('DatabaseDriver');
		$groups          = $this->getUsergroups();
		$guest_usergroup = JComponentHelper::getParams('com_users')->get('guest_usergroup', 1);

		if (count($groups))
		{
			$query = $db->getQuery(true)
			    ->select('*')
			    ->from($db->quoteName('#__jcomments_custom_bbcodes'));

			$where = array();

			foreach ($groups as $group)
			{
				$where[] = $db->quoteName('button_acl') . " LIKE " . $db->Quote('%' . $group->title . '%');
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

				if ($guest_usergroup !== 1 && in_array(1, $values))
				{
					$values[] = $guest_usergroup;
				}

				$row->button_acl = implode(',', $values);

				$query = $db->getQuery(true)
				    ->update($db->quoteName('#__jcomments_custom_bbcodes'))
				    ->set($db->quoteName('button_acl') . ' = ' . $db->Quote($row->button_acl))
				    ->where($db->quoteName('name') . ' = ' . $db->Quote($row->name));

				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	private function fixGuestUsergroup()
	{
		$params          = JComponentHelper::getParams('com_users');
		$guest_usergroup = $params->get('guest_usergroup');

		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
		    ->select('COUNT(*)')
		    ->from($db->quoteName('#__usergroups'))
		    ->where($db->quoteName('id') . ' = ' . (int) $guest_usergroup);

		$db->setQuery($query);
		$count = $db->loadResult();

		if ($count == 0)
		{
			$params->set('guest_usergroup', '1');

			$query = $db->getQuery(true)
			    ->update($db->quoteName('#__extensions'))
			    ->set($db->quoteName('params') . '= ' . $db->quote((string) $params))
			    ->where($db->quoteName('element') . ' = ' . $db->quote('com_users'));

			$db->setQuery($query);
			$db->execute();

			Factory::getCache('com_users')->clean();
			Factory::getCache('_system')->clean();
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

		$files = Folder::files(JPATH_ROOT . '/media/com_jcomments/', '\.(png|gif|css|js)',
			false, true);

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
		require_once JPATH_ROOT . '/administrator/components/com_jcomments/version.php';
		$version = new JCommentsVersion();
		?>
        <style type="text/css">
            .adminform tr th {
                display: none;
            }

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

            #jcomments-installer .btn {
                display: inline-block;
                *display: inline;
                *zoom: 1;
                padding: 4px 14px;
                margin-bottom: 0;
                font-size: 13px;
                line-height: 18px;
                *line-height: 18px;
                text-align: center;
                vertical-align: middle;
                cursor: pointer;
                color: #333;
                text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
                background-color: #f5f5f5;
                background-image: -moz-linear-gradient(top, #fff, #e6e6e6);
                background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#fff), to(#e6e6e6));
                background-image: -webkit-linear-gradient(top, #fff, #e6e6e6);
                background-image: -o-linear-gradient(top, #fff, #e6e6e6);
                background-image: linear-gradient(to bottom, #fff, #e6e6e6);
                background-repeat: repeat-x;
                filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe5e5e5', GradientType=0);
                border-color: #e6e6e6 #e6e6e6 #bfbfbf;
                border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
                *background-color: #e6e6e6;
                filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
                border: 1px solid #bbb;
                *border: 0;
                border-bottom-color: #a2a2a2;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                *margin-left: .3em;
                -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .2), 0 1px 2px rgba(0, 0, 0, .05);
                -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .2), 0 1px 2px rgba(0, 0, 0, .05);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, .2), 0 1px 2px rgba(0, 0, 0, .05);
            }

            #jcomments-installer .btn:hover,
            #jcomments-installer .btn:active,
            #jcomments-installer .btn.active {
                color: #333;
                text-decoration: none;
                background-color: #e6e6e6;
                *background-color: #d9d9d9;
                background-position: 0 -15px;
                -webkit-transition: background-position .1s linear;
                -moz-transition: background-position .1s linear;
                -o-transition: background-position .1s linear;
                transition: background-position .1s linear;
            }

            #jcomments-installer .btn:active,
            #jcomments-installer .btn.active {
                background-color: #cccccc \9;
            }
        </style>
        <div id="jcomments-installer">
            <table width="95%" cellpadding="0" cellspacing="0">
                <tbody>
                <tr>
                    <td width="50px">
                        <img src="http://www.joomlatune.com/images/logo/jcomments.png" alt=""/>
                    </td>
                    <td>
                        <div>
                            <span class="extension-name"><?php echo $version->getLongVersion(); ?></span>
                            <span class="extension-date">[<?php echo $version->getReleaseDate(); ?>]</span>
                        </div>

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
                                    <a href="<?php echo $data->next; ?>" class="btn">
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