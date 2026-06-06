<?php
// app/controllers/PlanejamentoController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Planejamento.php';

class PlanejamentoController
{
    public static function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /public/planejador.php");
            exit;
        }

        $dados = [
            'titulo'       => trim($_POST['titulo'] ?? ''),
            'data_inicio'  => $_POST['data_inicio'] ?? '',
            'data_fim'     => $_POST['data_fim'] ?? '',
            'descricao'    => trim($_POST['descricao'] ?? '')
        ];

        if (empty($dados['titulo']) || empty($dados['data_inicio'])) {
            $_SESSION['erro'] = "Preencha os campos obrigatórios.";
            header("Location: /public/planejador.php");
            exit;
        }

        $planejamento = new Planejamento($GLOBALS['pdo']);
        $planejamento->inserir($dados);

        $_SESSION['sucesso'] = "Planejamento cadastrado com sucesso!";
        header("Location: /public/planejador.php");
        exit;
    }
}
