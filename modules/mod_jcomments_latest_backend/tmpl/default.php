<?php
/**
 * JComments Latest - Shows latest comments in Joomla's dashboard
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var object $module */
/** @var object $list */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$app      = Factory::getApplication();
$user     = $app->getIdentity();
$userId   = $user->get('id');
$moduleId = str_replace(' ', '', $module->title) . $module->id;
?>
<table class="jcomments-latest-comments-admin table" id="<?php echo str_replace(' ', '', $module->title) . $module->id; ?>">
	<caption class="visually-hidden"><?php echo $module->title; ?></caption>
	<thead>
	<tr>
		<th scope="col" class="w-60"><?php echo Text::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_COMMENT'); ?></th>
		<th scope="col" class="w-20"><?php echo Text::_('MOD_JCOMMENTS_LATEST_BACKEND_HEADING_AUHTOR'); ?></th>
		<th scope="col" class="w-20"><?php echo Text::_('JDATE'); ?></th>
	</tr>
	</thead>
	<tbody>
		<?php if (count($list)): ?>
			<?php foreach ($list as $i => $item):
				$title = '';

				if ($item->deleted == 1)
				{
					$title = 'secondary';
				}
			?>
				<tr>
					<th scope="row">
						<?php if ($item->checked_out): ?>
							<?php echo HTMLHelper::_('jgrid.checkedout', $moduleId . $i, $item->editor, $item->checked_out_time); ?>
						<?php endif; ?>

						<?php if ($item->link):
							$title = Text::_('JACTION_EDIT');

							if ($item->deleted == 1)
							{
								$title = Text::_('JSTATUS') . ': ' . Text::_('JTRASHED')
									. '<br>' . $title . ' ' . htmlspecialchars($item->comment, ENT_QUOTES);
							}
							else
							{
								$title = $item->published
									? Text::_('JSTATUS') . ': ' . Text::_('JPUBLISHED')
										. '<br>' . $title . ' ' . htmlspecialchars($item->comment, ENT_QUOTES)
									: Text::_('JSTATUS') . ': ' . Text::_('JUNPUBLISHED')
										. '<br>' . $title . ' ' . htmlspecialchars($item->comment, ENT_QUOTES);
							}
							?>
							<a href="<?php echo $item->link; ?>" class="hasTooltip"
							   title="<?php echo $title; ?>">
								<?php echo htmlspecialchars($item->comment, ENT_QUOTES); ?>
							</a>
						<?php else: ?>
							<?php echo htmlspecialchars($item->comment, ENT_QUOTES); ?>
						<?php endif; ?>
					</th>
					<td><?php echo $item->author; ?></td>
					<td><?php echo HTMLHelper::_('date', $item->date, Text::_('DATE_FORMAT_LC4')); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="3"><?php echo Text::_('MOD_JCOMMENTS_LATEST_BACKEND_NO_MATCHING_RESULTS'); ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>
