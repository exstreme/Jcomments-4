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

use Joomla\CMS\MVC\Controller\BaseController;

class JCommentsController extends BaseController
{
	protected $default_view = 'comments';

	public function display($cachable = false, $urlparams = false)
	{
		parent::display();

		return $this;
	}
}