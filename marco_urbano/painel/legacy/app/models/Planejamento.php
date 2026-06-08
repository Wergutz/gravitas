<?php
// app/models/Planejamento.php

class Planejamento
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function inserir($dados)
    {
        $sql = "INSERT INTO planejamentos 
                (titulo, data_inicio, data_fim, descricao)
                VALUES (:titulo, :data_inicio, :data_fim, :descricao)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':titulo'       => $dados['titulo'],
            ':data_inicio'  => $dados['data_inicio'],
            ':data_fim'     => $dados['data_fim'],
            ':descricao'    => $dados['descricao']
        ]);
    }
}
