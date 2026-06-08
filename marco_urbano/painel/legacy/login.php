<?php
// login.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* CORREÇÃO ÚNICA E NECESSÁRIA */
require_once __DIR__ . '/app/controllers/AuthController.php';

AuthController::login();
