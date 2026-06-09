-- Super Admin — nivel 1 — acesso total a todos os apps
-- Senha inicial: Super@2026  (force_password_change = 1)
-- Execute UMA VEZ em cada banco.

-- ── Banco Gravitas (u278289683_vh_planeja) ──────────────────────────────────
INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, force_password_change)
VALUES (
    'superadmin',
    'superadmin@gravitas.net.br',
    '$2y$12$V1VN1Fx6HYH6/I1P36cZSOIKmBYVR3E/Mksdd2/mDdcD0jR2d23mW',
    1,
    1,
    1
);
