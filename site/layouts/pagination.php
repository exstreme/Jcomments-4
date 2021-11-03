<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

/** @var JoomlaTuneTemplate $displayData */
$activePage  = $displayData->getVar('comments-nav-active', 1);
$firstPage   = $displayData->getVar('comments-nav-first', 0);
$totalPage   = $displayData->getVar('comments-nav-total', 0);
$objectID    = $displayData->getVar('comment-object_id');
$objectGroup = $displayData->getVar('comment-object_group');

// Number of visible pages
$perPage = 10;

$first = $activePage - $perPage / 2;

if ($first <= 0)
{
	$first = 1;
}

$last = $first + $perPage;

if ($last > $totalPage)
{
	$last = $totalPage;
}

if ($last - $first < $perPage && $perPage < $totalPage)
{
	$first = $last - $perPage;
}

if ($displayData->getVar('comments-nav-top') == 1 || $displayData->getVar('comments-nav-bottom') == 1)
{
	if ($firstPage != 0 && $totalPage != 0)
	{
		?>
		<nav aria-label="Page navigation example">
			<ul class="pagination justify-content-center">
				<li class="page-item<?php echo $first == $activePage ? ' disabled' : ''; ?>">
					<a class="page-link" href="javascript:void(0);" aria-label="Previous"
					   onclick="jcomments.showPage(<?php echo (int) $objectID; ?>, '<?php echo $objectGroup; ?>', <?php echo $activePage - 1; ?>);">
						<span aria-hidden="true">&laquo;</span>
					</a>
				</li>

				<?php
				for ($i = $first; $i <= $last; $i++):
					if ($i == $activePage):
						?>
						<li class="page-item disabled"><a class="page-link" href="javascript:void(0);"><?php echo $i; ?></a></li>
						<?php
					else:
						?>
						<li class="page-item">
							<a class="page-link" href="javascript:void(0);"
							   onclick="jcomments.showPage(<?php echo (int) $objectID; ?>, '<?php echo $objectGroup; ?>', <?php echo $i; ?>);">
							   <?php echo $i; ?>
							</a>
						</li>
					<?php endif; ?>
				<?php endfor; ?>

				<li class="page-item<?php echo $last == $activePage ? ' disabled' : ''; ?>">
					<a class="page-link" href="javascript:void(0);" aria-label="Next"
					   onclick="jcomments.showPage(<?php echo (int) $objectID; ?>, '<?php echo $objectGroup; ?>', <?php echo $activePage + 1; ?>);">
						<span aria-hidden="true">&raquo;</span>
					</a>
				</li>
			</ul>
		</nav>
		<?php
	}
}
