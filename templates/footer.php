</div>
    </div>
    
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p>&copy; 2024 Job Portal. All rights reserved.</p>
            <p>
                <a href="#" class="text-white me-3">Privacy Policy</a>
                <a href="#" class="text-white me-3">Terms of Service</a>
                <a href="#" class="text-white">Contact Us</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Vulnerable JavaScript -->
    <script>
        // Expose sensitive functions globally
        // function makeRequest(url, data) {
        //     // No CSRF protection
        //     return fetch(url, {
        //         method: 'POST',
        //         headers: {
        //             'Content-Type': 'application/json',
        //         },
        //         body: JSON.stringify(data)
        //     });
        // }
        
        // XSS vulnerability
        // function displayMessage(message) {
        //     document.getElementById('message').innerHTML = message;
        // }
        
        // // Insecure local storage usage
        // function saveToken(token) {
        //     localStorage.setItem('jwt_token', token);
        // }
        
        // function getToken() {
        //     return localStorage.getItem('jwt_token');
        // }
    // </script>
</body>
</html>