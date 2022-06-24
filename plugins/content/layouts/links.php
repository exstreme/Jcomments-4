<?php
/**
 * JComments content plugin - Plugin for attaching comments list and form to content item.
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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * @var  array                     $item                Article data
 * @var  Joomla\Registry\Registry  $params              Plugin parameters
 * @var  string                    $link                Link for 'readmore'
 * @var  integer                   $commentsCount       Total number of comments
 * @var  boolean                   $commentsLinkHidden  Hide links on certain conditions
 */

$input = Factory::getApplication()->input;
?>
<div class="comments-readmore">
	<?php echo LayoutHelper::render(
		'joomla.content.readmore',
		array(
			'item'   => $item,
			'params' => $item->params,
			'link'   => $link
		)
	);

if ($commentsLinkHidden == false):
?>
	<div class="btn-group" role="group" aria-label="Comments link">
		<?php if ($params->get('link_read_comments') && ($params->get('show_frontpage') || $input->getString('view') != 'featured')): ?>
			<?php if ($commentsCount > 0): ?>
				<a href="<?php echo $link; ?>#comments" class="btn btn-secondary">
					<?php echo Text::plural('LINK_READ_COMMENTS', $commentsCount); ?>
				</a>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ($params->get('link_add_comment') && ($params->get('show_frontpage') || $input->getString('view') != 'featured')): ?>
			<?php if ($commentsCount == 0): ?>
				<a href="<?php echo $link; ?>#addcomments" class="btn btn-secondary">
					<span class="icon-plus" aria-hidden="true"></span> <?php echo Text::_('LINK_ADD_COMMENT'); ?>
				</a>
			<?php else: ?>
				<a href="<?php echo $link; ?>#addcomments" class="btn btn-secondary"
				   title="<?php echo Text::_('LINK_ADD_COMMENT'); ?>">
					<span class="icon-plus" aria-hidden="true"></span>
				</a>
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
</div>
