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

namespace Joomla\Component\Jcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;

/**
 * User item controller class.
 * This class is just placeholder for tasks user.{method}.
 *
 * @since  1.6
 */
class UserController extends FormController
{
	/**
	 * Method to save a record.
	 *
	 * NOTE! We need this custom method due to Joomla require a non editable item ID in database, but component users table
	 * have this field as editable and parent method throw an error while save.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public function save($key = null, $urlVar = null)
	{
		$this->checkToken();

		$app     = $this->app;
		/** @var \Joomla\Component\Jcomments\Administrator\Model\UserModel $model */
		$model   = $this->getModel();
		$context = "$this->option.edit.$this->context";
		$task    = $this->getTask();
		$id      = $this->input->getInt('id');
		$data    = $this->input->post->get('jform', array(), 'array');
		$form    = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		$objData = (object) $data;
		$app->triggerEvent(
			'onContentNormaliseRequestData',
			array($this->option . '.' . $this->context, $objData, $form)
		);
		$data = (array) $objData;

		$validData = $model->validate($form, $data);

		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=edit',
					false
				)
			);

			return false;
		}

		if (!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Redirect back to the edit screen.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($id),
					false
				)
			);

			return false;
		}

		$this->setMessage(Text::_('JLIB_APPLICATION_SAVE_SUCCESS'));

		switch ($task)
		{
			case 'apply':
				// Set the record data in the session.
				$recordId = $model->getState($model->getName() . '.id');
				$app->setUserState($context . '.data', null);

				// Redirect back to the edit screen.
				$this->setRedirect(
					Route::_(
						'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=edit&id=' . $recordId,
						false
					)
				);
				break;

			default:
				// Clear the record id and data from the session.
				$app->setUserState($context . '.data', null);

				$url = 'index.php?option=' . $this->option . '&view=' . $this->view_list
					. $this->getRedirectToListAppend();

				// Redirect to the list screen.
				$this->setRedirect(Route::_($url, false));
				break;
		}

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $validData);

		return true;
	}

	/**
	 * Method to check if selected user allready exists.
	 *
	 * @return  void
	 * @since   4.1
	 */
	public function exists()
	{
		$this->checkToken('get');

		$id = $this->app->input->getInt('id', 0);
		/** @var \Joomla\Component\Jcomments\Administrator\Model\UserModel $model */
		$model = $this->getModel();
		$exists = !empty($model->getItem($id)) ? 1 : 0;
		$msg = $exists ? Text::_('ERROR') : '';

		header('Content-type: application/json');

		echo new JsonResponse($exists, $msg, (bool) $exists);

		$this->app->close();
	}
}
