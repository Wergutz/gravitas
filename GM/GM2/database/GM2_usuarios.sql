-- =====================================================================
-- Usuários iniciais do banco GM2 (GM SERVIÇOS)
-- =====================================================================
--
-- Artefato de referência, fora do deploy — rodar manualmente via
-- phpMyAdmin depois de aplicar GM2_schema.sql no banco novo
-- `u278289683_GM2`.
--
-- Tabela `usuarios`: id, nome, usuario, senha, tipo_usuario, ativo,
-- criado_em, atualizado_em. O login aqui é por USUÁRIO/SENHA, não por
-- e-mail — diferente do resto do GM, que entra por e-mail no /login/.
--
-- Esta tabela NÃO tem coluna de "forçar troca de senha". Repasse a
-- senha temporária abaixo e cobre a troca no primeiro acesso, porque o
-- sistema não vai exigir sozinho.
--
-- Senha temporária: Gm@2026 (a mesma dos usuários do GM)
-- Hash bcrypt custo 12, gerado com password_hash() no PHP 8.
--
-- Níveis deste programa (numeração própria, não confundir com a do GM):
--   1 = MASTER   2 = PROPRIETÁRIO   3 = PLANEJADOR   4 = EXECUTOR
--
-- Criados abaixo apenas o MASTER e o PLANEJADOR: são os dois únicos
-- perfis com tela implementada. As pastas executor/ e proprietario/
-- estão vazias, então usuário desses níveis entraria e não teria para
-- onde ir. Criar quando as telas existirem.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `usuarios` (`nome`, `usuario`, `senha`, `tipo_usuario`, `ativo`)
VALUES
(
    'Master GM',
    'master_gm2',
    '$2y$12$KA9c5SFjPWKZIa/XzWdr7uCs0JSM67S./UOyZCiQPZcsn7krsXNWy',
    1,
    1
),
(
    'Planejador GM',
    'planejador_gm2',
    '$2y$12$KA9c5SFjPWKZIa/XzWdr7uCs0JSM67S./UOyZCiQPZcsn7krsXNWy',
    3,
    1
);
