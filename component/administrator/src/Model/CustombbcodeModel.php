<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class CustombbcodeModel extends AdminModel
{
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_jcomments.custombbcode', 'custombbcode', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		if (!$this->canEditState((object) $data))
		{
			$form->setFieldAttribute('published', 'disabled', 'true');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_jcomments.edit.custombbcode.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   object  $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   3.7.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$user = Factory::getApplication()->getIdentity();

		// Disable ACL field if user is not Super user or cannot core.admin
		if (!$user->get('isRoot') || !$user->authorise('core.admin'))
		{
			$form->setFieldAttribute('button_acl', 'disabled', true);
		}
	}

	public function changeButtonState($pks, $state = 1)
	{
		$table = $this->getTable('Custombbcode');
		$key   = $table->getKeyName();
		$db    = $this->getDatabase();

		$query = $db->getQuery(true)
			->update($table->getTableName())
			->set('button_enabled = ' . (int) $state)
			->where($key . ' = ' . implode(' OR ' . $key . ' = ', $pks));

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Duplicate bbcode item(s).
	 *
	 * @param   array  $pks  Array with primary keys
	 *
	 * @return  boolean
	 *
	 * @throws  \Exception
	 * @since   3.0
	 */
	public function duplicate($pks)
	{
		/** @var \Joomla\Component\Jcomments\Administrator\Table\CustombbcodeTable $table */
		$table = $this->getTable('Custombbcode');

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				$table->id        = 0;
				$table->name      = StringHelper::increment($table->name);
				$table->published = 0;

				if (!$table->check() || !$table->store())
				{
					throw new \Exception($table->getError());
				}
			}
			else
			{
				throw new \Exception($table->getError());
			}
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function save($data)
	{
		/** @var \Joomla\Component\Jcomments\Administrator\Table\CustombbcodeTable $table */
		$table  = $this->getTable();
		$pkName = $table->getKeyName();
		$pk     = (!empty($data[$pkName])) ? $data[$pkName] : (int) $this->getState($this->getName() . '.id');

		try
		{
			$oldSimplePattern         = '';
			$oldSimpleReplacementHtml = '';
			$oldSimpleReplacementText = '';

			if ($pk > 0)
			{
				$table->load($pk);

				$oldSimplePattern         = $table->simple_pattern;
				$oldSimpleReplacementHtml = $table->simple_replacement_html;
				$oldSimpleReplacementText = $table->simple_replacement_text;
			}

			if (!$table->bind($data))
			{
				$this->setError($table->getError());

				return false;
			}

			$acl = is_array($data['button_acl']) ? $data['button_acl'] : array();
			ArrayHelper::toInteger($acl);
			$table->button_acl = implode(',', $acl);

			$table->name             = trim(strip_tags($table->name));
			$table->button_open_tag  = trim(strip_tags($table->button_open_tag));
			$table->button_close_tag = trim(strip_tags($table->button_close_tag));
			$table->button_title     = trim(strip_tags($table->button_title));
			$table->button_prompt    = trim(strip_tags($table->button_prompt));
			$table->button_image     = trim(strip_tags($table->button_image));
			$table->button_css       = trim(strip_tags($table->button_css));

			if ($table->simple_replacement_text == '')
			{
				$table->simple_replacement_text = strip_tags($table->simple_replacement_html);
			}

			if ($table->simple_pattern != '' && $table->simple_replacement_html != '')
			{
				$tokens               = array();
				$tokens['TEXT']       = array('([\w0-9-\+\=\!\?\(\)\[\]\{\}\/\&\%\*\#\.,_ ]+)' => '$1');
				$tokens['SIMPLETEXT'] = array('([\A-Za-z0-9-\+\.,_ ]+)' => '$1');
				$tokens['IDENTIFIER'] = array('([\w0-9-_]+)' => '$1');
				$tokens['NUMBER']     = array('([0-9]+)' => '$1');
				$tokens['ALPHA']      = array('([A-Za-z]+)' => '$1');

				$pattern         = preg_quote($table->simple_pattern, '#');
				$replacementHtml = $table->simple_replacement_html;
				$replacementText = $table->simple_replacement_text;

				$m   = array();
				$pad = 0;

				if (preg_match_all('/\{(' . implode('|', array_keys($tokens)) . ')[0-9]*\}/im', $table->simple_pattern, $m))
				{
					foreach ($m[0] as $n => $token)
					{
						$tokenType = $m[1][$n];
						$match     = key($tokens[strtoupper($tokenType)]);
						$replace   = current($tokens[strtoupper($tokenType)]);
						$repad     = array();

						if (preg_match_all('/(?<!\\\\)\$([0-9]+)/', $replace, $repad))
						{
							$repad = $pad + count(array_unique($repad[0]));

							// TODO Test this as replacement for old preg_replace('/(?<!\\\\)\$([0-9]+)/e', "'\${' . (\$1 + \$pad) . '}'", $replace).
							$replace = preg_replace_callback(
								'/(?<!\\\\)\$([0-9]+)/',
								function ($_m) use ($pad)
								{
									return '\${' . ($_m[1] + $pad) . '}';
								},
								$replace
							);
							$pad = $repad;
						}

						$pattern         = str_replace(preg_quote($token, '#'), $match, $pattern);
						$replacementHtml = str_replace($token, $replace, $replacementHtml);
						$replacementText = str_replace($token, $replace, $replacementText);
					}
				}

				// If simple pattern not changed but pattern changed - clear simple
				if ($oldSimplePattern != $table->simple_pattern || $table->pattern == '')
				{
					$table->pattern = $pattern;
				}

				// If simple replacement not changed but pattern changed - clear simple
				if ($oldSimpleReplacementHtml != $table->simple_replacement_html || $table->replacement_html == '')
				{
					$table->replacement_html = $replacementHtml;
				}

				// If simple replacement not changed but pattern changed - clear simple
				if ($oldSimpleReplacementText != $table->simple_replacement_text || $table->replacement_text == '')
				{
					$table->replacement_text = $replacementText;
				}
			}

			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			if (!$table->store())
			{
				$this->setError($table->getError());

				return false;
			}
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		if (isset($table->$pkName))
		{
			$this->setState($this->getName() . '.id', $table->$pkName);
		}

		return true;
	}
}
