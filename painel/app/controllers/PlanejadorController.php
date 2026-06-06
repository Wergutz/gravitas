<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

class PlanejadorController
{
    public function dashboard()
    {
        auth_required([4]); // Planejador

        global $pdo;

        /* ===============================
           KPIs DO DASHBOARD
           =============================== */

        // Planejamentos ativos
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM planejamentos 
            WHERE status = 'ATIVO'
        ");
        $total_planejamentos_ativos = (int)$stmt->fetchColumn();

        // Equipes ativas
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM equipes 
            WHERE ativo = 1
        ");
        $total_equipes_ativas = (int)$stmt->fetchColumn();

        // Funcionários ativos
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM funcionarios 
            WHERE ativo = 1
        ");
        $total_funcionarios_ativos = (int)$stmt->fetchColumn();

        /* ===============================
           CARREGAR VIEW
           =============================== */

        require __DIR__ . '/../views/planejador/dashboard.php';
    }
}
