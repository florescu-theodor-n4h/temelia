<?php
/**
 * Utilitare comune pentru endpoint-urile REST (A3): raspunsuri JSON,
 * citirea corpului cererii si verificarea cheii API.
 *
 * Conventii:
 *   - toate raspunsurile sunt JSON UTF-8;
 *   - erorile au forma {"eroare": "mesaj"} si un cod HTTP adecvat;
 *   - endpoint-urile protejate cer antetul "X-Cheie-API".
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Api
{
    /**
     * Trimite un raspuns JSON si opreste executia.
     *
     * @param int   $cod  Codul HTTP.
     * @param mixed $date Datele serializate.
     * @return void
     */
    public static function raspuns($cod, $date)
    {
        http_response_code($cod);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($date, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Raspuns de eroare.
     *
     * @param int    $cod
     * @param string $mesaj
     * @return void
     */
    public static function eroare($cod, $mesaj)
    {
        self::raspuns($cod, array('eroare' => $mesaj));
    }

    /**
     * Metoda HTTP a cererii (GET, POST ...).
     *
     * @return string
     */
    public static function metoda()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    /**
     * Permite doar metodele indicate; altfel raspunde cu 405.
     *
     * @param array $metode
     * @return void
     */
    public static function permiteMetode(array $metode)
    {
        if (!in_array(self::metoda(), $metode, true)) {
            header('Allow: ' . implode(', ', $metode));
            self::eroare(405, 'Metoda HTTP nu este permisa pentru acest endpoint.');
        }
    }

    /**
     * Decodifica corpul JSON al cererii.
     *
     * @return array
     */
    public static function corpJson()
    {
        $brut = file_get_contents('php://input');
        if ($brut === '' || $brut === false) {
            return array();
        }
        $date = json_decode($brut, true);
        if (!is_array($date)) {
            self::eroare(400, 'Corpul cererii trebuie sa fie un obiect JSON valid.');
        }
        return $date;
    }

    /**
     * Verifica antetul X-Cheie-API; raspunde cu 401 daca lipseste sau e gresit.
     *
     * @return void
     */
    public static function cereCheie()
    {
        $cheie = isset($_SERVER['HTTP_X_CHEIE_API']) ? (string) $_SERVER['HTTP_X_CHEIE_API'] : '';
        if ($cheie === '' || !hash_equals((string) config('cheie_api'), $cheie)) {
            self::eroare(401, 'Cheie API lipsa sau invalida (antetul X-Cheie-API).');
        }
    }

    /**
     * Reprezentarea publica a unui produs in API.
     *
     * @param array $p
     * @param bool  $detaliat Include descrierea si specificatiile.
     * @return array
     */
    public static function produs(array $p, $detaliat = false)
    {
        $rezultat = array(
            'id'                => (int) $p['id'],
            'cod_sku'           => $p['cod_sku'],
            'nume'              => $p['nume'],
            'slug'              => $p['slug'],
            'categorie_id'      => (int) $p['categorie_id'],
            'pret'              => (float) $p['pret'],
            'pret_vechi'        => $p['pret_vechi'] !== null ? (float) $p['pret_vechi'] : null,
            'procent_reducere'  => Calculator::procentReducere($p['pret'], $p['pret_vechi']),
            'moneda'            => 'RON',
            'tva_inclus'        => true,
            'stoc'              => (int) $p['stoc'],
            'unitate'           => $p['unitate'],
            'imagine'           => imagine_produs($p),
        );
        if ($detaliat) {
            $rezultat['descriere'] = $p['descriere'];
            $rezultat['greutate_kg'] = (float) $p['greutate_kg'];
            $rezultat['specificatii'] = Produse::specificatii($p['id']);
        }
        return $rezultat;
    }
}
