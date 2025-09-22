// Movie Details Page JavaScript - Simplified Star Rating

document.addEventListener('DOMContentLoaded', function() {
    console.log('Movie details page loaded');
    initializeStarRating();
    initializeReviewTextarea();
});

function initializeStarRating() {
    const stars = document.querySelectorAll('.star-rating .star');
    const ratingInput = document.getElementById('rating-input');
    
    console.log('Found stars:', stars.length);
    console.log('Found rating input:', ratingInput);
    
    if (!stars.length || !ratingInput) {
        console.log('Star rating elements missing');
        return;
    }
    
    let selectedRating = parseInt(ratingInput.value) || 0;
    
    // Create rating display
    const ratingDisplay = document.createElement('div');
    ratingDisplay.id = 'rating-display';
    ratingDisplay.style.cssText = 'margin-top: 10px; font-weight: bold; color: #333; font-size: 16px;';
    document.querySelector('.star-rating').parentNode.appendChild(ratingDisplay);
    
    // Update display function
    function updateDisplay() {
        if (selectedRating > 0) {
            ratingDisplay.textContent = `You selected: ${selectedRating}/5 stars`;
        } else {
            ratingDisplay.textContent = 'Click on a star to rate this movie';
        }
        
        // Update star appearance
        stars.forEach(function(star, index) {
            const starNumber = parseInt(star.getAttribute('data-rating'));
            const starIcon = star.querySelector('i');
            
            if (starNumber <= selectedRating) {
                star.classList.add('filled');
                starIcon.style.color = '#ffc107';
            } else {
                star.classList.remove('filled');
                starIcon.style.color = '#ddd';
            }
        });
    }
    
    // Add click events to stars
    stars.forEach(function(star, index) {
        const starNumber = parseInt(star.getAttribute('data-rating'));
        
        star.addEventListener('click', function(e) {
            e.preventDefault();
            selectedRating = starNumber;
            ratingInput.value = selectedRating;
            updateDisplay();
            console.log('Star clicked:', starNumber, 'Rating set to:', selectedRating);
        });
        
        // Hover effects
        star.addEventListener('mouseenter', function() {
            stars.forEach(function(s, i) {
                const sNum = parseInt(s.getAttribute('data-rating'));
                const sIcon = s.querySelector('i');
                if (sNum <= starNumber) {
                    sIcon.style.color = '#ffeb3b';
                } else if (sNum <= selectedRating) {
                    sIcon.style.color = '#ffc107';
                } else {
                    sIcon.style.color = '#ddd';
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            updateDisplay();
        });
    });
    
    // Initialize display
    updateDisplay();
}

function initializeReviewTextarea() {
    const textarea = document.getElementById('review_text');
    if (!textarea) return;
    
    // Auto-expand textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 300) + 'px';
    });
}

// Form validation
function validateReviewForm() {
    const rating = document.getElementById('rating-input');
    const reviewText = document.getElementById('review_text');
    
    console.log('Validating form - Rating:', rating ? rating.value : 'null');
    
    if (!rating || !rating.value || rating.value === '0') {
        alert('Please select a rating by clicking on the stars above');
        return false;
    }
    
    if (!reviewText || !reviewText.value.trim()) {
        alert('Please write a review');
        reviewText.focus();
        return false;
    }
    
    console.log('Form validation passed - Rating:', rating.value);
    return true;
}
