// Event delegation para todos os cliques
document.addEventListener('click', function(e) {
    const btn = e.target.closest('button');
    
    if (!btn) return;

    // Botão criar projeto
    if (btn.classList.contains('btn-create-project')) {
        e.preventDefault();
        toggleCreateForm();
    }

    // Botão fechar formulário
    if (btn.classList.contains('btn-close-form')) {
        e.preventDefault();
        toggleCreateForm();
    }

    // Botão scroll projetos
    if (btn.classList.contains('btn-scroll-projects')) {
        e.preventDefault();
        const projectsList = document.querySelector('.projects-list');
        if (projectsList) {
            projectsList.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Botão compartilhar
    if (btn.classList.contains('btn-share')) {
        e.preventDefault();
        const projectId = btn.dataset.project;
        const shareRow = document.querySelector(`[data-share-row="${projectId}"]`);
        if (shareRow) {
            const isVisible = shareRow.style.display !== 'none';
            shareRow.style.display = isVisible ? 'none' : 'table-row';
            btn.textContent = isVisible ? 'Compartilhar' : 'Fechar';
        }
    }

    // Botão cancelar compartilhamento
    if (btn.classList.contains('btn-cancel-share')) {
        e.preventDefault();
        const shareRow = btn.closest('[data-share-row]');
        if (shareRow) {
            shareRow.style.display = 'none';
            const projectId = shareRow.dataset.shareRow;
            const shareBtn = document.querySelector(`.btn-share[data-project="${projectId}"]`);
            if (shareBtn) shareBtn.textContent = 'Compartilhar';
        }
    }

    // Botão acessos
    if (btn.classList.contains('btn-access')) {
        e.preventDefault();
        const projectId = btn.dataset.project;
        const accessRow = document.querySelector(`[data-access-row="${projectId}"]`);
        if (accessRow) {
            const isVisible = accessRow.style.display !== 'none';
            accessRow.style.display = isVisible ? 'none' : 'table-row';
            btn.textContent = isVisible ? 'Acessos' : 'Fechar';
        }
    }
});

function toggleCreateForm() {
    const form = document.querySelector('.create-project-form');
    if (form) {
        form.classList.toggle('active');
        if (form.classList.contains('active')) {
            setTimeout(() => {
                form.scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }
    }
}
