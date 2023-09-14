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

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $displayData */

$i = 1;

if (count($displayData['reports']) == 0): ?>
	<tr>
		<td colspan="7"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td>
	</tr>
<?php else:
	foreach ($displayData['reports'] as $report): ?>
		<tr>
			<td class="text-center">
				<?php echo HTMLHelper::_('grid.id', $i, $report->id, false, 'cid', 'cb', $report->id); ?>
			</td>
			<td class="d-none d-md-table-cell"><?php echo $i; ?></td>
			<td><?php echo $report->reason; ?></td>
			<td class="d-none d-md-table-cell"><?php echo $report->name; ?></td>
			<td><?php echo $report->ip; ?></td>
			<td>
				<?php echo HTMLHelper::_('date', $report->date, Text::_('DATE_FORMAT_LC6')); ?>
			</td>
			<td>
				<a href="#" title="<?php echo Text::_('A_REPORTS_REMOVE_REPORT'); ?>" class="cmd-delete-report btn btn-danger btn-sm">
					<i class="icon-remove"></i>
				</a>
			</td>
		</tr>
		<?php
		$i++;
	endforeach;
endif;
