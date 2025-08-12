document.addEventListener('DOMContentLoaded', function() {
    // Back to top button
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.remove('opacity-100', 'visible');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });
        
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Initialize animations
    const animateElements = document.querySelectorAll('[data-aos]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('aos-animate');
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(el => observer.observe(el));
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Close mobile menu when clicking a link
    document.querySelectorAll('#navbarNav .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            const navbarCollapse = bootstrap.Collapse.getInstance(document.getElementById('navbarNav'));
            if (navbarCollapse) {
                navbarCollapse.hide();
            }
        });
    });
    
    // Handle wish form submission
    const wishForm = document.getElementById('wish-form');
    if (wishForm) {
        wishForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new wish to the container
                    const wishHTML = `
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-${getRandomColor()}-100 text-${getRandomColor()}-600 rounded-full w-10 h-10 flex items-center justify-center">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-bold text-gray-800">${data.sender_name}</h4>
                                    <p class="text-gray-600 text-sm mb-1">${data.relationship} • ${data.date}</p>
                                    <p class="text-gray-700">${data.message}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    const wishesContainer = document.getElementById('wishes-container');
                    if (wishesContainer.children.length === 1 && wishesContainer.querySelector('.text-center')) {
                        // Replace "no wishes" message
                        wishesContainer.innerHTML = wishHTML;
                    } else {
                        // Prepend new wish
                        wishesContainer.insertAdjacentHTML('afterbegin', wishHTML);
                    }
                    
                    // Reset form
                    this.reset();
                    
                    // Show success message
                    alert('Thank you for your wish!');
                } else {
                    alert(data.message || 'Failed to submit wish. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
    
    // Helper function to get random color
    function getRandomColor() {
        const colors = ['pink', 'blue', 'yellow', 'green', 'purple'];
        return colors[Math.floor(Math.random() * colors.length)];
    }
});