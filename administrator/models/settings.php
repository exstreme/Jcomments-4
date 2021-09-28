<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Table\Table;

class JCommentsModelSettings extends JCommentsModelForm
{
	protected $context = null;

	public function __construct($config = array())
	{
		parent::__construct($config);

		if (empty($this->context))
		{
			$this->context = strtolower($this->option . '.' . $this->getName());
		}
	}

	public function getItem($pk = null)
	{
		$language = $this->getState($this->getName() . '.language');
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select("*")
			->from($db->quoteName('#__jcomments_settings'))
			->where($db->quoteName('component') . '=' . $db->quote(''))
			->where($db->quoteName('lang') . '=' . $db->quote($language));

		$db->setQuery($query);
		$params = $db->loadObjectList();

		$item = new StdClass;

		if (is_array($params))
		{
			$exclude = $this->getExclude();

			foreach ($params as $param)
			{
				$key   = $param->name;
				$value = $param->value;

				if (!in_array($key, $exclude))
				{
					$item->$key = $value;
				}
			}
		}

		return $item;
	}

	public function getExclude()
	{
		return array('enable_geshi');
	}

	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.settings', 'settings', array('control'   => 'jform',
		                                                                    'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.settings.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			$parameters = array('notification_type', 'enable_categories');

			foreach ($parameters as $parameter)
			{
				if (isset($data->$parameter))
				{
					$data->$parameter = explode(',', $data->$parameter);
				}
			}
		}

		return $data;
	}

	public function getTable($type = 'Settings', $prefix = 'JCommentsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	public function getLanguages()
	{
		static $languages = null;

		if (!isset($languages))
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select('enabled')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
				->where($db->quoteName('element') . ' = ' . $db->quote('languagefilter'));

			$db->setQuery($query);
			$enabled = $db->loadResult();

			if ($enabled)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->quoteName('#__languages'))
					->where($db->quoteName('published') . '= 1');

				$db->setQuery($query);
				$languages = $db->loadObjectList();
				$languages = is_array($languages) ? $languages : array();
			}
			else
			{
				$languages = array();
			}
		}

		return $languages;
	}

	public function getUserGroups()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level, a.parent_id')
			->from('#__usergroups AS a')
			->leftJoin($db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id, a.title, a.lft, a.rgt, a.parent_id')
			->order('a.lft ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getPermissionForms()
	{
		Form::addFormPath(JPATH_COMPONENT . '/models/forms');
		Form::addFieldPath(JPATH_COMPONENT . '/models/fields');

		$item   = $this->getItem();
		$groups = $this->getUserGroups();
		$form   = Form::getInstance('jcomments.permissions', 'permissions', array('control' => ''), false, '/permissions');

		$parameters = array();

		foreach ($form->getFieldsets() as $fieldset)
		{
			foreach ($form->getFieldset($fieldset->name) as $field)
			{
				$name              = $field->fieldname;
				$parameters[$name] = !empty($item->$name) ? explode(',', $item->$name) : array();
			}
		}

		$groupParameters = array();

		foreach ($groups as $group)
		{
			foreach ($parameters as $key => $values)
			{
				$groupParameters[$group->value][$key] = array('group' => $group->value,
				                                              'value' => in_array($group->value, $values) ? $group->value : null);
			}
		}

		$forms = array();

		foreach ($groups as $group)
		{
			$form = Form::getInstance('jcomments.permissions.' . $group->value, 'permissions', array('control' => 'jform'), false, '/permissions');
			$form->bind($groupParameters[$group->value]);
			$forms[$group->value] = $form;
		}

		return $forms;
	}

	public function save($data)
	{
		$language = $this->getState($this->getName() . '.language');

		if (is_array($data))
		{
			$config = JCommentsFactory::getConfig();

			Form::addFormPath(JPATH_COMPONENT . '/models/forms');
			Form::addFieldPath(JPATH_COMPONENT . '/models/fields');
			$form = Form::getInstance('jcomments.permissions', 'permissions', array('control' => ''), false, '/permissions');

			foreach ($form->getFieldsets() as $fieldset)
			{
				foreach ($form->getFieldset($fieldset->name) as $field)
				{
					$key = $field->fieldname;
					if (!isset($data[$key]))
					{
						$data[$key] = '';
					}
				}
			}

			$form = Form::getInstance('jcomments.settings', 'settings', array('control' => ''), false);
			foreach ($form->getFieldsets() as $fieldset)
			{
				foreach ($form->getFieldset($fieldset->name) as $field)
				{
					$key = $field->fieldname;
					if (!isset($data[$key]))
					{
						$data[$key] = '';
					}
				}
			}

			if ($data['captcha_engine'] != 'kcaptcha')
			{
				$plugin = $data['captcha_engine'] == 'joomladefault' ? Factory::getApplication()->get('captcha') : $data['captcha_engine'];

				if (($captcha = Captcha::getInstance($plugin, array('namespace' => 'jcomments'))) == null)
				{
					return false;
				}
			}

			if (isset($data['forbidden_names']))
			{
				$data['forbidden_names'] = preg_replace("#[\n|\r]+#", ',', $data['forbidden_names']);
				$data['forbidden_names'] = preg_replace("#,+#", ',', $data['forbidden_names']);
			}

			if (isset($data['badwords']))
			{
				$data['badwords'] = preg_replace('#[\s|\,]+#i', "\n", $data['badwords']);
				$data['badwords'] = preg_replace('#[\n|\r]+#i', "\n", $data['badwords']);

				$data['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $data['badwords']));
				$data['badwords'] = preg_replace("#,+#", ',', preg_replace("#[\n|\r]+#", ',', $data['badwords']));
			}

			if (!isset($data['smilies']))
			{
				$data['smilies'] = $config->get('smilies');
			}

			if (!isset($data['smilies_path']))
			{
				$data['smilies_path'] = $config->get('smilies_path');
			}

			if (!isset($data['comment_minlength']))
			{
				$data['comment_minlength'] = 0;
			}

			if (!isset($data['comment_maxlength']))
			{
				$data['comment_maxlength'] = 0;
			}

			if ($data['comment_minlength'] > $data['comment_maxlength'])
			{
				$data['comment_minlength'] = 0;
			}

			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('name'))
				->from($db->quoteName('#__jcomments_settings'))
				->where($db->quoteName('component') . '=' . $db->quote(''))
				->where($db->quoteName('lang') . '=' . $db->quote($language));

			$db->setQuery($query);
			$params = $db->loadColumn();

			$excludes = $this->getExclude();

			foreach ($data as $key => $value)
			{
				if (!in_array($key, $excludes))
				{
					if (is_array($value))
					{
						$value = implode(',', $value);

						if ($key == 'enable_categories')
						{
							if (strpos($value, '*') !== false)
							{
								$value = '*';
							}
						}
					}

					if (!function_exists('get_magic_quotes_gpc') || get_magic_quotes_gpc())
					{
						$value = stripslashes($value);
					}

					$value = trim($value);

					$config->set($key, $value);

					if (in_array($key, $params))
					{
						$query = $db->getQuery(true)
							->update($db->quoteName('#__jcomments_settings'))
							->set($db->quoteName('value') . '=' . $db->quote($value))
							->where($db->quoteName('component') . '=' . $db->quote(''))
							->where($db->quoteName('lang') . '=' . $db->quote($language))
							->where($db->quoteName('name') . '=' . $db->quote($key));

						$db->setQuery($query);
						$db->execute();
					}
					else
					{
						$query = $db->getQuery(true)
							->insert($db->quoteName('#__jcomments_settings'))
							->set($db->quoteName('value') . '=' . $db->quote($value))
							->set($db->quoteName('component') . '=' . $db->quote(''))
							->set($db->quoteName('lang') . '=' . $db->quote($language))
							->set($db->quoteName('name') . '=' . $db->quote($key));

						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		return true;
	}

	public function reset()
	{
		return true;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();
		$languages = $this->getLanguages();

		if (count($languages))
		{
			$language = $app->getUserStateFromRequest($this->context . '.language', 'language');

			if (empty($language))
			{
				$languages = LanguageHelper::getLanguages();
				$language  = isset($languages[0]->lang_code) ? $languages[0]->lang_code : '';
			}
		}
		else
		{
			$language = '';
		}

		$this->setState($this->getName() . '.language', $language);
	}
}
