<?php
use App\Core\Response;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Response::escape($title ?? 'Login - Inventory System') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <?= $content ?>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
