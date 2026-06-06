<?php
$pageTitle = 'Importar Equipamentos Leves';
$pageSubtitle = 'Importação em lote via Excel';

ob_start();
?>

<form method="post"
      action="<?= APP_BASE ?>/equipamentos-leves/importar-excel"
      enctype="multipart/form-data"
      class="form-padrao">

    <div class="form-group">
        <label>Arquivo Excel (.xlsx)</label>
        <input type="file" name="excel" required>
    </div>

    <div class="acoes-form">
        <button type="submit" class="btn btn-primary">
            Importar
        </button>

        <a href="<?= APP_BASE ?>/equipamentos-leves" class="btn btn-secondary">
            Cancelar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
