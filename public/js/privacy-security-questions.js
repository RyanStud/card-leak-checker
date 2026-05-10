document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('edit-security-questions');
    const form = document.getElementById('security-questions-form');
    const inputs = Array.from(document.querySelectorAll('.security-q-input'));
    const saveBtn = document.getElementById('save-security-questions');
    const filledCountEl = document.getElementById('filled-count');

    let editing = false;

    function updateCounts() {
        const filled = inputs.filter(i => i.value.trim() !== '').length;
        filledCountEl.textContent = String(filled);
        // allow saving when at least 5 answers are provided
        saveBtn.disabled = (filled < 5);
    }

    function setEditing(on) {
        editing = !!on;
        // show/hide the whole form instead of leaving visible disabled inputs
        form.style.display = editing ? '' : 'none';
        inputs.forEach(i => i.disabled = !editing);
        if (editing) {
            editBtn.textContent = 'Cancelar alteração';
            // focus first input
            const first = inputs.find(i => !i.disabled);
            if (first) first.focus();
        } else {
            editBtn.textContent = 'Alterar perguntas';
            updateCounts();
        }
    }

    editBtn.addEventListener('click', function () {
        setEditing(!editing);
    });

    inputs.forEach(i => {
        i.addEventListener('input', function () {
            // toggle visual indicator based on value
            const container = i.closest('.security-question');
            const indicator = container ? container.querySelector('.saved-indicator') : null;
            if (indicator) {
                if (i.value.trim() !== '') {
                    indicator.textContent = '✔';
                    indicator.classList.remove('saved-empty');
                } else {
                    indicator.textContent = '○';
                    indicator.classList.add('saved-empty');
                }
            }
            updateCounts();
        });
    });

    // Prevent form submit unless exactly 5 answers
    form.addEventListener('submit', function (e) {
        const filled = inputs.filter(i => i.value.trim() !== '').length;
        if (filled < 5) {
            e.preventDefault();
            alert('Por favor responda pelo menos 5 perguntas antes de salvar.');
            return false;
        }
        // allow submit
        return true;
    });

    // initial counts
    updateCounts();
});
