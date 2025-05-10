// Document ready
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });
    
    // Form validation for leave dates
    const leaveForm = document.querySelector('form[action="leave.php"]');
    if (leaveForm) {
        leaveForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate > endDate) {
                alert('End date must be after start date');
                e.preventDefault();
            }
        });
    }
    
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    }
});