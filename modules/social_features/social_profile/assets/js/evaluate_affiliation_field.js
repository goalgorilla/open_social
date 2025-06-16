(function () {
  Drupal.behaviors.evaluateFieldState = {
    attach(context) {
      const fieldASelector = '.field--name-field-group-affiliation table';
      const fieldBSelector = '.field--name-field-other-affiliations table';
      const targetClass = 'has-multiple-items';
      const wrapperA = context.querySelector('.field--name-field-group-affiliation');
      if (!wrapperA) return;

      const evaluate = () => {
        const getValidCount = (fieldSelector, subfieldClass, type = 'select') => {
          const table = context.querySelector(fieldSelector);
          if (!table) return 0;

          const cells = Array.from(table.querySelectorAll('td:not(.tabledrag-hide):not(.field-multiple-drag)'));
          let count = 0;

          cells.forEach((cell) => {
            const field = cell.querySelector(`${subfieldClass} ${type}`);
            if (field && field.value.trim() !== '') {
              count++;
            }
          });

          return count;
        };

        const countA = getValidCount(fieldASelector, '.form-type-select', 'select');
        const countB = getValidCount(fieldBSelector, '.field--name-field-affiliation-org-name', 'input');
        const shouldApplyClass = countA >= 2 || (countA >= 1 && (countA + countB) >= 2);
        wrapperA.classList.toggle(targetClass, shouldApplyClass);
      };

      // Initial evaluation
      evaluate();

      // Attach event listeners to relevant fields in context
      const selectsA = context.querySelectorAll(`${fieldASelector} .subfield1A select`);
      const inputsB = context.querySelectorAll(`${fieldBSelector} .field--name-field-affiliation-org-name input`);

      selectsA.forEach((select) => {
        select.addEventListener('change', evaluate);
      });

      inputsB.forEach((input) => {
        input.addEventListener('blur', evaluate);
      });
    },
  };
})();

