/**
 * Feedback Form Handler
 * Handles AJAX submission, validation, and user feedback
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get all feedback forms on the page
    const feedbackForms = document.querySelectorAll('form[action*="submit_feedback.php"]');
    
    feedbackForms.forEach(form => {
        const textarea = form.querySelector('textarea[name="feedback"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        const messageDiv = document.getElementById('feedbackMessage');
        const charCounter = createCharCounter(textarea);
        
        // Add character counter
        if (textarea && !textarea.nextElementSibling?.classList.contains('char-counter')) {
            textarea.parentNode.insertBefore(charCounter, textarea.nextElementSibling);
        }
        
        // Client-side validation
        textarea?.addEventListener('input', function() {
            updateCharCounter(this, charCounter);
            validateTextarea(this);
        });
        
        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate before submission
            if (!validateForm(form)) {
                return;
            }
            
            // Get form data
            const formData = new FormData(form);
            
            // Disable submit button and show loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            
            // Clear previous messages
            messageDiv.innerHTML = '';
            
            // Submit via AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const text = await response.text();
                if (!response.ok) {
                    throw new Error(text || `HTTP ${response.status}`);
                }
                try {
                    return JSON.parse(text || '{}');
                } catch (e) {
                    throw new Error(text || 'Invalid server response');
                }
            })
            .then(data => {
                if (data.success) {
                    // Success message
                    showMessage(messageDiv, 'success', data.message);
                    form.reset();
                    charCounter.textContent = '0 / 1000 characters';
                } else {
                    // Error message
                    showMessage(messageDiv, 'danger', data.message || 'Unable to submit feedback.');
                }
            })
            .catch(error => {
                showMessage(messageDiv, 'danger', 'An error occurred. Please try again.');
                console.error('Feedback submission error:', error);
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });
});

/**
 * Create character counter element
 */
function createCharCounter(textarea) {
    const counter = document.createElement('div');
    counter.className = 'char-counter';
    counter.style.cssText = 'font-size: 12px; color: #9fb1c7; margin-top: 4px; text-align: right;';
    counter.textContent = `${textarea?.value.length || 0} / 1000 characters`;
    return counter;
}

/**
 * Update character counter
 */
function updateCharCounter(textarea, counter) {
    const length = textarea.value.length;
    const max = 1000;
    counter.textContent = `${length} / ${max} characters`;
    
    // Change color if approaching or exceeding limit
    if (length > max) {
        counter.style.color = '#dc3545'; // Red
    } else if (length > max * 0.9) {
        counter.style.color = '#ffc107'; // Yellow
    } else {
        counter.style.color = '#9fb1c7'; // Default
    }
}

/**
 * Validate textarea input
 */
function validateTextarea(textarea) {
    const length = textarea.value.trim().length;
    const min = 10;
    const max = 1000;
    
    // Remove previous validation classes
    textarea.classList.remove('is-invalid', 'is-valid');
    
    if (length === 0) {
        // No input yet
        return false;
    } else if (length < min) {
        textarea.classList.add('is-invalid');
        return false;
    } else if (length > max) {
        textarea.classList.add('is-invalid');
        return false;
    } else {
        textarea.classList.add('is-valid');
        return true;
    }
}

/**
 * Validate entire form
 */
function validateForm(form) {
    const textarea = form.querySelector('textarea[name="feedback"]');
    const machine = form.querySelector('input[name="machine"]');
    
    if (!textarea || !machine) {
        return false;
    }
    
    const feedback = textarea.value.trim();
    
    // Check if feedback is empty
    if (feedback.length === 0) {
        showMessage(
            document.getElementById('feedbackMessage'),
            'warning',
            'Please enter your feedback before submitting.'
        );
        textarea.focus();
        return false;
    }
    
    // Check minimum length
    if (feedback.length < 10) {
        showMessage(
            document.getElementById('feedbackMessage'),
            'warning',
            'Feedback must be at least 10 characters long.'
        );
        textarea.focus();
        return false;
    }
    
    // Check maximum length
    if (feedback.length > 1000) {
        showMessage(
            document.getElementById('feedbackMessage'),
            'warning',
            'Feedback cannot exceed 1000 characters.'
        );
        textarea.focus();
        return false;
    }
    
    return true;
}

/**
 * Display message to user
 */
function showMessage(container, type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');

    const messageText = document.createTextNode(String(message || ''));
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close';
    closeButton.setAttribute('data-bs-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');

    alertDiv.appendChild(messageText);
    alertDiv.appendChild(closeButton);
    
    container.innerHTML = '';
    container.appendChild(alertDiv);
    
    // Scroll to message
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-dismiss success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }
        }, 5000);
    }
}

/**
 * Display URL parameter messages on page load
 */
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const messageDiv = document.getElementById('feedbackMessage');
    
    if (!messageDiv) return;
    
    // Check for success parameter
    if (urlParams.has('success')) {
        const message = urlParams.get('success');
        showMessage(messageDiv, 'success', message || 'Feedback submitted successfully!');
        
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Check for error parameter
    if (urlParams.has('error')) {
        const message = urlParams.get('error');
        showMessage(messageDiv, 'danger', message || 'An error occurred.');
        
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
