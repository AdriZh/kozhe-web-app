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

// Создание таблицы пользователей, если она не существует
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50),
    reg_date TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// Обработка регистрации
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];

    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $register_error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        if ($stmt->execute()) {
            $register_success = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $register_error = "Registration failed. Please try again.";
        }
    }
}

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $login_error = "All fields are required!";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                header("Location: index.php");
                exit();
            } else {
                $login_error = "Invalid password!";
            }
        } else {
            $login_error = "No user found!";
        }
    }
}

// Выход из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Получение данных пользователя для профиля
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = null;
$email = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $email = $row['email'];
    }
}

$order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC') ? 'DESC' : 'ASC';
$column = isset($_GET['column']) ? $_GET['column'] : 'username'; // Сортировка по умолчанию

$valid_columns = ['username', 'created_at']; // Разрешенные поля
if (!in_array($column, $valid_columns)) {
    $column = 'username';
}

$query = "SELECT * FROM users ORDER BY $column $order";
$result = $conn->query($query);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM users WHERE username LIKE ?";
$stmt = $conn->prepare($query);
$search_param = "%$search%";
$stmt->bind_param("s", $search_param);
$stmt->execute();
$result = $stmt->get_result();

// function generateToken($length = 32) {
//     return bin2hex(random_bytes($length));
// }
// $token = generateToken();
// echo "Generated Token: " . $token;
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheaply Assured Raccoon</title>
    <style>
        /* css для хедера */
        header {
            background-color: white; /* Белый фон */
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc; /* Легкая граница снизу */
        }
        .logo {
            font-size: 24px;
            color: black; /* Черный шрифт */
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
            color: black; /* Черный шрифт */
            font-weight: bold;
        }
        nav ul li a:hover {
            color: #555; /* Темно-серый цвет при наведении */
        }

        /* css для карусели */
        .carousel {
            position: relative;
            width: 100%;
            height: 500px; /* Высота карусели */
            overflow: hidden;
        }
        .carousel img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .carousel img.active {
            opacity: 1;
        }
        .carousel-text {
            position: absolute;
            top: 50%;
            left: 10%; /* Текст слева */
            transform: translateY(-50%); /* Центрирование по вертикали */
            color: white; /* Белый текст */
            max-width: 40%; /* Ширина текстового блока */
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .carousel-text.active {
            opacity: 1;
        }
        .carousel-text h1 {
            font-size: 36px;
            margin: 0;
        }
        .carousel-text p {
            font-size: 18px;
            margin: 10px 0 20px;
        }
        .carousel-text button {
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: transparent; /* Прозрачный фон */
            border: 1px solid white; /* Белая граница */
            border-radius: 5px;
            cursor: pointer;
        }

         /* css для b2 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .product-container {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 20px;
            padding: 20px;
            white-space: nowrap;
        }

        .product-card {
            flex: 0 0 auto;
            width: 250px;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }

        .product-card img {
            width: 100%;
            border-radius: 5px;
        }

        .sale-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background: black;
            color: white;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 5px;
        }

        .product-name {
            font-weight: bold;
            margin: 10px 0;
        }

        .product-price {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .old-price {
            text-decoration: line-through;
            color: gray;
            margin-right: 5px;
        }

        .add-to-cart {
            color: black; /* Черный цвет текста */
            text-decoration: underline; /* Нижнее подчеркивание */
            font-weight: bold;
            cursor: pointer;
        }

        .scroll-buttons {
            text-align: center;
            margin-top: 10px;
        }

        /* css для футтера */
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
        <div class="logo">KOZHE</div>
        <nav>
            <ul>
                <li><a href="/fresh-produce">Fresh Produce</a></li>
                <li><a href="/baked-goods">Baked Goods</a></li>
                <li><a href="/dairy-eggs">Dairy & Eggs</a></li>
                <li><a href="/weekly-specials">Weekly Specials</a></li>
                <li><a href="/farm-fresh">Farm Fresh</a></li>
                <li><a href="/community-favorites">Community Favorites</a></li>
                <li><a href="login.php">LogIn</a></li>
            </ul>
        </nav>
    </header>

    <!-- код для карусели -->
    <div class="carousel">
        <!-- Картинки для карусели -->
        <img src="https://i.pinimg.com/736x/06/b2/2f/06b22f05357c31e8f3182dc82d4c3639.jpg" alt="Image 1" class="active">
        <img src="https://images.squarespace-cdn.com/content/v1/54ec7e4fe4b0866fef062b8b/1556222413921-58N0U5QQQ0T2GGIF5FMC/Local-Food-Trends.jpg?format=750w" alt="Image 2">
        <img src="https://hopkinsdiabetesinfo.org/wp-content/uploads/2022/02/Food-Grocery-Vegetables-1140771380.jpg" alt="Image 3">

        <!-- Текст для карусели -->
        <div class="carousel-text active">
            <h1>Save Big on Surplus Groceries</h1>
            <p>Discover incredible deals on high-quality surplus food from local businesses. Reduce waste and save money with our weekly specials. Limited stock available!</p>
            <button>View Deals</button>
        </div>
        <div class="carousel-text">
            <h1>Rescue Delicious Food Today</h1>
            <p>Join KOZHE in our mission to combat food waste. Find amazing discounts on surplus food items and help create a more sustainable community. Shop now!</p>
            <button>Learn More</button>
        </div>
        <div class="carousel-text">
            <h1>Enjoy Fresh Food at Lower Prices</h1>
            <p>Get access to weekly discounts on a wide range of food products. From fresh produce to baked goods, find everything you need at unbeatable prices. Don't miss out!</p>
            <button>Explore</button>
        </div>
    </div>

    <script>
        let currentIndex = 0;
        const images = document.querySelectorAll('.carousel img');
        const texts = document.querySelectorAll('.carousel-text');

        function showNextSlide() {
            // Скрываем текущую картинку и текст
            images[currentIndex].classList.remove('active');
            texts[currentIndex].classList.remove('active');

            // Переходим к следующему слайду
            currentIndex = (currentIndex + 1) % images.length;

            // Показываем следующую картинку и текст
            images[currentIndex].classList.add('active');
            texts[currentIndex].classList.add('active');
        }

        // Автоматическое переключение слайдов каждые 5 секунд
        setInterval(showNextSlide, 5000);
    </script>


<form method="GET">
        <input type="text" name="search" placeholder="Search users" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>


    <!-- код для продуктов -->
    <div class="scroll-buttons">
        <button onclick="scrollLeft()">&#9665;</button>
        <button onclick="scrollRight()">&#9655;</button>
    </div>
    <div class="product-container" id="productScroll">
        <div class="product-card">
            <div class="sale-tag">Sale</div>
            <img src="https://premierproduceone.com/media/catalog/product/cache/28bf51390c6a7eeeeb3ce2b166c68ce8/6/0/60130_white_milk.jpeg.jpg" alt="Organic Whole Milk">
            <div class="product-name">Organic Whole Milk</div>
            <div class="product-price"><span class="old-price">$3.99</span> $2.99</div>
            <a href="#" class="add-to-cart">Add to cart</a>
        </div>
        <div class="product-card">
        <div class="sale-tag">Sale</div>
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ-HVuracEvP5iGSPPnsF69NRv32glS3eYWbQ&s" alt="Blueberry Muffins">
            <div class="product-name">Blueberry Muffins</div>
            <div class="product-price"><span class="old-price">$3.99</span> $2.99</div>
            <a href="#" class="add-to-cart">Add to cart</a>
        </div>
        <div class="product-card">
            <div class="sale-tag">Sale</div>
            <img src="https://andersenbakeryusa.com/cdn/shop/products/englishbread-3_5184x.jpg?v=1613082862" alt="Artisan Sourdough Bread">
            <div class="product-name">Artisan Sourdough Bread</div>
            <div class="product-price"><span class="old-price">$4.99</span> $3.99</div>
            <a href="#" class="add-to-cart">Add to cart</a>
        </div>
        <div class="product-card">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTN9-OuOtsl4iecwrOQ4c00iOqngoUdBz1dzQ&s" alt="Heirloom Tomatoes">
            <div class="product-name">Heirloom Tomatoes</div>
            <div class="product-price">$4.99</div>
            <a href="#" class="add-to-cart">Add to cart</a>
        </div>
    </div>
    <script>
        function scrollLeft() {
            document.getElementById("productScroll").scrollBy({ left: -300, behavior: "smooth" });
        }

        function scrollRight() {
            document.getElementById("productScroll").scrollBy({ left: 300, behavior: "smooth" });
        }
    </script>


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


