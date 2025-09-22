// Add Review Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating');
    const ratingInput = document.getElementById('rating');
    const currentRating = parseInt(ratingInput.value) || 0;
    
    // Set initial star display
    updateStarDisplay(currentRating);
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating;
            updateStarDisplay(rating);
        });
        
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.dataset.rating);
            updateStarDisplay(rating);
        });
    });
    
    // Reset to actual rating on mouse leave
    document.querySelector('.rating-input').addEventListener('mouseleave', function() {
        const actualRating = parseInt(ratingInput.value) || 0;
        updateStarDisplay(actualRating);
    });
    
    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
});
