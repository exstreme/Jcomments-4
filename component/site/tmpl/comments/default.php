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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\Component\Jcomments\Site\View\Comments\HtmlView $this */

$app            = Factory::getApplication();
$locked         = $this->params->get('comments_locked');
$feedLimit      = $this->params->get('feed_limit', $app->get('feed_limit'));
$feedUrl        = 'index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $this->objectID
	. '&object_group=' . $this->objectGroup . '&type=rss&format=feed';
$view           = $app->input->getWord('view');
$formLayoutData = array(
	// It is necessary to get the form object in the 'params' layout.
	'viewObject'    => &$this,
	'params'        => $this->params,
	'displayForm'   => $this->displayForm,
	'canViewForm'   => $this->canViewForm,
	'canComment'    => $this->canComment,
	'returnPage'    => $this->returnPage,
	'form'          => $this->form,
	'item'          => $this->item
);
?>
<div class="container-fluid px-0 mt-2 comments hasLoader" id="comments">
	<?php if ($this->params->get('form_position') && $view != 'comments'):
		echo LayoutHelper::render('form', $formLayoutData, '', array('component' => 'com_jcomments'));
	endif; ?>

	<div class="row comments-list-header">
		<div class="col col-auto h5 me-2"><?php echo Text::_('COMMENTS_LIST'); ?>
			<span class="text-info ps-2 total-comments"></span>
		</div>
		<div class="col small">
			<?php if (!$locked): ?>
				<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>" class="me-1 ms-1 mb-1 refresh-list">
					<span aria-hidden="true" class="fa icon-loop"></span>
				</a>
			<?php endif; ?>

			<?php if ($this->params->get('enable_rss') && $feedLimit > 0 && !$locked): ?>
				<a href="<?php echo Route::_($feedUrl); ?>" title="<?php echo Text::_('BUTTON_RSS'); ?>"
				   target="_blank" class="ms-1 mb-1 rss-link">
					<span aria-hidden="true" class="fa icon-rss"></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="row mb-2 comments-list-container">
		<div class="d-flex align-items-center">
			<div class="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true"></div>
			<span class="ms-2"><?php echo Text::_('COMMENTS_LOADING'); ?></span>
		</div>
	</div>

	<div class="row mb-1 comments-list-footer">
		<?php if (!$locked): ?>
			<div class="comments-refresh">
				<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>" class="refresh-list">
					<span aria-hidden="true" class="fa icon-loop me-1"></span><?php echo Text::_('BUTTON_REFRESH'); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ($this->params->get('enable_rss') && $feedLimit > 0 && !$locked): ?>
			<div class="comments-rss">
				<a href="<?php echo Route::_($feedUrl); ?>" title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
					<span aria-hidden="true" class="fa icon-rss me-1"></span><?php echo Text::_('BUTTON_RSS'); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ($this->canSubscribe && !$locked):
			$text = $this->isSubscribed ? Text::_('LINK_UNSUBSCRIBE') : Text::_('LINK_SUBSCRIBE');
			$func = $this->isSubscribed ? 'remove' : 'add';
			$url  = 'index.php?option=com_jcomments&task=subscription.' . $func . '&object_id=' . $this->objectID
				. '&object_group=' . $this->objectGroup . '&return=' . base64_encode(Uri::getInstance());
			?>
			<div class="comments-subscription">
				<a href="<?php echo Route::_($url); ?>" class="cmd-subscribe" title="<?php echo $text; ?>" rel="nofollow">
					<span aria-hidden="true" class="fa icon-mail me-1"></span><?php echo $text; ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

	<?php if (!$this->params->get('form_position') && $view != 'comments'):
		echo LayoutHelper::render('form', $formLayoutData, '', array('component' => 'com_jcomments'));
	endif; ?>
</div>
<?php
echo LayoutHelper::render('comment-report', null, '', array('component' => 'com_jcomments'));
