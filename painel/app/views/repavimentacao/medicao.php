<?php
require_once __DIR__ . '/../../helpers/csrf.php';

$title     = 'Medição de Repavimentação';
$pageTitle = 'Medição de Repavimentação';
$pageSubtitle = 'PV ' . htmlspecialchars($trecho['pv_montante'] ?? '') . ' → ' . htmlspecialchars($trecho['pv_jusante'] ?? '');

ob_start();
?>

<div style="margin-bottom:14px;">
    <a href="<?= APP_BASE ?>/repavimentacao" class="btn btn-sec btn-sm">← Voltar à fila</a>
</div>

<div class="grade2" style="align-items:start;">

    <!-- Formulário de novo pavimento -->
    <div>
        <div class="card mb16">
            <div class="label">Dados do Trecho</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">
                <div><span style="color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">PV Montante</span><br><b><?= htmlspecialchars($trecho['pv_montante']) ?></b></div>
                <div><span style="color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">PV Jusante</span><br><?= htmlspecialchars($trecho['pv_jusante'] ?? '—') ?></div>
                <div><span style="color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Extensão</span><br><?= $trecho['extensao'] ? number_format((float)$trecho['extensao'], 2, ',', '.') . ' m' : '—' ?></div>
                <div><span style="color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Rua</span><br><?= htmlspecialchars($trecho['rua'] ?? '—') ?></div>
            </div>
        </div>

        <div class="card mb16">
            <div class="label">Adicionar Pavimento</div>
            <form method="post" action="<?= APP_BASE ?>/repavimentacao/salvar-pavimento">
                <?= csrf_input() ?>
                <input type="hidden" name="medicao_id" value="<?= (int)$medicao['id'] ?>">

                <div class="form-grid col2">
                    <div class="campo">
                        <label>Tipo de Pavimento <span style="color:var(--erro)">*</span></label>
                        <select name="tipo_pavimento" required>
                            <option value="">Selecione</option>
                            <option value="paralelepipedo_regular">Paralelepípedo Regular</option>
                            <option value="paralelepipedo_irregular">Paralelepípedo Irregular</option>
                            <option value="bloco_concreto">Bloco de Concreto</option>
                            <option value="asfalto">Asfalto</option>
                            <option value="asfalto_paralelepipedo">Asfalto + Paralelepípedo</option>
                            <option value="chao_batido">Chão Batido</option>
                            <option value="calcada">Calçada</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label>Espessura (cm) <small style="color:var(--muted);">(asfalto)</small></label>
                        <input type="text" name="espessura_cm" placeholder="Ex: 5.00">
                    </div>
                </div>

                <!-- Linhas de dimensão -->
                <div class="label" style="margin-top:8px;">Dimensões (comprimento × largura)</div>
                <div id="linhas-container">
                    <div class="form-grid col2 linha-dim" style="margin-bottom:6px;">
                        <div class="campo">
                            <label>Comprimento (m)</label>
                            <input type="text" name="comprimentos[]" placeholder="0.00">
                        </div>
                        <div class="campo">
                            <label>Largura (m)</label>
                            <input type="text" name="larguras[]" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-sec btn-sm" style="margin-bottom:14px;" onclick="addLinha()">
                    + Adicionar linha
                </button>

                <div class="form-actions">
                    <button type="submit" class="btn btn-pri">Salvar pavimento</button>
                </div>
            </form>
        </div>

        <!-- Upload de foto -->
        <div class="card">
            <div class="label">Upload de Foto</div>
            <form method="post" action="<?= APP_BASE ?>/repavimentacao/upload-foto" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="medicao_id" value="<?= (int)$medicao['id'] ?>">

                <div class="form-grid col2">
                    <div class="campo">
                        <label>Tipo de Foto</label>
                        <select name="tipo_foto" required>
                            <option value="antes">Antes</option>
                            <option value="durante">Durante</option>
                            <option value="depois">Depois</option>
                            <option value="croqui">Croqui</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label>Imagem</label>
                        <input type="file" name="foto" accept="image/*" required
                               style="font-family:inherit;font-size:13px;padding:8px;border:1px solid var(--line);border-radius:8px;width:100%;">
                    </div>
                </div>

                <small style="color:var(--muted);font-size:11px;">JPEG, PNG, WebP — máx. 10MB. Redimensionado para max 1600px.</small>

                <div class="form-actions">
                    <button type="submit" class="btn btn-pri">Enviar foto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pavimentos cadastrados + fotos -->
    <div>
        <div class="card mb16">
            <div class="label">Pavimentos cadastrados (<?= count($pavimentos) ?>)</div>

            <?php if (empty($pavimentos)): ?>
                <p style="color:var(--muted);font-size:13px;">Nenhum pavimento cadastrado ainda.</p>
            <?php else: ?>
                <?php
                $tipos_legenda = [
                    'paralelepipedo_regular'   => 'Paralel. Regular',
                    'paralelepipedo_irregular' => 'Paralel. Irregular',
                    'bloco_concreto'           => 'Bloco Concreto',
                    'asfalto'                  => 'Asfalto',
                    'asfalto_paralelepipedo'   => 'Asf. + Paralel.',
                    'chao_batido'              => 'Chão Batido',
                    'calcada'                  => 'Calçada',
                ];
                ?>
                <?php foreach ($pavimentos as $pav): ?>
                    <?php
                    $tipo_leg = $tipos_legenda[$pav['tipo_pavimento']] ?? $pav['tipo_pavimento'];
                    $area = 0;
                    if ($pav['linhas_raw']) {
                        foreach (explode('|', $pav['linhas_raw']) as $linha) {
                            [$c, $l] = explode('x', $linha . 'x0');
                            $area += (float)$c * (float)$l;
                        }
                    }
                    ?>
                    <div style="border:1px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:8px;">
                        <div class="flex-between" style="margin-bottom:6px;">
                            <b style="font-size:13px;"><?= htmlspecialchars($tipo_leg) ?></b>
                            <span style="color:var(--muted);font-size:12px;">Área: <?= number_format($area, 2, ',', '.') ?> m²</span>
                        </div>
                        <?php if ($pav['espessura_cm']): ?>
                            <span style="font-size:11.5px;color:var(--muted);">Espessura: <?= number_format((float)$pav['espessura_cm'], 1, ',', '.') ?> cm &nbsp;</span>
                        <?php endif; ?>
                        <?php if ($pav['linhas_raw']): ?>
                            <div style="font-size:11.5px;color:var(--muted);margin-top:4px;">
                                <?php foreach (explode('|', $pav['linhas_raw']) as $idx => $linha): ?>
                                    <?php [$c, $l] = explode('x', $linha . 'x0'); ?>
                                    <span><?= ($idx > 0 ? ' + ' : '') ?><?= number_format((float)$c, 2, ',', '.') ?>m × <?= number_format((float)$l, 2, ',', '.') ?>m</span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Fotos -->
        <?php if (!empty($fotos)): ?>
        <div class="card">
            <div class="label">Fotos (<?= count($fotos) ?>)</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;">
                <?php foreach ($fotos as $foto): ?>
                    <div style="text-align:center;">
                        <a href="<?= APP_BASE ?>/uploads/repavimentacao/<?= htmlspecialchars($foto['arquivo']) ?>" target="_blank">
                            <img src="<?= APP_BASE ?>/uploads/repavimentacao/<?= htmlspecialchars($foto['thumb']) ?>"
                                 alt="<?= htmlspecialchars($foto['tipo']) ?>"
                                 style="width:100%;border-radius:6px;border:1px solid var(--line);"
                                 onerror="this.style.display='none'">
                        </a>
                        <span style="font-size:10px;color:var(--muted);"><?= htmlspecialchars($foto['tipo']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function addLinha() {
    var c = document.getElementById('linhas-container');
    var clone = c.querySelector('.linha-dim').cloneNode(true);
    clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
    c.appendChild(clone);
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/planejador.php';
