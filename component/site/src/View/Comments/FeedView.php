<?php
/**
 * Namespaced comments feed view bridge.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\View\Comments;

defined('_JEXEC') or die;

require_once JPATH_ROOT . '/components/com_jcomments/views/comments/view.feed.php';

/**
 * Comments feed view for Joomla 6 MVC factory resolution.
 *
 * @since  5.0.2
 */
final class FeedView extends \JCommentsViewComments
{
}
