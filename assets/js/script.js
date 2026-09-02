document.addEventListener("DOMContentLoaded", () => {
    // Dark mode toggle functionality
    const darkModeToggle = document.getElementById("darkModeToggle");
    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector("i");
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            document.body.classList.add("dark-theme");
            icon.classList.remove("fa-moon");
            icon.classList.add("fa-sun");
        }

        darkModeToggle.addEventListener("click", () => {
            document.body.classList.toggle("dark-theme");
            
            if (document.body.classList.contains("dark-theme")) {
                localStorage.setItem("theme", "dark");
                icon.classList.remove("fa-moon");
                icon.classList.add("fa-sun");
            } else {
                localStorage.setItem("theme", "light");
                icon.classList.remove("fa-sun");
                icon.classList.add("fa-moon");
            }
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
});
