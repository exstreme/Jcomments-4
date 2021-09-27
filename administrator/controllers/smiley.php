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

class JCommentsControllerSmiley extends JCommentsControllerForm
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->view_list = 'smilies';
	}
}
