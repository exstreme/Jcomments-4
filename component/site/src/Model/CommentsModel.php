<?php
/**
 * Namespaced comments model bridge.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\Model;

defined('_JEXEC') or die;

require_once JPATH_ROOT . '/components/com_jcomments/models/comments.php';

/**
 * Comments list model for Joomla 6 MVC factory resolution.
 *
 * @since  5.0.2
 */
final class CommentsModel extends \JcommentsModelComments
{
}
