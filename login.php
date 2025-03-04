<?php
require_once 'includes/connect.php';
require_once 'includes/checkuser.php';

if ($userCount == 0) {
    header("Location: registration.php");
    exit();
}

session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $db->close();
    header("Location: /");
    exit();
} 

$theme = "light";
if (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
}

$loginFailed = false;
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember']) ? true : false;

    $query = "SELECT id, password, main_currency FROM user WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        $hashedPasswordFromDb = $row['password'];
        $userId = $row['id'];
        $main_currency = $row['main_currency'];
        if (password_verify($password, $hashedPasswordFromDb)) {
            $_SESSION['username'] = $username;
            $_SESSION['loggedin'] = true;
            $_SESSION['main_currency'] = $main_currency;
            if ($rememberMe) {
                $token = bin2hex(random_bytes(32));
                $addLoginTokens = "INSERT INTO login_tokens (user_id, token) VALUES (?, ?)";
                $addLoginTokensStmt = $db->prepare($addLoginTokens);
                $addLoginTokensStmt->bindValue(1, $userId, SQLITE3_INTEGER);
                $addLoginTokensStmt->bindValue(2, $token, SQLITE3_TEXT);
                $addLoginTokensStmt->execute();
                $_SESSION['token'] = $token;
                $cookieExpire = time() + (30 * 24 * 60 * 60);
                $cookieValue = $username . "|" . $token . "|" . $main_currency;
                setcookie('wallos_login', $cookieValue , $cookieExpire, '/');
            }
            $db->close();
            header("Location: /");
            exit();
        } else {
            $loginFailed = true;
        }
    } else {
        $loginFailed = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wallos - Subscription Tracker</title>
    <link rel="icon" type="image/png" href="images/icon/favicon.ico" sizes="16x16">
    <link rel="apple-touch-icon" sizes="180x180" href="images/icon/apple-touch-icon.png">
    <link rel="manifest" href="images/icon/site.webmanifest">
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Barlow:300,400,500,600,700">
    <link rel="stylesheet" href="styles/login-dark-theme.css" id="dark-theme" <?= $theme == "light" ? "disabled" : "" ?>>
</head>
<body>
    <div class="content">
        <section class="container">
            <header>
                <? 
                    if ($theme == "light") {
                        ?> <img src="images/wallossolid.png" alt="Wallos Logo" title="Wallos - Subscription Tracker" /> <?
                    } else {
                        ?> <img src="images/wallossolidwhite.png" alt="Wallos Logo" title="Wallos - Subscription Tracker" /> <?
                    }
                ?>
                <p>
                    Please login.
                </p>
            </header>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group-inline">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Stay logged in (30 days)</label>
                </div>
                <?php
                    if ($loginFailed) {
                        ?>
                        <sup class="error">
                            Login details are incorrect.
                        </sup>
                        <?php
                    }
                ?>
                <div class="form-group">
                    <input type="submit" value="Login">
                </div>
            </form>
        </section>
    </div>
</body>
</html>