<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

$directory = __DIR__;
$files = glob($directory . "/*.html");

if (isset($_GET['file']) && in_array($_GET['file'], $files)) {
    $file = $_GET['file'];
} else {
    $file = $files[0];
}

$content = file_get_contents($file);

// Извлечение <title>
preg_match("/<title>(.*?)<\/title>/s", $content, $titleMatch);
$pageTitle = $titleMatch[1] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Чтение содержимого файла
    $content = file_get_contents($file);
    $originalContent = $content;

    $hasImageChanges = false;

    // ===== ЗАГРУЗКА ИЗОБРАЖЕНИЙ ДЛЯ КАРТОЧЕК =====
    if (isset($_FILES['image']) && is_array($_FILES['image']['tmp_name'])) {
        $uploadDir = __DIR__ . '/images/beerIcons/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['image']['tmp_name'] as $groupKey => $tmpName) {
            if (!empty($tmpName) && $_FILES['image']['error'][$groupKey] === UPLOAD_ERR_OK) {

                // Ожидаем ключ вида Card1, Card2, ...
                if (preg_match('/^Card(\d+)/', $groupKey, $m)) {
                    $num = (int)$m[1];
                    if ($num > 0) {
                        $fileName   = $num . '.png';
                        $targetPath = $uploadDir . $fileName;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $hasImageChanges = true;
                        }
                    }
                }
            }
        }
    }
    // =============================================

    // Сохранение <title>
    if (isset($_POST['title'])) {
        $newTitle = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
        $content = preg_replace("/<title>(.*?)<\/title>/s", "<title>$newTitle</title>", $content);
    }

    // Сохранение содержимого полей
    if (isset($_POST['content'])) {
        foreach ($_POST['content'] as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Кодирование значений

            // Паттерн для поиска и замены
            $pattern = "/<!-- INPUT: $escapedKey -->.*?<!-- END INPUT -->/s";
            $replacement = "<!-- INPUT: $key -->$escapedValue<!-- END INPUT -->";

            $content = preg_replace($pattern, $replacement, $content);
        }
    }

    // Сохранение файла (или просто redirect, если были только картинки)
    if ($content !== $originalContent || $hasImageChanges) {
        // Перезаписываем html (даже если не изменился — это не страшно)
        file_put_contents($file, $content);

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo '<p style="color:blue;">Изменений не обнаружено.</p>';
    }
}

// Распознавание разделителей для редактирования содержимого
preg_match_all("/<!-- (INPUT|TEXTAREA): (.*?) -->(.*?)<!-- END \\1 -->/s", $content, $matches, PREG_SET_ORDER);

// Группировка по карточкам
$groupedFields = [];
foreach ($matches as $match) {
    $parts = explode('_', $match[2], 2);
    $groupKey = $parts[0];
    $fieldLabel = $parts[1] ?? $groupKey;

    $groupedFields[$groupKey][$fieldLabel] = [
        'type' => $match[1],
        'content' => trim($match[3])
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="images/vite.svg">
    <title>Первая страница</title>
</head>
<body class="bg-white admin-panel">
    <div class="container">
        <h2>Редактировать файл: <?php echo basename($file); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <!-- Поле для редактирования <title> -->
                <div class="col-12">
                    <label>Заголовок страницы (title):</label><br>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
                    <br><br>
                </div>

                <!-- Карточки услуг -->
                <?php foreach ($groupedFields as $group => $fields): ?>
                    <div class="col-12 mb-4">
                        <div class="card bg-white border-success border-radius-10">
                            <div class="card-header bg-white">
                                <?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="card-body bg-white">
                                <div class="row">
                                    <?php foreach ($fields as $label => $field): ?>
                                        <div class="col-6 mb-3">
                                            <label><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                                            <?php if ($field['type'] === 'INPUT'): ?>
                                                <input type="text"
                                                       class="form-control"
                                                       name="content[<?php echo htmlspecialchars($group . '_' . $label, ENT_QUOTES, 'UTF-8'); ?>]"
                                                       value="<?php echo htmlspecialchars($field['content'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php elseif ($field['type'] === 'TEXTAREA'): ?>
                                                <textarea class="form-control"
                                                          name="content[<?php echo htmlspecialchars($group . '_' . $label, ENT_QUOTES, 'UTF-8'); ?>]"
                                                          style="width:100%; height:100px;"><?php echo htmlspecialchars($field['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Поле загрузки изображения для карточки -->
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <?php
                                        $imgInfo = '';
                                        if (preg_match('/^Card(\d+)/', $group, $m)) {
                                            $num = (int)$m[1];
                                            if ($num > 0) {
                                                $imgInfo = ' (перезапишет images/beerIcons/' . $num . '.png)';
                                            }
                                        }
                                        ?>
                                        <label>Изображение для карточки<?php echo $imgInfo; ?></label>
                                        <input type="file"
                                               class="form-control"
                                               name="image[<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>]"
                                               accept="image/*">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <input type="submit" class="btn btn-primary" value="Сохранить">
                </div>
            </div>
        </form>
        <h3>Выберите файл для редактирования:</h3>
        <ul>
        <?php foreach ($files as $f): ?>
            <li><a href="?file=<?php echo urlencode($f); ?>"><?php echo basename($f); ?></a></li>
        <?php endforeach; ?>
        </ul>
        <a href="login.php?logout=1">Выйти</a>
    </div>
    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap.bundle.js"></script>
</body>
</html>
