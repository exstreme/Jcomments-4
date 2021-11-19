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

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');

/**
 * Comments form template
 */
class jtt_tpl_form extends JoomlaTuneTemplate
{
	public function render()
	{
		if ($this->getVar('comments-form-message', 0) == 1)
		{
			$this->getMessage($this->getVar('comments-form-message-text'));

			return;
		}

		echo LayoutHelper::render('comment-form', $this, JPATH_ROOT . '/components/com_jcomments/layouts/');
	}

	/**
	 *
	 * Displays service message
	 *
	 * @param   string  $text  Message text
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function getMessage($text)
	{
		$htmlBeforeForm = $this->getVar('comments-html-before-form');
		$htmlAfterForm  = $this->getVar('comments-html-after-form');
		?>
		<a id="addcomments" href="#addcomments"></a>
		<?php
		echo $htmlBeforeForm;

		if ($text != '')
		{
			?>
			<p class="message"><?php echo $text; ?></p>
			<?php
		}

		echo $htmlAfterForm;
	}

	public function getFormFields($fields)
	{
		if (!empty($fields))
		{
			$fields = is_array($fields) ? $fields : array($fields);

			foreach ($fields as $field)
			{
				$labelElement = '';

				if (is_array($field))
				{
					$labelElement = $field['label'] ?? '';
					$inputElement = $field['input'] ?? '';
				}
				else
				{
					$inputElement = $field;
				}

				if (!empty($inputElement))
				{
					?>
					<div>
						<?php echo $inputElement; ?>
						<?php echo $labelElement; ?>
					</div>
					<?php
				}
			}
		}
	}
}
