<?php
/**
 * API REST pentru comenzi (A3) - protejat cu antetul X-Cheie-API.
 *
 *   GET  api/comenzi.php                 lista comenzilor (?status=noua)
 *   GET  api/comenzi.php?numar=TM...     detaliile unei comenzi, cu produse si istoric
 *   POST api/comenzi.php                 schimbarea statusului
 *        corp JSON: {"numar": "TM2026000001", "status": "confirmata", "nota": "..."}
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

Api::permiteMetode(array('GET', 'POST'));
Api::cereCheie();

/**
 * Reprezentarea unei comenzi in API.
 *
 * @param array $c
 * @param bool  $detaliat
 * @return array
 */
function comanda_api(array $c, $detaliat)
{
    $r = array(
        'numar'          => $c['numar'],
        'data'           => $c['creat_la'],
        'status'         => $c['status'],
        'client'         => $c['nume_client'],
        'email'          => $c['email'],
        'judet'          => $c['judet'],
        'metoda_livrare' => $c['metoda_livrare'],
        'metoda_plata'   => $c['metoda_plata'],
        'stare_plata'    => $c['stare_plata'],
        'subtotal'       => (float) $c['subtotal'],
        'discount'       => (float) $c['discount'],
        'cost_livrare'   => (float) $c['cost_livrare'],
        'total'          => (float) $c['total'],
        'awb'            => $c['awb'],
    );
    if ($detaliat) {
        $r['produse'] = array();
        foreach (Comenzi::linii($c['id']) as $l) {
            $r['produse'][] = array('cod_sku' => $l['cod_sku'], 'nume' => $l['nume_produs'],
                'pret_unitar' => (float) $l['pret_unitar'], 'cantitate' => (int) $l['cantitate'], 'total' => (float) $l['total_linie']);
        }
        $r['istoric'] = array();
        foreach (Comenzi::istoric($c['id']) as $h) {
            $r['istoric'][] = array('data' => $h['creat_la'], 'status' => $h['status'], 'nota' => $h['nota']);
        }
    }
    return $r;
}

if (Api::metoda() === 'POST') {
    $corp = Api::corpJson();
    $comanda = isset($corp['numar']) ? Comenzi::dupaNumar((string) $corp['numar']) : null;
    if (!$comanda) {
        Api::eroare(404, 'Comanda nu exista.');
    }
    $status = isset($corp['status']) ? (string) $corp['status'] : '';
    if (!array_key_exists($status, statusuri_comanda())) {
        Api::eroare(422, 'Status invalid. Valori permise: ' . implode(', ', array_keys(statusuri_comanda())) . '.');
    }
    try {
        Comenzi::schimbaStatus($comanda['id'], $status, isset($corp['nota']) ? substr((string) $corp['nota'], 0, 200) : 'Actualizare prin API');
    } catch (RuntimeException $ex) {
        Api::eroare(409, $ex->getMessage());
    }
    Api::raspuns(200, array('comanda' => comanda_api(Comenzi::dupaId($comanda['id']), true)));
}

if (param('numar', '', $_GET) !== '') {
    $comanda = Comenzi::dupaNumar(param('numar', '', $_GET));
    if (!$comanda) {
        Api::eroare(404, 'Comanda nu exista.');
    }
    Api::raspuns(200, array('comanda' => comanda_api($comanda, true)));
}

$lista = array();
foreach (Comenzi::toate(param('status', '', $_GET)) as $c) {
    $lista[] = comanda_api($c, false);
}
Api::raspuns(200, array('total' => count($lista), 'comenzi' => $lista));
