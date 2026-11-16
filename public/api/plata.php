<?php
/**
 * Expunerea HTTP/JSON a procesatorului de plati de test (dummy).
 *
 *   POST api/plata.php
 *   {
 *     "cheie_secreta": "sk_test_temelia_0000",
 *     "suma": 150.50, "moneda": "RON", "referinta_comanda": "TEST-1",
 *     "card_numar": "4111111111111111", "card_luna": 12, "card_an": 2030,
 *     "card_cvv": "123", "card_titular": "Ion Popescu"
 *   }
 *
 * Raspuns: {"aprobata": true, "cod": "00", "mesaj": "...", "referinta": "TX...", "ultimele4": "1111"}
 * Endpoint-ul NU modifica nicio comanda; serveste la testarea contractului.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

Api::permiteMetode(array('POST'));
$raspuns = PlataDummy::autorizeaza(Api::corpJson(), config('plata_cheie_secreta'));
Api::raspuns($raspuns['cod'] === '401' ? 401 : ($raspuns['aprobata'] ? 200 : 402), $raspuns);
