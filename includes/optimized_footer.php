<?php
/**
 * Optimized Footer
 * 
 * This file includes all necessary JavaScript and closing tags
 * with optimized loading and execution
 */
?>

        </div> <!-- End of #content -->
    </div> <!-- End of .wrapper -->

    <!-- Footer -->
    <footer class="bg-light py-4 mt-4">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> SLGTI. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Load JavaScript with defer -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" 
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
            crossorigin="anonymous" 
            defer></script>
            
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous" 
            defer></script>
    
    <!-- Load custom scripts -->
    <script src="/assets/js/main.js" defer></script>
    
    <!-- Inline critical JavaScript -->
    <script>
        // Immediately load critical JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Service Worker Registration (if PWA is implemented)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful');
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
    
    <!-- Load non-critical JavaScript -->
    <script src="/assets/js/analytics.js" async></script>
</body>
</html>
