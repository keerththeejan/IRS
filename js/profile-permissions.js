// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the permissions form when the tab is shown
    const permissionsTab = document.getElementById('permissions-tab');
    if (permissionsTab) {
        permissionsTab.addEventListener('shown.bs.tab', function() {
            loadUserPermissions();
        });
    }

    // Handle form submission
    const permissionsForm = document.getElementById('permissionsForm');
    if (permissionsForm) {
        permissionsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveUserPermissions();
        });
    }
});

// Load user permissions from the server
function loadUserPermissions() {
    fetch('api/get_user_permissions.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update the checkboxes based on the server response
                Object.entries(data.permissions).forEach(([permission, enabled]) => {
                    const checkbox = document.getElementById(permission);
                    if (checkbox) {
                        checkbox.checked = enabled;
                    }
                });
            } else {
                showAlert('error', 'Failed to load permissions: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Error loading permissions. Please try again.');
            
            // Set default values if API fails
            const defaultPermissions = {
                'viewProfile': true,
                'editProfile': true,
                'emailNotifications': true,
                'enrollModules': true,
                'viewGrades': true
            };
            
            Object.entries(defaultPermissions).forEach(([permission, enabled]) => {
                const checkbox = document.getElementById(permission);
                if (checkbox) {
                    checkbox.checked = enabled;
                }
            });
        });
}

// Save user permissions to the server
function saveUserPermissions() {
    const permissions = {
        viewProfile: document.getElementById('viewProfile').checked,
        editProfile: document.getElementById('editProfile').checked,
        emailNotifications: document.getElementById('emailNotifications').checked,
        enrollModules: document.getElementById('enrollModules').checked,
        viewGrades: document.getElementById('viewGrades').checked
    };
    
    // Show loading state
    const submitBtn = document.querySelector('#permissionsForm button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    fetch('api/update_user_permissions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'permissions=' + encodeURIComponent(JSON.stringify(permissions))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Permissions updated successfully!');
        } else {
            showAlert('error', 'Failed to update permissions: ' + (data.message || 'Unknown error'));
            // Revert checkboxes on error
            loadUserPermissions();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Error saving permissions. Please try again.');
        // Revert checkboxes on error
        loadUserPermissions();
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

// Show alert message
function showAlert(type, message) {
    // Remove any existing alerts
    const existingAlerts = document.querySelectorAll('#permissions .alert');
    existingAlerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add the new alert
    const permissionsCard = document.querySelector('#permissions .card');
    if (permissionsCard && permissionsCard.parentNode) {
        permissionsCard.parentNode.insertBefore(alertDiv, permissionsCard);
    }
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}
