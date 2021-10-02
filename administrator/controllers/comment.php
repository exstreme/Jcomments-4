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

use Joomla\CMS\Factory;

class JCommentsControllerComment extends JCommentsControllerForm
{
	public function deleteReportAjax()
	{
		$id     = $this->input->getInt('id');
		$model  = $this->getModel('Comment', 'JCommentsModel');
		$table  = $model->getTable('report');
		$result = -1;

		if ($table->load($id))
		{
			$commentId = (int) $table->commentid;

			if ($model->deleteReport($id))
			{
				$reports = $model->getReports($commentId);
				$result  = count($reports);
			}
		}

		echo $result;

		Factory::getApplication()->close();
	}
}
