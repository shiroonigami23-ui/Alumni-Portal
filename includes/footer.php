<?php
// includes/footer.php
?>
    </main>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Toggle user dropdown
        document.querySelector('.relative button').addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.relative > .hidden');
            const button = document.querySelector('.relative button');
            
            if (dropdown && !dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Mobile menu active state
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.md\\:hidden a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('text-blue-600');
                link.querySelector('i').classList.add('text-blue-600');
            }
        });
    </script>
</body>
</html>