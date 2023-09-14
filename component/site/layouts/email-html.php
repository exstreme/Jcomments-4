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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/**
 * @var array $displayData
 * @see NotificationHelper::getMessageBody()
 */
$comment = $displayData['data'];
$lang    = Factory::getApplication()->getLanguage();
$logo    = @file_get_contents(JPATH_ROOT . '/media/com_jcomments/images/icon-48-jcomments.jpg');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="<?php echo strtolower($lang->getTag()); ?>" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;" />
	<style type="text/css">
		<?php echo @file_get_contents(JPATH_ROOT . '/media/vendor/bootstrap/css/bootstrap-reboot.min.css'); ?>
		<?php echo @file_get_contents(JPATH_ROOT . '/media/com_jcomments/editor/themes/content/component.css'); ?>
		.container {
			display: block !important;
			max-width: 800px !important;
			margin: 0 auto !important; /* makes it centered */
			clear: both !important;
		}
		.content {
			padding: 10px 15px;
			max-width: 800px;
			margin: 0 auto;
			display: block;
		}

		table.head-wrap, table.body-wrap, table.footer-wrap { width: 100%; }
		.content table { width: 100%; }

		.footer-wrap .content { text-align: center; border-top: 1px solid rgb(215,215,215); }
		.footer-wrap .u-text { color: #bbbbbb; font-size: .9em; }
		.footer-wrap a { font-size: .9em; }
		.text-muted { --text-opacity: 1; color: #6d757e !important; }
		.report {
			color: var(--bs-warning);
			background-color: var(--bs-gray-800) !important;
			border: 1px solid var(--bs-gray-800) !important;
			border-radius: 0.25rem !important;
			font-size: .9em;
			font-weight: 600;
			padding: 10px;
		}
		.report .reason { font-weight: bold; }
	</style>
</head>
<body>
	<table class="head-wrap">
		<tr>
			<td class="container">
				<div class="content">
					<table>
						<tr>
							<td><img src="<?php echo 'data:image/png;base64,' . base64_encode($logo); ?>" alt="Logo" /></td>
							<td>
								<h6 style="text-align: right;">
									<?php echo Text::_('EMAIL_HEADER'); ?>&nbsp;
									<a href="<?php echo $comment->object_link; ?>" target="_blank"
									   title="<?php echo Text::_('NOTIFICATION_COMMENT_LINK'); ?>"
									   style="font-weight: 600;">
										<?php echo $comment->object_title; ?>
									</a>
								</h6>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	<table class="body-wrap">
		<tr>
			<td></td>
			<td class="container">
				<div class="content">
					<table>
						<?php if ($displayData['report']): ?>
						<tr>
							<td>
								<p class="report">
									<?php echo Text::sprintf('REPORT_NOTIFICATION_MESSAGE', $comment->author, $comment->report_name, $comment->ip); ?>
									<?php if ($comment->report_reason != ''): ?>
										<br />
										<span>
											<?php echo Text::_('REPORT_NOTIFICATION_MESSAGE_REASON'); ?> -
											<span class="reason"><?php echo $comment->report_reason; ?></span>
										</span>
									<?php endif; ?>
								</p>
							</td>
						</tr>
						<?php endif; ?>
						<?php if ($comment->notification_type == 'comment-delete' || $comment->notification_type == 'moderate-delete'): ?>
							<tr>
								<td>
									<p class="report">
										<?php echo Text::sprintf('NOTIFICATION_SUBJECT_DELETED', $comment->object_title); ?>
									</p>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>
								<?php if ($comment->title > ''): ?>
									<h4><?php echo htmlspecialchars($comment->title, ENT_QUOTES); ?></h4>
								<?php endif; ?>
								<p class="text-muted">
									<?php echo $comment->author; ?>,

									<?php if ($displayData['isAdmin'] && !empty($comment->email)): ?>
										<a href="mailto: <?php echo $comment->email; ?>" target="_blank"><?php echo $comment->email; ?></a>,
										IP: <?php echo $comment->ip; ?>,
									<?php endif; ?>

									<?php echo HTMLHelper::_('date', $comment->date, 'DATE_FORMAT_LC5'); ?>,
									<a href="<?php echo $comment->object_link ?>#comment-<?php echo $comment->id; ?>"
									   target="_blank" title="<?php echo Text::_('BUTTON_PERMALINK'); ?>">#</a>
								</p>
								<p><?php echo $comment->comment; ?></p>
							</td>
						</tr>
					</table>
				</div>
			</td>
			<td></td>
		</tr>
	</table>
	<table class="footer-wrap">
		<tr>
			<td class="container">
				<p class="content u-text">
					<?php echo Text::_('NOTIFICATION_COMMENT_UNSUBSCRIBE_MESSAGE'); ?>
				</p>
			</td>
		</tr>
		<?php if ($displayData['isAdmin']): ?>
			<tr>
				<td class="container">
					<p class="content">
						<?php if ($displayData['config']->get('enable_quick_moderation')):
							$aTag   = array();
							$return = '&return=' . base64_encode($comment->object_link);

							// Publish/unpublish link
							$action = ($comment->published == 0) ? 'publish' : 'unpublish';
							$hash   = JcommentsFactory::getCmdHash($action, $comment->id);
							$link   = 'index.php?option=com_jcomments&task=comment.' . $action . '&id=' . $comment->id . '&hash=' . $hash;
							$aTag[] = '<a href="' . Route::link('site', $link, false, 0, true) . $return
								. '" title="' . Text::_(strtoupper($action)) . '" target="_blank">' . Text::_(strtoupper($action))
								. '</a>';

							// Delete link
							$hash   = JcommentsFactory::getCmdHash('delete', $comment->id);
							$link   = 'index.php?option=com_jcomments&task=comment.delete&id=' . $comment->id . '&hash=' . $hash;
							$title  = Text::_('JACTION_DELETE');
							$aTag[] = '<a href="' . Route::link('site', $link, false, 0, true) . $return
								. '" title="' . $title . '" target="_blank">' . $title . '</a>';

							if ($displayData['config']->get('enable_blacklist'))
							{
								$hash   = JcommentsFactory::getCmdHash('banIP', $comment->id);
								$link   = 'index.php?option=com_jcomments&task=comment.banIP&id=' . $comment->id . '&hash=' . $hash;
								$title  = Text::_('BUTTON_BANIP');
								$aTag[] = '<a href="' . Route::link('site', $link, false, 0, true) . $return
									. '" title="' . $title . '" target="_blank">' . $title . '</a>';
							}

							if (count($aTag))
							{
								echo Text::_('QUICK_MODERATION') . ': ' . implode(' | ', $aTag);
							}
						endif; ?>
					</p>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<td class="container">
				<p class="content">
					<a href="<?php echo Route::link('site', 'index.php?option=com_jcomments&task=terms', false, 0, true); ?>"
					   target="_blank"><?php echo Text::_('FORM_TOS'); ?></a> |
					<a href="<?php echo Route::link('site', 'index.php?option=com_jcomments&task=privacy', false, 0, true); ?>"
					   target="_blank"><?php echo Text::_('FORM_PRIVACY'); ?></a> |
					<a href="<?php echo Route::link('site', 'index.php?option=com_jcomments&task=subscription.remove&hash=' . $displayData['hash'], false, 0, true); ?>"
					   target="_blank"><?php echo Text::_('NOTIFICATION_COMMENT_UNSUBSCRIBE_LINK'); ?></a>
				</p>
			</td>
		</tr>
	</table>
</body>
</html>
