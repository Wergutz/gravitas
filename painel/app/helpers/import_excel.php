<?php

/**
 * Shared helpers for 2-phase Excel import (PA11).
 * Must be required AFTER vendor/autoload.php.
 */

function import_parse_date($val): ?string
{
    if ($val === null || $val === '' || $val === false) return null;
    if (is_float($val) || (is_int($val) && $val > 1000)) {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$val)
                ->format('Y-m-d');
        } catch (\Exception $e) {}
    }
    if (is_string($val)) {
        $v = trim($val);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $v);
            if ($d && $d->format($fmt) === $v) return $d->format('Y-m-d');
        }
        // Try lenient parse
        try {
            $d = new \DateTime($v);
            return $d->format('Y-m-d');
        } catch (\Exception $e) {}
    }
    return null;
}

function import_doc_status(string $val): int
{
    return strtolower(trim($val)) === 'apto' ? 1 : 0;
}

function import_preview_totals(array $rows): array
{
    $t = ['novo' => 0, 'atualizar' => 0, 'erro' => 0, 'ignorar' => 0];
    foreach ($rows as $r) {
        $s = $r['_status'] ?? 'ignorar';
        if (isset($t[$s])) $t[$s]++;
        else $t['ignorar']++;
    }
    return $t;
}

function import_row_class(string $status): string
{
    return match($status) {
        'novo'      => 'imp-novo',
        'atualizar' => 'imp-atualizar',
        'erro'      => 'imp-erro',
        default     => 'imp-ignorar',
    };
}

function import_status_label(string $status): string
{
    return match($status) {
        'novo'      => '✅ Novo',
        'atualizar' => '🔄 Atualizar',
        'erro'      => '❌ Erro',
        default     => '— Ignorar',
    };
}
