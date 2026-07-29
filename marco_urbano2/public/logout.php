<?php
session_start();

/* Remove todas as variáveis de sessão */
$_SESSION = [];

/* Destroi a sessão */
session_destroy();

/* Redireciona para o login */
header('Location: /visionhub_locar/public/login.php');
exit;
