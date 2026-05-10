/**
 * Form confirmation handlers
 * Adds confirm dialogs to forms instead of using inline event handlers
 */
document.addEventListener('DOMContentLoaded', function () {
    // Revoke access confirmation
    const revokeAccessForm = document.querySelector('form[data-confirm-action][action*="/projects/revoke-access"]');
    if (revokeAccessForm) {
        revokeAccessForm.addEventListener('submit', function (e) {
            if (!confirm('Remover o acesso deste usuário?')) {
                e.preventDefault();
            }
        });
    }

    // Delete history confirmation
    const deleteHistoryForm = document.querySelector('form[data-confirm-action][action*="/privacy/delete-history"]');
    if (deleteHistoryForm) {
        deleteHistoryForm.addEventListener('submit', function (e) {
            if (!confirm('Deseja realmente apagar seu histórico de verificações?')) {
                e.preventDefault();
            }
        });
    }

    // Delete projects confirmation
    const deleteProjectsForm = document.querySelector('form[data-confirm-action][action*="/privacy/delete-projects"]');
    if (deleteProjectsForm) {
        deleteProjectsForm.addEventListener('submit', function (e) {
            if (!confirm('Deseja realmente apagar todos os seus projetos próprios?')) {
                e.preventDefault();
            }
        });
    }

    // Delete account confirmation
    const deleteAccountForm = document.querySelector('form[data-confirm-action][action*="/privacy/delete-account"]');
    if (deleteAccountForm) {
        deleteAccountForm.addEventListener('submit', function (e) {
            if (!confirm('Tem certeza que deseja excluir sua conta definitivamente?')) {
                e.preventDefault();
            }
        });
    }
});
