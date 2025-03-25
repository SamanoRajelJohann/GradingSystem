<?php
// Include the database connection file
require_once("db.php");

// Start the session
session_start();

// Error messages
$errors = [];

// Handle Sign-Up
if (isset($_POST['signup'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    } else {
        // Check if the email already exists
        $stmt = $mysqli->prepare("SELECT account_id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            // Insert new user with default role 'User'
            $stmt = $mysqli->prepare("INSERT INTO accounts (username, email, password, role) VALUES (?, ?, ?, 'User')");
            $stmt->bind_param("sss", $name, $email, $password);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Account created successfully. Please sign in.";
            } else {
                $errors[] = "Failed to create account.";
            }
        }

        $stmt->close();
    }
}

/// Handle Sign-In
if (isset($_POST['signin'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        // Prepare the statement to select the user based on email and password
        $stmt = $mysqli->prepare("SELECT account_id, username, password, role FROM accounts WHERE email = ? AND password = ?");
        
        // Bind parameters (email and plaintext password)
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $stmt->store_result();

        // Ensure there is a result
        if ($stmt->num_rows > 0) {
            // Bind the result variables
            $stmt->bind_result($id, $name, $hashed_password, $role);
            $stmt->fetch();

            // Password and email match
            $_SESSION['id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            $_SESSION['logged_in'] = true;

            // Redirect based on role
            $role = strtolower($role);
            if ($role === 'admin' || $role === 'faculty') {
                header("Location: adminhome.php");
            } elseif ($role === 'user') {
                header("Location: userhome.php");
            }
            exit();
        } else {
            // Invalid email or password
            $errors[] = "Invalid email or password.";
        }

        $stmt->close();
    }
}


// Close the connection
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="CSS/index.css">
    <link rel="icon" type="image/png" href="img/USSG.png">
    <title>USSG</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form method="POST">
                <h1>Create Account</h1>
                <br>
                <input type="text" name="name" placeholder="Name" autocomplete="off" required>
                <input type="email" name="email" placeholder="Email" autocomplete="off" required>
                <input type="password" name="password" placeholder="Password" autocomplete="off" required>
                <br>
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>
        <div class="form-container sign-in">
            <form method="POST">
                <h1>Sign In</h1>       
                <br>        
                <input type="email" name="email" placeholder="Email" autocomplete="off" required>
                <input type="password" name="password" placeholder="Password" autocomplete="off" required>
                <br>
                <button type="submit" name="signin">Sign In</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <img src="img/USSG.png" alt="">
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <img src="img/USSG.png" alt="">
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($errors)): ?>
    <script>
        let errorMessage = "<?php echo implode('\n', $errors); ?>";
        alert(errorMessage);
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        alert("<?php echo $_SESSION['success']; ?>");
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>


    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });
    </script>
</body>
</html>
