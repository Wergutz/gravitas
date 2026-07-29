-- =====================================================================
-- Atualiza a senha dos usuários do marco_urbano2 (VisionHub Locar)
-- para o padrão já usado no marco_urbano: Marco@2026
--
-- Rodar manualmente no phpMyAdmin do banco `u278289683_marco_urbano2`.
--
-- Hash gerado com password_hash(..., PASSWORD_DEFAULT) via PHP CLI
-- (PHP 8.4, algoritmo bcrypt $2y$), verificado com password_verify().
-- =====================================================================

UPDATE `usuarios` SET `senha` = '$2y$12$ajTNZxO8rdvfrrdqxA7UeuNPUWn1l/SR5wdToMsC7fojqACajODb.'
WHERE `usuario` IN ('planejador_isaias', 'executor2_mu');
