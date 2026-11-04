if (typeof window.Dashboard === 'undefined') {
window.Dashboard = class {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupRealTimeUpdates();
        this.setupQuickActions();
    }
    
    setupRealTimeUpdates() {
        // Real-time updates disabled - data loaded from server-side
        console.log('Dashboard using server-side data');
    }
    
    async updateDashboardData() {
        try {
            // Data sudah diload di server-side, tidak perlu AJAX
            console.log('Dashboard data updated from server-side');
        } catch (error) {
            console.error('Failed to update dashboard data:', error);
        }
    }
    
    updateStatCards(data) {
        const statCards = document.querySelectorAll('.dashboard-user-stat-card');
        statCards.forEach((card, index) => {
            const numberEl = card.querySelector('.dashboard-user-stat-number');
            if (numberEl && data.stats && data.stats[index]) {
                this.animateNumber(numberEl, data.stats[index]);
            }
        });
    }
    
    animateNumber(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) || 
                (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 50);
    }
    
    setupQuickActions() {
        // Quick actions removed
    }
};
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.Dashboard && !window.dashboardInstance) {
        window.dashboardInstance = new window.Dashboard();
    }
});

if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (window.Dashboard && !window.dashboardInstance) {
            window.dashboardInstance = new window.Dashboard();
        }
    }, 100);
}

window.initDashboard = function() {
    if (window.Dashboard) {
        if (window.dashboardInstance) {
            window.dashboardInstance.init();
        } else {
            window.dashboardInstance = new window.Dashboard();
        }
    }
};