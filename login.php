<?php
session_start();

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cheaply_assured_raccoon";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $login_error = "All fields are required!";
    } else {
        $sql = "SELECT id, username, password FROM users WHERE username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username']; // Сохраняем имя пользователя
                header("Location: home.php");
                exit();
            } else {
                $login_error = "Invalid password!";
            }
        } else {
            $login_error = "No user found!";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверяем, совпадают ли пароли
    if ($password !== $confirm_password) {
        $register_error = "Passwords do not match!";
    } elseif (strlen($password) < 8) { // Проверка на сложность пароля
        $register_error = "Password must be at least 8 characters long!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Хеширование пароля

        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id; // Сохраняем ID пользователя в сессии
            header("Location: index.php"); // Перенаправляем на главную страницу
            exit();
        } else {
            $register_error = "Error: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogIn - Cheaply Assured Raccoon</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        header {
            background-color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
        }

        .logo {
            font-size: 24px;
            color: black;
            font-weight: bold;
        }

        nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
        }

        nav ul li a {
            text-decoration: none;
            color: black;
            font-weight: bold;
        }

        nav ul li a:hover {
            color: #555;
        }

        main {
            padding: 20px;
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 10px;
            background-color: transparent;
            border: 1px solid #333;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #333;
            color: white;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        footer {
            background-color: #000; /* Черный фон */
            color: white; /* Белый шрифт */
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        .footer-content {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .footer-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .footer-section h3 {
            margin-bottom: 10px;
            font-size: 16px;
            color: white; /* Белый цвет для заголовков */
        }
        .footer-section p {
            margin: 5px 0;
            font-size: 14px;
            color: white; /* Белый цвет для текста */
        }
        .newsletter {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .newsletter input {
            padding: 10px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .newsletter button {
            padding: 10px 20px;
            background-color: #333; /* Темно-серый цвет для кнопки */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .footer-bottom {
            margin-top: 20px;
            font-size: 12px;
            color: white; /* Белый цвет для текста в нижней части */
        }
        .footer-bottom p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo"><a href="home.php">KOZHE</a></div>
        <nav>
            <ul>
                <li><a href="/fresh-produce">Fresh Produce</a></li>
                <li><a href="/baked-goods">Baked Goods</a></li>
                <li><a href="/dairy-eggs">Dairy & Eggs</a></li>
                <li><a href="/weekly-specials">Weekly Specials</a></li>
                <li><a href="/farm-fresh">Farm Fresh</a></li>
                <li><a href="/community-favorites">Community Favorites</a></li>
                <li><a href="/login">LogIn</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Register</h2>
        <?php if (isset($register_error)) echo "<p style='color:red;'>$register_error</p>"; ?>
        <?php if (isset($register_success)) echo "<p style='color:green;'>$register_success</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <button type="submit" name="register">Register</button>
        </form>

        <h2>Log In</h2>
        <?php if (isset($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit" name="login">Log In</button>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("loginForm").addEventListener("submit", function (event) {
                let username = document.getElementById("username").value.trim();
                let password = document.getElementById("password").value.trim();

                if (username === "" || password === "") {
                    event.preventDefault(); // Останавливаем отправку формы
                    alert("All fields are required!");
                }
            });
        });
    </script>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Explore</h3>
                <p>About Koozhe</p>
                <p>Our Mission</p>
                <p>Sustainability</p>
                <p>Partner With Us</p>
            </div>
            <div class="footer-section">
                <h3>Weekly Specials</h3>
                <p>Fresh Produce</p>
                <p>Form Fresh</p>
                <p>Community Favorites</p>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <p>FAQ</p>
                <p>Contact Us</p>
                <p>Terms and Conditions</p>
                <p>Privacy Policy</p>
            </div>
            <div class="newsletter">
                <h3>Subscribe to Our Newsletter</h3>
                <p>Stay up-to-date with the latest deals and sustainable food options!</p>
                <input type="email" placeholder="Enter your email">
                <button>Subscribe</button>
            </div>
        </div>
        <div class="footer-bottom">
            <p>2025 KOZHE. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>