<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Optional: Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Prevent browser caching (so Back button or copy-paste URL won’t reopen a protected page)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Use JavaScript to clear only cart and activeUser, then redirect
echo "<script>
  localStorage.removeItem('cart');
  localStorage.removeItem('activeUser');
  window.location.replace('index.php');
  history.pushState(null, '', location.href);
  window.onpopstate = function() {
    history.go(1); // prevents navigating back after logout
  };
</script>";
exit;
?>
