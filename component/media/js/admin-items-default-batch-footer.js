((document, submitForm) => {

  const buttonDataSelector = 'data-submit-task';
  const formId = 'adminForm';
  /**
   * Submit the task
   * @param task
   */

  const submitTask = task => {
    const form = document.getElementById(formId);

    if (form && (task === 'comment.batch' || task === 'subscription.batch')) {
      submitForm(task, form);
    }
  }; // Register events


  document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('batch-submit-button-id');

    if (button) {
      button.addEventListener('click', e => {
        const task = e.target.getAttribute(buttonDataSelector);
        submitTask(task);
        return false;
      });
    }
  });
})(document, Joomla.submitform);
