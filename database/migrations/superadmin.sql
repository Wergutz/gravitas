-- Super Admin Gravitas — nivel 1 — acesso total a TODOS os sistemas e apps
-- Usuário exclusivo do banco Gravitas (u278289683_vh_planeja).
-- Ao fazer login, aparece um seletor para escolher qual sistema acessar.
-- Senha inicial: Super@2026  (force_password_change = 1)
-- Execute UMA VEZ.

INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, force_password_change)
VALUES (
    'superadmin',
    'superadmin@gravitas.net.br',
    '$2y$12$V1VN1Fx6HYH6/I1P36cZSOIKmBYVR3E/Mksdd2/mDdcD0jR2d23mW',
    1,
    1,
    1
);
