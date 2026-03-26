<<<<<<< HEAD
let currentProjectId = null;

function openRejectModal(projectId) {
    currentProjectId = projectId;
    document.getElementById('rejectModal').classList.add('show');
    document.getElementById('rejectionReasonInput').value = '';
    document.getElementById('rejectionReasonInput').focus();
}

function closeRejectModal() {
    currentProjectId = null;
    document.getElementById('rejectModal').classList.remove('show');
    document.getElementById('rejectionReasonInput').value = '';
}

function submitRejectForm() {
    const reason = document.getElementById('rejectionReasonInput').value.trim();
    
    if (reason === '') {
        alert('Por favor, informe um motivo para a rejeição.');
        return;
    }

    if (reason.length < 10) {
        alert('O motivo deve ter pelo menos 10 caracteres.');
        return;
    }

    document.getElementById('rejectionReason_' + currentProjectId).value = reason;
    document.getElementById('rejectForm_' + currentProjectId).submit();
}

// Initialize event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Button to open reject modal
    const openButtons = document.querySelectorAll('[data-open-reject-modal]');
    openButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = parseInt(this.getAttribute('data-open-reject-modal'), 10);
            openRejectModal(projectId);
        });
    });

    // Modal cancel button
    const cancelBtn = document.getElementById('modalCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeRejectModal);
    }

    // Modal confirm button
    const confirmBtn = document.getElementById('modalConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', submitRejectForm);
    }

    // Close modal when clicking outside
    const modal = document.getElementById('rejectModal');
    if (modal) {
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRejectModal();
            }
        });
    }

    // Allow Enter+Ctrl to submit
    const textarea = document.getElementById('rejectionReasonInput');
    if (textarea) {
        textarea.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                submitRejectForm();
            }
        });
    }
});

=======
let currentProjectId = null;

function openRejectModal(projectId) {
    currentProjectId = projectId;
    document.getElementById('rejectModal').classList.add('show');
    document.getElementById('rejectionReasonInput').value = '';
    document.getElementById('rejectionReasonInput').focus();
}

function closeRejectModal() {
    currentProjectId = null;
    document.getElementById('rejectModal').classList.remove('show');
    document.getElementById('rejectionReasonInput').value = '';
}

function submitRejectForm() {
    const reason = document.getElementById('rejectionReasonInput').value.trim();
    
    if (reason === '') {
        alert('Por favor, informe um motivo para a rejeição.');
        return;
    }

    if (reason.length < 10) {
        alert('O motivo deve ter pelo menos 10 caracteres.');
        return;
    }

    document.getElementById('rejectionReason_' + currentProjectId).value = reason;
    document.getElementById('rejectForm_' + currentProjectId).submit();
}

// Initialize event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Button to open reject modal
    const openButtons = document.querySelectorAll('[data-open-reject-modal]');
    openButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = parseInt(this.getAttribute('data-open-reject-modal'), 10);
            openRejectModal(projectId);
        });
    });

    // Modal cancel button
    const cancelBtn = document.getElementById('modalCancelBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeRejectModal);
    }

    // Modal confirm button
    const confirmBtn = document.getElementById('modalConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', submitRejectForm);
    }

    // Close modal when clicking outside
    const modal = document.getElementById('rejectModal');
    if (modal) {
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRejectModal();
            }
        });
    }

    // Allow Enter+Ctrl to submit
    const textarea = document.getElementById('rejectionReasonInput');
    if (textarea) {
        textarea.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                submitRejectForm();
            }
        });
    }
});

>>>>>>> d6eb04a2ddb82177eed8d2da39d5c72281512a54
