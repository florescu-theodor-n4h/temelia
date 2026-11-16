<?php
/**
 * Procesator de plati cu cardul de TEST ("dummy"), care imita API-ul unui
 * procesator real (de tipul celor oferite de bancile comerciale).
 *
 * Nu se efectueaza nicio tranzactie reala. Numarul complet al cardului si
 * codul CVV nu sunt salvate niciodata: dupa autorizare se pastreaza doar
 * ultimele 4 cifre si referinta tranzactiei.
 *
 * Carduri de test:
 *   4111 1111 1111 1111  -> plata aprobata
 *   5555 5555 5555 4444  -> plata aprobata (Mastercard)
 *   4000 0000 0000 0002  -> respinsa: fonduri insuficiente
 *   4000 0000 0000 0069  -> respinsa: card expirat / blocat de banca
 * Orice alt numar valid conform algoritmului Luhn este aprobat.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class PlataDummy
{
    /** Carduri de test cu raspuns predefinit: numar => array(cod, mesaj). */
    private static $carduriRespinse = array(
        '4000000000000002' => array('51', 'Fonduri insuficiente.'),
        '4000000000000069' => array('54', 'Cardul este expirat sau blocat de banca emitenta.'),
    );

    /**
     * Echivalentul apelului POST /v1/autorizare al procesatorului.
     *
     * Cerere: cheie_secreta, suma, moneda, referinta_comanda, card_numar,
     *         card_luna, card_an, card_cvv, card_titular.
     *
     * @param array  $cerere
     * @param string $cheieSecreta Cheia comerciantului din configurare.
     * @return array Raspuns: aprobata, cod, mesaj, referinta, ultimele4.
     */
    public static function autorizeaza(array $cerere, $cheieSecreta)
    {
        $c = function ($k) use ($cerere) {
            return isset($cerere[$k]) ? trim((string) $cerere[$k]) : '';
        };

        if (!hash_equals((string) $cheieSecreta, $c('cheie_secreta'))) {
            return self::raspuns(false, '401', 'Cheia comerciantului nu este valida.', '');
        }
        $suma = (float) $c('suma');
        if ($suma <= 0) {
            return self::raspuns(false, '13', 'Suma tranzactiei nu este valida.', '');
        }
        if ($c('moneda') !== 'RON') {
            return self::raspuns(false, '13', 'Moneda nu este acceptata.', '');
        }

        $numar = preg_replace('/\D/', '', $c('card_numar'));
        $ultimele4 = substr($numar, -4);
        if (strlen($numar) < 13 || strlen($numar) > 19 || !self::luhn($numar)) {
            return self::raspuns(false, '14', 'Numarul cardului nu este valid.', $ultimele4);
        }
        if (!self::expirareValida($c('card_luna'), $c('card_an'))) {
            return self::raspuns(false, '54', 'Data de expirare a cardului nu este valida.', $ultimele4);
        }
        if (!preg_match('/^\d{3,4}$/', $c('card_cvv'))) {
            return self::raspuns(false, '82', 'Codul CVV nu este valid.', $ultimele4);
        }
        if (strlen($c('card_titular')) < 3) {
            return self::raspuns(false, '14', 'Numele titularului cardului este obligatoriu.', $ultimele4);
        }
        if (isset(self::$carduriRespinse[$numar])) {
            list($cod, $mesaj) = self::$carduriRespinse[$numar];
            return self::raspuns(false, $cod, $mesaj, $ultimele4);
        }
        return self::raspuns(true, '00', 'Tranzactie aprobata.', $ultimele4);
    }

    /**
     * Verifica cifra de control a unui numar de card (algoritmul Luhn).
     *
     * @param string $numar Doar cifre.
     * @return bool
     */
    public static function luhn($numar)
    {
        if (!preg_match('/^\d+$/', (string) $numar)) {
            return false;
        }
        $suma = 0;
        $dubleaza = false;
        for ($i = strlen($numar) - 1; $i >= 0; $i--) {
            $cifra = (int) $numar[$i];
            if ($dubleaza) {
                $cifra *= 2;
                if ($cifra > 9) {
                    $cifra -= 9;
                }
            }
            $suma += $cifra;
            $dubleaza = !$dubleaza;
        }
        return $suma % 10 === 0;
    }

    /**
     * Verifica daca data de expirare (luna/an) este in viitor.
     *
     * @param string|int $luna 1-12
     * @param string|int $an   Doua sau patru cifre.
     * @param int|null   $acum Moment de referinta (pentru teste).
     * @return bool
     */
    public static function expirareValida($luna, $an, $acum = null)
    {
        $acum = $acum === null ? time() : $acum;
        $luna = (int) $luna;
        $an = (int) $an;
        if ($an < 100) {
            $an += 2000;
        }
        if ($luna < 1 || $luna > 12) {
            return false;
        }
        $anCurent = (int) date('Y', $acum);
        $lunaCurenta = (int) date('n', $acum);
        return $an > $anCurent || ($an === $anCurent && $luna >= $lunaCurenta);
    }

    /**
     * Construieste raspunsul standard al procesatorului.
     *
     * @param bool   $aprobata
     * @param string $cod       Cod de raspuns ISO 8583 simplificat ('00' = aprobat).
     * @param string $mesaj
     * @param string $ultimele4
     * @return array
     */
    private static function raspuns($aprobata, $cod, $mesaj, $ultimele4)
    {
        return array(
            'aprobata'  => $aprobata,
            'cod'       => $cod,
            'mesaj'     => $mesaj,
            'referinta' => 'TX' . strtoupper(bin2hex(random_bytes(8))),
            'ultimele4' => $ultimele4,
        );
    }
}
