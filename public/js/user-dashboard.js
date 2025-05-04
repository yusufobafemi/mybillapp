// Simple date display
const date = new Date();
const options = { weekday: 'long', day: 'numeric', month: 'long' };
document.getElementById('current-date').textContent = date.toLocaleDateString('en-US', options);

// Add animation classes after page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate dashboard cards with staggered delay
    const cards = document.querySelectorAll('.card');
    setTimeout(() => {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('animate-in');
            }, index * 100);
        });
    }, 300);

    // Animate transaction rows with staggered delay
    const transactionRows = document.querySelectorAll('.transaction-row');
    setTimeout(() => {
        transactionRows.forEach((row, index) => {
            setTimeout(() => {
                row.classList.add('animate-in');
            }, index * 80);
        });
    }, 800);

    // Animate timeline items with staggered delay
    const timelineItems = document.querySelectorAll('.timeline-item');
    setTimeout(() => {
        timelineItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animate-in');
            }, index * 150);
        });
    }, 1200);

    // Animate notification items
    const notificationItems = document.querySelectorAll('.notification-item');
    setTimeout(() => {
        notificationItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animate-in');
            }, index * 200);
        });
    }, 1500);

    // Animate donut chart segments
    const donutSegments = document.querySelectorAll('.donut-segment');
    setTimeout(() => {
        donutSegments.forEach((segment, index) => {
            setTimeout(() => {
                segment.classList.add('animate-in');
            }, index * 150);
        });
    }, 1000);
});

// Add click animations for interactive elements
const actionItems = document.querySelectorAll('.action-item, .btn, .period, .pagination-btn');
actionItems.forEach(item => {
    item.addEventListener('click', function(e) {
        if (!this.disabled) {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 300);
        }
        
        // For period selector
        if (this.classList.contains('period')) {
            document.querySelectorAll('.period').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
        }
        
        // For filter dropdown
        if (this.classList.contains('filter-btn')) {
            e.stopPropagation();
            this.classList.toggle('active');
        }
    });
});

// Close filter dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.filter-btn')) {
        document.querySelector('.filter-btn')?.classList.remove('active');
    }
});

// Simulate table sorting
document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', function() {
        document.querySelectorAll('th.sortable i').forEach(icon => {
            icon.className = 'fas fa-sort';
        });
        
        const icon = this.querySelector('i');
        if (icon.className === 'fas fa-sort') {
            icon.className = 'fas fa-sort-up';
        } else if (icon.className === 'fas fa-sort-up') {
            icon.className = 'fas fa-sort-down';
        } else {
            icon.className = 'fas fa-sort-up';
        }
    });
});

// filter button 
$(document).ready(function() {
    $('.xai-filter-btn').click(function() {
        $('.xai-filter-btn').removeClass('xai-selected');
        $(this).addClass('xai-selected');
        
        // Get the selected filter type
        var filterType = $(this).data('type');
        console.log('Selected filter: ' + filterType);
        // You can add additional logic here to handle the filter
    });
});