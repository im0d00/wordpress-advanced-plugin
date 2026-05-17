(function() {
  'use strict';

  function initForms() {
    const forms = document.querySelectorAll('.nexus-form');
    if (!forms.length) return;

    forms.forEach(form => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Basic HTML5 validation trigger
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        const btn   = form.querySelector('button[type="submit"]');
        const resEl = form.querySelector('.nexus-form__response');
        const ogTxt = btn.textContent;
        const ldTxt = btn.dataset.loadingText || 'Sending...';

        btn.disabled = true;
        btn.textContent = ldTxt;
        resEl.className = 'nexus-form__response';
        resEl.textContent = '';

        try {
          const formData = new FormData(form);
          formData.append('action', 'nexusbuilder_submit_form');

          const response = await fetch(NexusBuilderFrontend.ajaxUrl, {
            method: 'POST',
            body: formData,
          });

          const result = await response.json();

          if (!result.success) {
             throw new Error(result.data?.message || 'Server error occurred.');
          }

          // Handle success action
          const { action, message, url } = result.data;

          if (action === 'message') {
             resEl.textContent = message;
             resEl.classList.add('nexus-form__response--success');
             form.reset();
          } else if (action === 'redirect' && url) {
             window.location.href = url;
          }

        } catch (error) {
          resEl.textContent = error.message;
          resEl.classList.add('nexus-form__response--error');
        } finally {
          btn.disabled = false;
          btn.textContent = ogTxt;
        }
      });

      // Conditional logic
      setupConditions(form);
    });
  }

  function setupConditions(form) {
    const fieldsWithCondition = form.querySelectorAll('[data-condition]');

    if (!fieldsWithCondition.length) return;

    // Build dependency map
    const evalConditions = () => {
       fieldsWithCondition.forEach(fieldEl => {
          // Condition format: field_id:value (e.g., field-f3:yes)
          const condition = fieldEl.dataset.condition;
          if (!condition || !condition.includes(':')) return;

          const [depId, expectedVal] = condition.split(':');
          const depInput = form.querySelector(`[name="${depId}"]`);

          if (!depInput) return;

          let isMatch = false;

          if (depInput.type === 'checkbox' || depInput.type === 'radio') {
             // For multiple checkboxes with same name or radios
             const checked = form.querySelector(`[name="${depId}"]:checked`);
             isMatch = checked && checked.value === expectedVal;
          } else {
             isMatch = depInput.value === expectedVal;
          }

          if (isMatch) {
             fieldEl.style.display = '';
             // Restore required attributes if they were removed
             fieldEl.querySelectorAll('input, select, textarea').forEach(i => {
                if (i.dataset.wasRequired) {
                   i.required = true;
                   delete i.dataset.wasRequired;
                }
             });
          } else {
             fieldEl.style.display = 'none';
             // Disable required validation for hidden fields
             fieldEl.querySelectorAll('input, select, textarea').forEach(i => {
                if (i.required) {
                   i.dataset.wasRequired = 'true';
                   i.required = false;
                }
             });
          }
       });
    };

    // Listen to changes on all inputs to re-eval conditions
    form.addEventListener('change', evalConditions);
    form.addEventListener('input', evalConditions);

    // Initial eval
    evalConditions();
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initForms);
  } else {
    initForms();
  }
})();
