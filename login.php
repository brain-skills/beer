<?php
session_start();

$login = 'giorgi';
$password = 'beermore';

if (isset($_POST['login']) && isset($_POST['password'])) {
    if ($_POST['login'] === $login && $_POST['password'] === $password) {
        $_SESSION['loggedin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['loggedin'])) {
    echo '<form method="post">
            <label>Логин:</label><br>
            <input type="text" name="login"><br>
            <label>Пароль:</label><br>
            <input type="password" name="password"><br><br>
            <input type="submit" value="Войти">
          </form>';
    if (isset($error)) {
        echo '<p style="color:red;">'.$error.'</p>';
    }
    exit;
}

header('Location: admin.php');
exit;