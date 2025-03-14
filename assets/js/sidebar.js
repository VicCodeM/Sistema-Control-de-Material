document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    const mainContent = document.querySelector('.main-content');

    // Check for saved sidebar state on desktop
    if (window.innerWidth >= 768) {
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
            if (mainContent) {
                mainContent.style.marginLeft = '0';
            }
        }
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (window.innerWidth >= 768) {
                // Desktop behavior - toggle collapse
                sidebar.classList.toggle('collapsed');
                body.classList.toggle('sidebar-collapsed');
                
                // Adjust main content margin
                if (mainContent) {
                    if (sidebar.classList.contains('collapsed')) {
                        mainContent.style.marginLeft = '0';
                    } else {
                        mainContent.style.marginLeft = '250px';
                    }
                }
                
                // Save state
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            } else {
                // Mobile behavior - show/hide sidebar
                sidebar.classList.toggle('show');
                body.classList.toggle('sidebar-open');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('show');
                body.classList.remove('sidebar-open');
                
                // Restore desktop state
                const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (sidebarCollapsed) {
                    sidebar.classList.add('collapsed');
                    body.classList.add('sidebar-collapsed');
                    if (mainContent) {
                        mainContent.style.marginLeft = '0';
                    }
                } else {
                    sidebar.classList.remove('collapsed');
                    body.classList.remove('sidebar-collapsed');
                    if (mainContent) {
                        mainContent.style.marginLeft = '250px';
                    }
                }
            } else {
                // Reset mobile state
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                }
            }
        });
    }
});