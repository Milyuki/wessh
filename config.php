<?php
// Google OAuth Configuration
// Get these from Google Cloud Console: https://console.cloud.google.com/
define('GOOGLE_CLIENT_ID', '684588825502-ppu0djjqg8lra3aqglh451fd74slb22p.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-abcdefghijklmnopqrstuvwxy');
define('GOOGLE_REDIRECT_URI', 'http://localhost/wessh/google_callback.php');

// Facebook OAuth Configuration
// Create a Facebook App at: https://developers.facebook.com/apps/
// Add Facebook Login product and configure redirect URIs
define('FACEBOOK_APP_ID', '1234567890123456'); // Replace with actual App ID (numeric)
define('FACEBOOK_APP_SECRET', 'abcdefghijklmnopqrstuvwxy123456'); // Replace with actual App Secret
define('FACEBOOK_REDIRECT_URI', 'http://localhost/wessh/facebook_callback.php');
?>