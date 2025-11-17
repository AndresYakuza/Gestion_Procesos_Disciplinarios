<?php
namespace App\Domain\Furd;

final class FurdPhases
{
    public const REGISTRO = 'registrado';
    public const CITACION = 'citacion';
    public const DESCARGOS = 'descargos';
    public const SOPORTE = 'soporte';
    public const DECISION = 'decision';

    /** Orden estricto del flujo */
    public static function ordered(): array
    {
        return [
            self::REGISTRO,
            self::CITACION,
            self::DESCARGOS,
            self::SOPORTE,
            self::DECISION,
        ];
    }

    /** Retorna la fase anterior (o null si es la primera) */
    public static function previousOf(string $phase): ?string
    {
        $seq = self::ordered();
        $idx = array_search($phase, $seq, true);
        if ($idx === false || $idx === 0) return null;
        return $seq[$idx - 1];
    }

    /** Retorna la fase siguiente (o null si es la última) */
    public static function nextOf(string $phase): ?string
    {
        $seq = self::ordered();
        $idx = array_search($phase, $seq, true);
        if ($idx === false || $idx === count($seq) - 1) return null;
        return $seq[$idx + 1];
    }

    /** Devuelve el índice de orden (0..N) */
    public static function indexOf(string $phase): int
    {
        $seq = self::ordered();
        $idx = array_search($phase, $seq, true);
        return $idx === false ? -1 : $idx;
    }
}
