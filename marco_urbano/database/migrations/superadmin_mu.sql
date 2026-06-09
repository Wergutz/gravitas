-- Super Admin Marco Urbano — nivel 1 — acesso total a todos os apps MU
-- Senha inicial: Super@2026  (force_password_change = 1)
-- Execute UMA VEZ no banco u278289683_marco_urbano.

INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, force_password_change)
VALUES (
    'superadmin_mu',
    'superadmin@marcourbanourbanizadora.com.br',
    '$2y$12$V1VN1Fx6HYH6/I1P36cZSOIKmBYVR3E/Mksdd2/mDdcD0jR2d23mW',
    1,
    1,
    1
);
