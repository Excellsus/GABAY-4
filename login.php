<?php

// Start session for login management


// If already logged in, redirect to home page
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit;
}

// Initialize error message variable
$error_message = "";

// Process login form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    require_once 'connect_db.php';
    
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Prepare a statement to select user from the admin table
        $stmt = $connect->prepare("SELECT id, username, password FROM admin WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Check if username exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if the password is stored as a hash
            if (password_verify($password, $user['password'])) {
                // Password is hashed and correct
                $password_correct = true;
            } else if ($password === $user['password']) {
                // Password is stored as plain text and matches
                $password_correct = true;
            } else {
                // Password is incorrect
                $password_correct = false;
            }
            
            if ($password_correct) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION["logged_in"] = true;
                $_SESSION["id"] = $user['id'];
                $_SESSION["username"] = $user['username'];
                
                // Redirect to home page
                header("Location: home.php");
                exit;
            } else {
                // Password is incorrect
                $error_message = "Invalid username or password. Please try again.";
            }
        } else {
            // Username doesn't exist
            $error_message = "Invalid username or password. Please try again.";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GABAY | Negros Occidental Provincial Capitol</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <div class="background-blur">
      <div class="green-bubble"></div>
      <div class="gold-bubble"></div>
    </div>

    <div class="container">
      <div class="card">
        <!-- Left: Image -->
        <div class="image-section">
          <img src="./srcImage/news-capitol2.jpg" alt="Negros Occidental Provincial Capitol" />
          <div class="image-overlay">
            <div class="image-overlay-content">
              <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h1>GABAY</h1>  
              </div>
              <p style="opacity: 0.9; font-size: 0.875rem;">Navigation System for Negros Occidental Provincial Capitol</p>
            </div>
          </div>
        </div>

        <!-- Right: Form -->
        <div class="form-section">
          <h2>Admin Portal</h2>
          <p>Manage the navigation system and content</p>

          <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-group">
              <label for="username">Username</label>
              <div class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input type="text" id="username" name="username" placeholder="Enter admin username" required />
            </div>

            <div class="input-group">
              <label for="password">Password</label>
              <div class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input type="password" id="password" name="password" placeholder="Enter your password" required />
            </div>

            <?php if (!empty($error_message)): ?>
            <div id="errorMessage" class="error-message" style="display: block;">
              <?php echo $error_message; ?>
            </div>
            <?php else: ?>
            <div id="errorMessage" class="error-message">
              Invalid username or password. Please try again.
            </div>
            <?php endif; ?>

            <button type="submit" class="btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              Sign In
            </button>
          </form>

          <div class="footer" style="margin-top: 2rem;">
            Need help? Contact
            <a href="mailto:support@gabay-capitol.com">support@gabay-capitol.com</a>
          </div>
        </div>
      </div>
      <div class="footer" style="margin-top: 1rem;">
        © 2023 GABAY Navigation System. Provincial Government of Negros Occidental.
      </div>
    </div>
  </body>
</html>