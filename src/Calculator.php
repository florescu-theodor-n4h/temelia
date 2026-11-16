<?php
/**
 * Calculele financiare ale magazinului: totaluri de linie, subtotal,
 * reduceri, cupoane, TVA si total de plata.
 *
 * Toate metodele sunt statice si "pure" (nu acceseaza baza de date sau
 * sesiunea), ceea ce permite testarea lor automata (vezi teste/).
 * Pentru a evita erorile de rotunjire specifice numerelor in virgula
 * mobila, calculele interne se efectueaza in bani (1 leu = 100 bani).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Calculator
{
    /**
     * Converteste o suma in lei intr-un numar intreg de bani.
     *
     * @param float|string $lei
     * @return int
     */
    public static function inBani($lei)
    {
        return (int) round(((float) $lei) * 100);
    }

    /**
     * Converteste un numar de bani in lei (doua zecimale).
     *
     * @param int $bani
     * @return float
     */
    public static function inLei($bani)
    {
        return round($bani / 100, 2);
    }

    /**
     * Totalul unei linii din cos: pret unitar x cantitate.
     *
     * @param float|string $pretUnitar
     * @param int          $cantitate
     * @return float
     */
    public static function totalLinie($pretUnitar, $cantitate)
    {
        return self::inLei(self::inBani($pretUnitar) * max(0, (int) $cantitate));
    }

    /**
     * Subtotalul unei liste de linii. Fiecare linie trebuie sa contina
     * cheile 'pret' si 'cantitate'.
     *
     * @param array $linii
     * @return float
     */
    public static function subtotal(array $linii)
    {
        $bani = 0;
        foreach ($linii as $linie) {
            $bani += self::inBani($linie['pret']) * max(0, (int) $linie['cantitate']);
        }
        return self::inLei($bani);
    }

    /**
     * Procentul de reducere fata de pretul vechi, rotunjit la intreg.
     * Returneaza 0 daca nu exista reducere.
     *
     * @param float|string      $pret      Pretul curent.
     * @param float|string|null $pretVechi Pretul anterior reducerii.
     * @return int
     */
    public static function procentReducere($pret, $pretVechi)
    {
        $pret = (float) $pret;
        $pretVechi = (float) $pretVechi;
        if ($pretVechi <= 0 || $pret >= $pretVechi) {
            return 0;
        }
        return (int) round(($pretVechi - $pret) / $pretVechi * 100);
    }

    /**
     * Valoarea TVA continuta intr-o suma care include TVA.
     * Formula: suma - suma / (1 + cota/100).
     *
     * @param float|string $sumaCuTva
     * @param int|float    $cota Cota de TVA in procente (ex. 21).
     * @return float
     */
    public static function tvaInclus($sumaCuTva, $cota)
    {
        $bani = self::inBani($sumaCuTva);
        $faraTva = (int) round($bani / (1 + $cota / 100));
        return self::inLei($bani - $faraTva);
    }

    /**
     * Verifica un cupon si calculeaza reducerea aplicabila.
     *
     * Cuponul este un tablou cu cheile: tip ('procent'|'valoare'),
     * valoare, minim_comanda, valabil_pana (Y-m-d sau null),
     * utilizari_max (sau null), utilizari, activ.
     *
     * @param array|null   $cupon    Randul din tabela cupoane sau null.
     * @param float|string $subtotal Subtotalul produselor (cu TVA).
     * @param string       $azi      Data de referinta Y-m-d (pentru teste).
     * @return array array('valid' => bool, 'reducere' => float, 'mesaj' => string)
     */
    public static function aplicaCupon($cupon, $subtotal, $azi = null)
    {
        if ($azi === null) {
            $azi = date('Y-m-d');
        }
        if (!$cupon) {
            return array('valid' => false, 'reducere' => 0.0, 'mesaj' => 'Codul de reducere nu exista.');
        }
        if (empty($cupon['activ']) || $cupon['activ'] === 'f') {
            return array('valid' => false, 'reducere' => 0.0, 'mesaj' => 'Codul de reducere nu mai este activ.');
        }
        if (!empty($cupon['valabil_pana']) && $cupon['valabil_pana'] < $azi) {
            return array('valid' => false, 'reducere' => 0.0, 'mesaj' => 'Codul de reducere a expirat.');
        }
        if ($cupon['utilizari_max'] !== null && $cupon['utilizari_max'] !== ''
            && (int) $cupon['utilizari'] >= (int) $cupon['utilizari_max']) {
            return array('valid' => false, 'reducere' => 0.0, 'mesaj' => 'Codul de reducere a atins numarul maxim de utilizari.');
        }
        $subtotalBani = self::inBani($subtotal);
        if ($subtotalBani < self::inBani($cupon['minim_comanda'])) {
            return array('valid' => false, 'reducere' => 0.0,
                'mesaj' => 'Codul este valabil pentru comenzi de minimum ' . number_format((float) $cupon['minim_comanda'], 2, ',', '.') . ' lei.');
        }

        if ($cupon['tip'] === 'procent') {
            $procent = min(100, (float) $cupon['valoare']);
            $reducereBani = (int) round($subtotalBani * $procent / 100);
        } else {
            $reducereBani = self::inBani($cupon['valoare']);
        }
        // Reducerea nu poate depasi valoarea produselor
        $reducereBani = min($reducereBani, $subtotalBani);

        return array('valid' => true, 'reducere' => self::inLei($reducereBani), 'mesaj' => 'Codul de reducere a fost aplicat.');
    }

    /**
     * Totalul de plata: subtotal - reducere + cost livrare (minimum 0).
     *
     * @param float|string $subtotal
     * @param float|string $reducere
     * @param float|string $costLivrare
     * @return float
     */
    public static function total($subtotal, $reducere, $costLivrare)
    {
        $bani = self::inBani($subtotal) - self::inBani($reducere);
        $bani = max(0, $bani) + self::inBani($costLivrare);
        return self::inLei($bani);
    }
}
