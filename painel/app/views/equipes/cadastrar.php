<?php
$title = 'Nova Equipe';
$pageTitle = 'Nova Equipe';
$pageSubtitle = 'Cadastro completo da equipe';

ob_start();
?>

<div class="card">

<form method="post" action="<?= APP_BASE ?>/equipes/salvar">

    <!-- DADOS DA EQUIPE -->
    <div class="campo">
        <label>Nome da Equipe</label>
        <input type="text" name="nome" required>
    </div>

    <div class="campo">
        <label>Responsável (Executor)</label>
        <select name="responsavel_id" required>
            <option value="">Selecione</option>
            <?php foreach ($executores as $e): ?>
                <option value="<?= $e['id'] ?>">
                    <?= htmlspecialchars($e['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- FUNCIONÁRIOS -->
    <div class="campo">
        <label>Funcionários da Equipe (máx. 10)</label>

        <div id="funcionarios-container">
            <div class="campo">
                <select name="funcionarios[]" class="funcionario-select" onchange="validarDuplicados()">
                    <option value="">Selecione</option>
                    <?php foreach ($funcionarios as $f): ?>
                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="campo">
        <button type="button"
                class="btn btn-sec btn-sm"
                style="width:auto; align-self:flex-start; padding:6px 12px; font-size:12px;"
                onclick="addFuncionario()">
            + Incluir funcionário
        </button>
    </div>

    <!-- MÁQUINAS LEVES -->
    <div class="campo">
        <label>Máquinas Leves</label>

        <div id="leves-container">
            <div class="campo">
                <select name="maquinas_leves[]">
                    <option value="">Selecione</option>
                    <?php foreach ($maquinasLeves as $m): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['referencia']) ?> - <?= htmlspecialchars($m['modelo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="campo">
        <button type="button"
                class="btn btn-sec btn-sm"
                style="width:auto; align-self:flex-start; padding:6px 12px; font-size:12px;"
                onclick="addLeve()">
            + Incluir máquina leve
        </button>
    </div>

    <!-- MÁQUINAS PESADAS -->
    <div class="campo">
        <label>Máquinas Pesadas</label>

        <div id="pesadas-container">
            <div class="campo">
                <select name="maquinas_pesadas[]">
                    <option value="">Selecione</option>
                    <?php foreach ($maquinasPesadas as $m): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['tipo']) ?> - <?= htmlspecialchars($m['placa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="campo">
        <button type="button"
                class="btn btn-sec btn-sm"
                style="width:auto; align-self:flex-start; padding:6px 12px; font-size:12px;"
                onclick="addPesada()">
            + Incluir máquina pesada
        </button>
    </div>

    <!-- AÇÕES -->
    <div class="form-actions">
        <button class="btn btn-pri btn-sm">Salvar</button>
        <a href="<?= APP_BASE ?>/equipes" class="btn btn-sec btn-sm">Cancelar</a>
    </div>

</form>
</div>

<script>
function validarDuplicados() {
    const usados = [];
    document.querySelectorAll('.funcionario-select').forEach(sel => {
        if (sel.value && usados.includes(sel.value)) {
            alert('Funcionário já incluído na equipe.');
            sel.value = '';
        }
        if (sel.value) usados.push(sel.value);
    });
}

function addFuncionario() {
    const container = document.getElementById('funcionarios-container');
    if (container.children.length >= 10) return;

    const clone = container.children[0].cloneNode(true);
    clone.querySelector('select').value = '';
    container.appendChild(clone);
}

function addLeve() {
    const c = document.getElementById('leves-container');
    const clone = c.children[0].cloneNode(true);
    clone.querySelector('select').value = '';
    c.appendChild(clone);
}

function addPesada() {
    const c = document.getElementById('pesadas-container');
    const clone = c.children[0].cloneNode(true);
    clone.querySelector('select').value = '';
    c.appendChild(clone);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
