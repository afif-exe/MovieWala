// Main JavaScript Functions for MovieWala

// Auto-scroll chatroom to bottom
function scrollChatroomToBottom() {
    const chatroomMessages = document.getElementById('chatroom-messages');
    if (chatroomMessages) {
        chatroomMessages.scrollTop = chatroomMessages.scrollHeight;
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Check password length
    if (password.length >= 8) strength++;
    
    // Check for lowercase letters
    if (/[a-z]/.test(password)) strength++;
    
    // Check for uppercase letters
    if (/[A-Z]/.test(password)) strength++;
    
    // Check for numbers
    if (/\d/.test(password)) strength++;
    
    // Check for special characters
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}

// Display password strength
function displayPasswordStrength(inputId, indicatorId) {
    const input = document.getElementById(inputId);
    const indicator = document.getElementById(indicatorId);
    
    if (!input || !indicator) return;
    
    input.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        
        indicator.className = 'password-strength';
        
        if (this.value.length === 0) {
            indicator.style.display = 'none';
        } else {
            indicator.style.display = 'block';
            
            if (strength <= 2) {
                indicator.classList.add('weak');
            } else if (strength <= 3) {
                indicator.classList.add('medium');
            } else {
                indicator.classList.add('strong');
            }
        }
    });
}

// Auto-refresh chatroom messages
function autoRefreshChatroom(interval = 30000) {
    if (!document.getElementById('chatroom-messages')) return;
    
    setInterval(() => {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newMessages = doc.getElementById('chatroom-messages');
                
                if (newMessages) {
                    document.getElementById('chatroom-messages').innerHTML = newMessages.innerHTML;
                    scrollChatroomToBottom();
                }
            })
            .catch(error => console.error('Error refreshing chatroom:', error));
    }, interval);
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = searchInput?.closest('form');
    
    if (!searchInput || !searchForm) return;
    
    // Add live search functionality
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                searchForm.submit();
            }
        }, 500);
    });
}

// Star rating display
function displayStarRating(rating, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let starsHTML = '';
    
    // Full stars
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<span class="star filled">★</span>';
    }
    
    // Half star
    if (hasHalfStar) {
        starsHTML += '<span class="star half">☆</span>';
    }
    
    // Empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<span class="star empty">☆</span>';
    }
    
    container.innerHTML = starsHTML;
}

// Initialize interactive star rating
function initializeStarRating(containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    
    if (!container || !input) return;
    
    const stars = container.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', () => {
            const rating = index + 1;
            input.value = rating;
            
            // Update visual display
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('filled');
                    s.classList.remove('empty');
                } else {
                    s.classList.remove('filled');
                    s.classList.add('empty');
                }
            });
        });
        
        star.addEventListener('mouseover', () => {
            const rating = index + 1;
            
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('hover');
                } else {
                    s.classList.remove('hover');
                }
            });
        });
    });
    
    container.addEventListener('mouseleave', () => {
        stars.forEach(s => s.classList.remove('hover'));
    });
}

// Show/hide loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="loading-spinner">Loading...</div>';
    }
}

function hideLoading(elementId, originalContent = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = originalContent;
    }
}

// Initialize all JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll chatroom
    scrollChatroomToBottom();
    
    // Initialize search
    initializeSearch();
    
    // Auto-refresh chatroom (every 30 seconds)
    autoRefreshChatroom();
    
    // Initialize password strength checker
    displayPasswordStrength('password', 'password-strength');
    
    // Add smooth transitions to cards
    const cards = document.querySelectorAll('.movie-card, .card');
    cards.forEach(card => {
        card.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
    });
});

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}
