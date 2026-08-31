<?php
require_once __DIR__ . '/../app/helpers/auth.php';
auth_required([3]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Equipes | GM SERVIÇOS</title>

<link rel="stylesheet" href="/GM/GM2/public/css/base.css">
<link rel="stylesheet" href="/GM/GM2/public/css/menu.css">
<link rel="stylesheet" href="/GM/GM2/public/css/internal.css">
</head>
<body>

<?php require_once __DIR__ . '/../app/views/menu.php'; ?>
<?php require_once __DIR__ . '/../app/views/header.php'; ?>

<div class="content">
    <h1>Cadastro de Equipes</h1>
</div>

</body>
</html>
