<?php
/**
 * Cosul de cumparaturi, pastrat in sesiunea PHP.
 *
 * In sesiune se retin doar perechi produs_id => cantitate; preturile si
 * stocurile sunt citite mereu din baza de date, astfel incat clientul
 * nu poate manipula pretul trimis catre server.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Cos
{
    /** Cantitatea maxima acceptata pe o singura linie. */
    const CANTITATE_MAXIMA = 999;

    /**
     * Returneaza referinta la continutul cosului din sesiune.
     *
     * @return array produs_id => cantitate
     */
    private static function &continut()
    {
        if (!isset($_SESSION['cos']) || !is_array($_SESSION['cos'])) {
            $_SESSION['cos'] = array();
        }
        return $_SESSION['cos'];
    }

    /**
     * Adauga un produs in cos sau mareste cantitatea existenta.
     * Cantitatea este limitata la stocul disponibil.
     *
     * @param int $produsId
     * @param int $cantitate
     * @return string Mesaj de rezultat (gol daca totul a decurs normal).
     */
    public static function adauga($produsId, $cantitate)
    {
        $produs = Produse::dupaId($produsId);
        if (!$produs || !$produs['activ']) {
            return 'Produsul nu exista.';
        }
        if ((int) $produs['stoc'] <= 0) {
            return 'Produsul nu este disponibil in stoc.';
        }
        $cos = &self::continut();
        $existenta = isset($cos[$produsId]) ? $cos[$produsId] : 0;
        $dorita = $existenta + max(1, (int) $cantitate);
        $permisa = min($dorita, (int) $produs['stoc'], self::CANTITATE_MAXIMA);
        $cos[$produsId] = $permisa;
        if ($permisa < $dorita) {
            return 'Cantitatea a fost limitata la stocul disponibil (' . $produs['stoc'] . ').';
        }
        return '';
    }

    /**
     * Seteaza cantitatea unei linii; 0 elimina produsul din cos.
     *
     * @param int $produsId
     * @param int $cantitate
     * @return void
     */
    public static function modifica($produsId, $cantitate)
    {
        $cos = &self::continut();
        $cantitate = (int) $cantitate;
        if ($cantitate <= 0) {
            unset($cos[$produsId]);
            return;
        }
        if (isset($cos[$produsId])) {
            $cos[$produsId] = min($cantitate, self::CANTITATE_MAXIMA);
        }
    }

    /**
     * Elimina un produs din cos.
     *
     * @param int $produsId
     * @return void
     */
    public static function elimina($produsId)
    {
        $cos = &self::continut();
        unset($cos[$produsId]);
    }

    /**
     * Goleste complet cosul (dupa plasarea comenzii).
     *
     * @return void
     */
    public static function goleste()
    {
        $_SESSION['cos'] = array();
        unset($_SESSION['cupon']);
    }

    /**
     * Numarul total de articole din cos (afisat in antet).
     *
     * @return int
     */
    public static function numarArticole()
    {
        return array_sum(self::continut());
    }

    /**
     * Construieste liniile cosului cu datele actuale din catalog.
     * Produsele sterse sau dezactivate sunt eliminate automat, iar
     * cantitatile sunt ajustate la stocul curent.
     *
     * @return array Lista de linii: produs, pret, cantitate, total, avertizare.
     */
    public static function linii()
    {
        $cos = &self::continut();
        if (empty($cos)) {
            return array();
        }
        $produse = Produse::dupaIduri(array_keys($cos));
        $linii = array();
        foreach ($cos as $id => $cantitate) {
            if (!isset($produse[$id]) || !$produse[$id]['activ']) {
                unset($cos[$id]);
                continue;
            }
            $p = $produse[$id];
            $avertizare = '';
            if ($cantitate > (int) $p['stoc']) {
                $avertizare = (int) $p['stoc'] > 0
                    ? 'Stoc insuficient: au ramas doar ' . (int) $p['stoc'] . ' ' . $p['unitate'] . '.'
                    : 'Produsul nu mai este disponibil in stoc.';
            }
            $linii[] = array(
                'produs'     => $p,
                'pret'       => $p['pret'],
                'cantitate'  => $cantitate,
                'total'      => Calculator::totalLinie($p['pret'], $cantitate),
                'avertizare' => $avertizare,
            );
        }
        return $linii;
    }

    /**
     * Verifica daca toate liniile pot fi onorate din stocul curent.
     *
     * @param array $linii Rezultatul metodei linii().
     * @return bool
     */
    public static function stocSuficient(array $linii)
    {
        foreach ($linii as $l) {
            if ($l['avertizare'] !== '') {
                return false;
            }
        }
        return !empty($linii);
    }

    /**
     * Greutatea totala a coletului, folosita la calculul tarifului de curierat.
     *
     * @param array $linii
     * @return float Kilograme.
     */
    public static function greutate(array $linii)
    {
        $kg = 0.0;
        foreach ($linii as $l) {
            $kg += (float) $l['produs']['greutate_kg'] * $l['cantitate'];
        }
        return round($kg, 2);
    }

    /**
     * Calculeaza rezumatul financiar al cosului: subtotal, reducerea
     * din cuponul aplicat, costul livrarii (prin shim-ul de curierat)
     * si totalul de plata.
     *
     * Un cupon devenit invalid (expirat, sub pragul minim) este eliminat
     * automat din sesiune, iar motivul este returnat in 'mesaj_cupon'.
     *
     * @param array  $linii         Rezultatul metodei linii().
     * @param string $metodaLivrare 'curier' sau 'ridicare'.
     * @param string $judet         Judetul de livrare (gol = estimare).
     * @param string $metodaPlata   'ramburs' sau 'card'.
     * @return array
     */
    public static function rezumat(array $linii, $metodaLivrare = 'curier', $judet = '', $metodaPlata = 'ramburs')
    {
        $subtotal = Calculator::subtotal($linii);
        $cupon = null;
        $reducere = 0.0;
        $mesajCupon = '';

        if (!empty($_SESSION['cupon'])) {
            $candidat = Cupoane::dupaCod($_SESSION['cupon']);
            $rezultat = Calculator::aplicaCupon($candidat, $subtotal);
            if ($rezultat['valid']) {
                $cupon = $candidat;
                $reducere = $rezultat['reducere'];
            } else {
                $mesajCupon = $rezultat['mesaj'];
                unset($_SESSION['cupon']);
            }
        }

        $costLivrare = 0.0;
        $zileLivrare = 0;
        $estimare = false;
        if ($metodaLivrare === 'curier' && !empty($linii)) {
            $estimare = ($judet === '');
            $raspuns = CurierShim::tarif($estimare ? 'Bucuresti' : $judet, self::greutate($linii),
                Calculator::total($subtotal, $reducere, 0), $metodaPlata === 'ramburs');
            if (!empty($raspuns['succes'])) {
                $costLivrare = (float) $raspuns['tarif'];
                $zileLivrare = (int) $raspuns['zile_livrare'];
            }
        }

        return array(
            'subtotal'     => $subtotal,
            'reducere'     => $reducere,
            'cupon'        => $cupon,
            'mesaj_cupon'  => $mesajCupon,
            'cost_livrare' => $costLivrare,
            'zile_livrare' => $zileLivrare,
            'estimare'     => $estimare,
            'total'        => Calculator::total($subtotal, $reducere, $costLivrare),
            'tva'          => Calculator::tvaInclus(Calculator::total($subtotal, $reducere, $costLivrare), config('cota_tva')),
        );
    }
}
