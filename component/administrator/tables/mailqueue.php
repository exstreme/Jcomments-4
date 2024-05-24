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

use Joomla\CMS\Table\Table;

/**
 * JComments mail queue table
 */
class JCommentsTableMailqueue extends Table
{
	protected $_supportNullValue = true;

	/** @var integer Primary key */
	public $id = null;

	/** @var string */
	public $name = null;

	/** @var string */
	public $email = null;

	/** @var string */
	public $subject = null;

	/** @var string */
	public $body = null;

	/** @var datetime */
	public $created = null;

	/** @var integer */
	public $attempts = null;

	/** @var integer */
	public $priority = null;

	/** @var string */
	public $session_id = null;

	public function __construct($_db)
	{
		parent::__construct('#__jcomments_mailq', 'id', $_db);
	}
}
