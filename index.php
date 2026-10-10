<?php
session_start();

// Настройки базы данных
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cheaply_assured_raccoon";

// Подключение к базе данных
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Создание таблицы пользователей, если она не существует
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Функция для генерации CSRF-токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция для проверки CSRF-токена
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Обработка регистрации
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token!");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);

    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $register_error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format!";
    } else {
        // Проверка существования пользователя
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $register_error = "Username or email already exists!";
        } else {
            // Регистрация пользователя
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $email);
            if ($stmt->execute()) {
                $register_success = "Registration successful! <a href='index.php'>Login here</a>";
            } else {
                error_log("Database error: " . $stmt->error);
                $register_error = "An error occurred. Please try again later.";
            }
        }
    }
}

// Обработка авторизации
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token!");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            header("Location: index.php");
            exit();
        } else {
            $login_error = "Invalid password!";
        }
    } else {
        $login_error = "No user found!";
    }
}

// Выход из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Получение данных пользователя для профиля
$user_data = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheaply Assured Raccoon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        nav ul li {
            display: inline;
            margin: 0 15px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }
        main {
            padding: 20px;
            text-align: center;
        }
        section {
            margin-bottom: 20px;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        p {
            font-size: 1.2em;
            max-width: 800px;
            margin: 0 auto;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input, button {
            margin: 5px;
            padding: 10px;
            width: 200px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background-color: #007BFF;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1em;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="#profile">Profile</a></li>
                    <li><a href="index.php?logout=1">Logout</a></li>
                <?php else: ?>
                    <li><a href="#register">Register</a></li>
                    <li><a href="#login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Главная секция -->
        <section id="home">
            <h1>Welcome to Cheaply Assured Raccoon</h1>
            <p>Your trusted partner for affordable and reliable solutions.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p>Welcome back, <?php echo htmlspecialchars($user_data['username']); ?>!</p>
            <?php endif; ?>
        </section>

        <!-- Регистрация -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <section id="register">
                <h1>Register</h1>
                <?php if (!empty($register_error)): ?>
                    <p class="error"><?php echo htmlspecialchars($register_error); ?></p>
                <?php elseif (!empty($register_success)): ?>
                    <p class="success"><?php echo $register_success; ?></p>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit" name="register">Register</button>
                </form>
            </section>

            <!-- Авторизация -->
            <section id="login">
                <h1>Login</h1>
                <?php if (!empty($login_error)): ?>
                    <p class="error"><?php echo htmlspecialchars($login_error); ?></p>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="login">Login</button>
                </form>
            </section>
        <?php else: ?>
            <!-- Профиль -->
            <section id="profile">
                <h1>Welcome, <?php echo htmlspecialchars($user_data['username']); ?>!</h1>
                <p>Email: <?php echo htmlspecialchars($user_data['email']); ?></p>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Cheaply Assured Raccoon. All rights reserved.</p>
    </footer>
</body>
</html>