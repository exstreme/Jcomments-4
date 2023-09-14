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

namespace Joomla\Component\Jcomments\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * JComments CustomBBCodes table
 *
 * @property string  $name
 * @property string  $simple_pattern
 * @property string  $simple_replacement_html
 * @property string  $simple_replacement_text
 * @property string  $pattern
 * @property string  $replacement_html
 * @property string  $replacement_text
 * @property string  $button_acl
 * @property string  $button_open_tag
 * @property string  $button_close_tag
 * @property string  $button_title
 * @property string  $button_prompt
 * @property string  $button_image
 * @property string  $button_css
 * @property integer $button_enabled
 * @property integer $ordering
 * @property integer $published
 *
 * @since  1.5
 */
class CustombbcodeTable extends Table
{
	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = true;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.5
	 */
	public function __construct($db)
	{
		parent::__construct('#__jcomments_custom_bbcodes', 'id', $db);
	}

	/**
	 * Method to perform sanity checks on the Table instance properties to ensure they are safe to store in the database.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @since   1.7.0
	 */
	public function check()
	{
		if (empty($this->ordering))
		{
			$this->ordering = self::getNextOrder();
		}

		return true;
	}

	/**
	 * Overrides Table::store to set modified data.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function store($updateNulls = true)
	{
		return parent::store($updateNulls);
	}
}
