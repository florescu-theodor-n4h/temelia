<?php
/**
 * Expunerea HTTP/JSON a shim-ului de curierat RapidCurier.
 *
 * Permite testarea contractului API-ului de curier cu orice client HTTP
 * (curl, Postman), exact ca in cazul unei integrari reale:
 *
 *   POST api/curier.php?endpoint=tarif     {"judet_destinatie":"Cluj","greutate_kg":42,"valoare_declarata":300,"ramburs":true}
 *   POST api/curier.php?endpoint=awb       (necesita X-Cheie-API) {"destinatar":"...","telefon":"...","adresa":"...","judet":"...","greutate_kg":3}
 *   GET  api/curier.php?endpoint=urmarire&awb=RC20261116000123
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

$endpoint = param('endpoint', '', $_GET);

switch ($endpoint) {
    case 'tarif':
        Api::permiteMetode(array('POST'));
        $raspuns = CurierShim::transport('/v1/tarif', Api::corpJson());
        break;

    case 'awb':
        Api::permiteMetode(array('POST'));
        Api::cereCheie();
        $raspuns = CurierShim::transport('/v1/awb', Api::corpJson());
        break;

    case 'urmarire':
        Api::permiteMetode(array('GET'));
        $raspuns = CurierShim::transport('/v1/urmarire', array('awb' => param('awb', '', $_GET)));
        break;

    default:
        Api::eroare(404, 'Endpoint necunoscut. Valori permise: tarif, awb, urmarire.');
}

Api::raspuns(isset($raspuns['cod']) ? (int) $raspuns['cod'] : 200, $raspuns);
