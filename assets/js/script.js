// Apply saved theme immediately
if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-theme");
}

document.addEventListener("DOMContentLoaded", () => {
    // Dark mode toggle functionality (Landing page)
    const darkModeToggle = document.getElementById("darkModeToggle");
    const dashToggle = document.getElementById("darkModeToggleDash"); // Dashboard inline toggle
    const isDark = document.body.classList.contains("dark-theme");

    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector("i");
        if (isDark && icon) {
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        }

        darkModeToggle.addEventListener("click", () => {
            document.body.classList.toggle("dark-theme");
            const currentlyDark = document.body.classList.contains("dark-theme");
            localStorage.setItem("theme", currentlyDark ? "dark" : "light");
            if(icon) {
                icon.classList.remove("fa-moon", "fa-sun");
                icon.classList.add(currentlyDark ? "fa-sun" : "fa-moon");
            }
        });
    }

    if (dashToggle) {
        const icon = dashToggle.querySelector("i");
        if (isDark && icon) {
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        }
        
        dashToggle.addEventListener("click", () => {
            setTimeout(() => {
                const currentlyDark = document.body.classList.contains("dark-theme");
                localStorage.setItem("theme", currentlyDark ? "dark" : "light");
            }, 10);
        });
    }

    // Password Eye Button Toggle
    const togglePasswordIcons = document.querySelectorAll('.fa-eye');
    togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', function (e) {
            // Find the closest wrapper
            const wrapper = this.closest('.input-wrapper');
            if (wrapper) {
                const input = wrapper.querySelector('input');
                if (input && input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else if (input && input.type === 'text') {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });

    // Auth Tabs functionality (Sign Up Page)
    const authTabs = document.querySelectorAll(".auth-tab");
    const roleIdInput = document.getElementById("role_id");

    if (authTabs.length > 0) {
        authTabs.forEach(tab => {
            tab.addEventListener("click", (e) => {
                e.preventDefault();
                // Remove active class from all tabs
                authTabs.forEach(t => t.classList.remove("active"));
                // Add active class to clicked tab
                tab.classList.add("active");
                
                // Update hidden input value (1 = donator, 2 = user/recipient)
                if (roleIdInput) {
                    if (tab.textContent.includes("Donate")) {
                        roleIdInput.value = "1";
                    } else {
                        roleIdInput.value = "2";
                    }
                }
            });
        });
    }

    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.dashboard-sidebar');
    let overlay = document.querySelector('.sidebar-overlay');
    
    // Create overlay if it doesn't exist but we are in dashboard
    if (mobileMenuBtn && sidebar && !overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
        });

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('sidebar-open');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
    }
});
