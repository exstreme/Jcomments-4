(function () {
  'use strict';

  (function (document, submitForm) {
    var buttonDataSelector = 'data-submit-task';
    var formId = 'adminForm';
    /**
     * Submit the task
     * @param task
     */

    var submitTask = function submitTask(task) {
      var form = document.getElementById(formId);

      if (form && (task === 'comment.batch' || task === 'subscription.batch')) {
        submitForm(task, form);
      }
    }; // Register events


    document.addEventListener('DOMContentLoaded', function () {
      var button = document.getElementById('batch-submit-button-id');

      if (button) {
        button.addEventListener('click', function (e) {
          var task = e.target.getAttribute(buttonDataSelector);
          submitTask(task);
          return false;
        });
      }
    });
  })(document, Joomla.submitform);

}());
