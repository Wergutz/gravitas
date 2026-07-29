<?php
require_once __DIR__ . '/../app/helpers/auth.php';
auth_required([3]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipes | VisionHub Locar</title>

<link rel="stylesheet" href="/visionhub_locar/public/css/base.css">
<link rel="stylesheet" href="/visionhub_locar/public/css/menu.css">
<link rel="stylesheet" href="/visionhub_locar/public/css/internal.css">
</head>
<body>

<?php require_once __DIR__ . '/../app/views/menu.php'; ?>
<?php require_once __DIR__ . '/../app/views/header.php'; ?>

<div class="content">
    <h1>Cadastro de Equipes</h1>
</div>

</body>
</html>
