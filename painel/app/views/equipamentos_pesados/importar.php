<?php
$title = 'Importar Equipamentos Pesados';
$pageTitle = 'Equipamentos Pesados';
$pageSubtitle = 'Importação via Excel';

ob_start();
?>

<div class="card">

    <form method="post"
          action="<?= APP_BASE ?>/equipamentos-pesados/importar-excel"
          enctype="multipart/form-data">

        <div class="campo">
            <label>Arquivo Excel (.xlsx)</label>
            <input type="file" name="excel" required>
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn btn-pri">
                Importar
            </button>

            <a href="<?= APP_BASE ?>/equipamentos-pesados" class="btn btn-sec">
                Cancelar
            </a>
        </div>

    </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
