<?php
// includes/footer.php
?>
    </main>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu active state
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.md\\:hidden a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('text-blue-600');
                const icon = link.querySelector('svg, i');
                if (icon) {
                    icon.classList.add('text-blue-600');
                }
            }
        });
    </script>
</body>
</html>
