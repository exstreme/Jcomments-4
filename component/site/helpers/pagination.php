<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @subpackage    Helpers
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * JComments Pagination Helper
 *
 * @since  3.0
 */
class JCommentsPagination
{
	protected $commentsCount = 0;
	protected $commentsOrder = null;
	protected $commentsPerPage = null;
	protected $commentsPageLimit = null;
	protected $totalPages = 1;
	protected $limitStart = 0;
	protected $currentPage = 0;

	public function __construct($objectID, $objectGroup)
	{
		$config = ComponentHelper::getParams('com_jcomments');

		$this->commentsPerPage   = (int) $config->get('comments_per_page');
		$this->commentsPageLimit = (int) $config->get('comments_page_limit');
		$this->commentsOrder     = $config->get('comments_list_order');

		if ($this->commentsPerPage > 0)
		{
			$this->setCommentsCount(JComments::getCommentsCount($objectID, $objectGroup));
		}
	}

	public function setCommentsCount($commentsCount)
	{
		$this->commentsCount = $commentsCount;
		$this->_calculateTotalPages();
	}

	public function setCurrentPage($currentPage)
	{
		if ($this->commentsPerPage > 0 && $this->commentsCount > 0)
		{
			if ($currentPage <= 0)
			{
				$this->currentPage = $this->commentsOrder == 'DESC' ? 1 : $this->totalPages;
			}
			elseif ($currentPage > $this->totalPages)
			{
				$this->currentPage = $this->totalPages;
			}
			else
			{
				$this->currentPage = $currentPage;
			}

			$this->limitStart = (($this->currentPage - 1) * $this->commentsPerPage);
		}
		else
		{
			$this->currentPage = 0;
			$this->limitStart  = 0;
		}
	}

	public function getTotalPages()
	{
		return $this->totalPages;
	}

	public function getCommentsPerPage()
	{
		return $this->commentsPerPage;
	}

	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	public function getLimitStart()
	{
		return $this->limitStart;
	}

	public function getCommentPage($objectID, $objectGroup, $commentID)
	{
		$result = 0;

		if ($this->commentsPerPage > 0)
		{
			$compare = $this->commentsOrder == 'DESC' ? '>=' : '<=';
			$prev    = JComments::getCommentsCount($objectID, $objectGroup, "\n id " . $compare . " " . $commentID);
			$result  = max(ceil($prev / $this->commentsPerPage), 1);
		}

		return $result;
	}

	protected function _calculateTotalPages()
	{
		if ($this->commentsPerPage > 0)
		{
			$this->totalPages = ceil($this->commentsCount / $this->commentsPerPage);

			if (($this->commentsPageLimit > 0) && ($this->totalPages > $this->commentsPageLimit))
			{
				$this->totalPages      = $this->commentsPageLimit;
				$this->commentsPerPage = ceil($this->commentsCount / $this->totalPages);

				if (ceil($this->commentsCount / $this->commentsPerPage) < $this->totalPages)
				{
					$this->totalPages = ceil($this->commentsCount / $this->commentsPerPage);
				}
			}
		}
	}
}
