<?php
$title = 'Importar Funcionários';
$pageTitle = 'Importar Funcionários';
$pageSubtitle = 'Cadastro em lote via Excel (.xlsx)';

ob_start();
?>

<div class="form-card">

    <form method="post"
          action="<?= APP_BASE ?>/funcionarios/importar"
          enctype="multipart/form-data">

        <div class="form-group">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" accept=".xlsx" required>
        </div>

        <p style="font-size:14px;opacity:0.8;margin-top:10px;">
            Ordem das colunas:<br>
            Nome | CPF | Empresa | Função | Salário | ASO | NR06 | NR10 | NR11 | NR12 | NR18 | NR20 | NR23 | NR33 | NR35 | Integração CORSAN | SERTRAS
        </p>

        <div class="form-actions">
            <button class="btn-primary">Importar</button>
            <a href="<?= APP_BASE ?>/funcionarios" class="btn-secondary">Cancelar</a>
        </div>

    </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
