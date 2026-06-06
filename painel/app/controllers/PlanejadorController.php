<?php

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

class PlanejadorController
{
    public function dashboard()
    {
        auth_required([4]);
        global $pdo;

        $hoje = date('Y-m-d');

        /* ===============================
           KPI: Caminhamentos de hoje
           =============================== */
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM caminhamentos WHERE data_execucao = ?");
        $stmt->execute([$hoje]);
        $caminhamentos_hoje = (int)$stmt->fetchColumn();

        /* ===============================
           KPI: Metros programados hoje
           (soma extensao dos trechos em caminhamentos de hoje)
           =============================== */
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(t.extensao), 0)
            FROM caminhamento_trechos ct
            JOIN caminhamentos c ON c.id = ct.caminhamento_id
            JOIN trechos t ON t.id = ct.trecho_id
            WHERE c.data_execucao = ?
        ");
        $stmt->execute([$hoje]);
        $metros_programados = (float)$stmt->fetchColumn();

        /* ===============================
           KPI: Equipes ativas
           =============================== */
        $stmt = $pdo->query("SELECT COUNT(*) FROM equipes WHERE ativo = 1");
        $equipes_ativas = (int)$stmt->fetchColumn();

        /* ===============================
           KPI: Trechos sem OS ativa
           =============================== */
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM trechos
            WHERE id NOT IN (SELECT trecho_id FROM ordens_servico WHERE ativa = 1)
        ");
        $trechos_sem_os = (int)$stmt->fetchColumn();

        /* ===============================
           KPI: Documentos a vencer em 15 dias
           =============================== */
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM funcionario_documentos
            WHERE data_validade BETWEEN ? AND DATE_ADD(?, INTERVAL 15 DAY)
        ");
        $stmt->execute([$hoje, $hoje]);
        $docs_vencer = (int)$stmt->fetchColumn();

        /* ===============================
           KPI: Trechos aguardando repavimentação
           =============================== */
        $stmt = $pdo->query("SELECT COUNT(*) FROM trechos WHERE status_repav = 'aguardando'");
        $repav_pendentes = (int)$stmt->fetchColumn();

        /* ===============================
           Caminhamentos do dia com equipe
           =============================== */
        $stmt = $pdo->prepare("
            SELECT c.id, c.status, c.observacoes, e.nome AS equipe_nome,
                   (SELECT COUNT(*) FROM caminhamento_trechos ct WHERE ct.caminhamento_id = c.id) AS total_trechos
            FROM caminhamentos c
            JOIN equipes e ON e.id = c.equipe_id
            WHERE c.data_execucao = ?
            ORDER BY e.nome
        ");
        $stmt->execute([$hoje]);
        $caminhamentos_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ===============================
           Pendências: trechos sem OS (até 5)
           =============================== */
        $stmt = $pdo->query("
            SELECT id, pv_montante, pv_jusante, bacia, rua
            FROM trechos
            WHERE id NOT IN (SELECT trecho_id FROM ordens_servico WHERE ativa = 1)
            ORDER BY id DESC
            LIMIT 5
        ");
        $pendencias_sem_os = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ===============================
           Pendências: documentos a vencer (até 5)
           =============================== */
        $stmt = $pdo->prepare("
            SELECT fd.tipo, fd.data_validade, f.nome AS funcionario_nome
            FROM funcionario_documentos fd
            JOIN funcionarios f ON f.id = fd.funcionario_id
            WHERE fd.data_validade BETWEEN ? AND DATE_ADD(?, INTERVAL 15 DAY)
            ORDER BY fd.data_validade
            LIMIT 5
        ");
        $stmt->execute([$hoje, $hoje]);
        $pendencias_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/planejador/dashboard.php';
    }
}
