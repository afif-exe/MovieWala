// Subscription Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeSubscriptionPage();
});

function initializeSubscriptionPage() {
    // Initialize purchase confirmation
    initializePurchaseConfirmation();
    
    // Initialize pricing card animations
    initializePricingCards();
    
    // Initialize subscription history interactions
    initializeHistoryTable();
}

function initializePurchaseConfirmation() {
    const purchaseButtons = document.querySelectorAll('.purchase-btn:not([disabled])');
    
    purchaseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const isFirstTime = this.textContent.includes('$9.00');
            const amount = isFirstTime ? '$9.00' : '$0.99';
            const type = isFirstTime ? 'first-time premium subscription' : 'monthly premium renewal';
            
            const confirmMessage = `Are you sure you want to purchase the ${type} for ${amount}?`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '⏳ Processing...';
            this.style.opacity = '0.7';
        });
    });
}

function initializePricingCards() {
    const pricingCards = document.querySelectorAll('.pricing-card');
    
    pricingCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '';
        });
        
        // Add click effects for better mobile experience
        card.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        card.addEventListener('touchend', function() {
            this.style.transform = '';
        });
    });
}

function initializeHistoryTable() {
    const historyTable = document.querySelector('.history-table');
    if (!historyTable) return;
    
    // Add row hover effects
    const rows = historyTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });
}

// Auto-renewal cancellation confirmation
function confirmCancellation() {
    return confirm(
        'Are you sure you want to cancel auto-renewal?\n\n' +
        'Your premium access will continue until the current period ends, ' +
        'but will not automatically renew after that.'
    );
}

// Premium features highlight animation
function highlightPremiumFeatures() {
    const featuresList = document.querySelector('.features-list');
    if (!featuresList) return;
    
    const listItems = featuresList.querySelectorAll('li');
    
    listItems.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '0.7';
            item.style.transform = 'translateX(10px)';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
                item.style.transition = 'all 0.3s ease';
            }, 100);
        }, index * 100);
    });
}

// Call highlight animation after page load
setTimeout(highlightPremiumFeatures, 1000);

// Format currency display
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

// Calculate savings
function calculateSavings(originalPrice, currentPrice) {
    const savings = originalPrice - currentPrice;
    const percentage = Math.round((savings / originalPrice) * 100);
    return { amount: savings, percentage: percentage };
}

// Show/hide additional features based on screen size
function handleResponsiveFeatures() {
    const featuresList = document.querySelector('.features-list');
    if (!featuresList) return;
    
    const items = featuresList.querySelectorAll('li');
    const isSmallScreen = window.innerWidth <= 768;
    
    items.forEach((item, index) => {
        if (isSmallScreen && index >= 6) {
            item.style.display = 'none';
        } else {
            item.style.display = 'flex';
        }
    });
    
    // Add "show more" button on small screens
    if (isSmallScreen && !featuresList.querySelector('.show-more-btn')) {
        const showMoreBtn = document.createElement('button');
        showMoreBtn.className = 'show-more-btn';
        showMoreBtn.textContent = 'Show all features';
        showMoreBtn.style.cssText = `
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 15px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 14px;
        `;
        
        showMoreBtn.addEventListener('click', function() {
            items.forEach(item => item.style.display = 'flex');
            this.remove();
        });
        
        featuresList.appendChild(showMoreBtn);
    }
}

// Handle responsive features on load and resize
window.addEventListener('load', handleResponsiveFeatures);
window.addEventListener('resize', handleResponsiveFeatures);

// Smooth scroll to subscription section
function scrollToSubscription() {
    const subscriptionSection = document.querySelector('.subscription-section');
    if (subscriptionSection) {
        subscriptionSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start' 
        });
    }
}

// Premium badge animation
function animatePremiumBadge() {
    const badges = document.querySelectorAll('.premium-badge');
    
    badges.forEach(badge => {
        setInterval(() => {
            badge.style.transform = 'scale(1.1)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
        }, 3000);
    });
}

// Initialize premium badge animation
setTimeout(animatePremiumBadge, 2000);
