<?php
/**
 * API REST pentru catalogul de produse (A3) - acces public, doar citire.
 *
 *   GET api/produse.php                         lista produselor active
 *       ?q=text&categorie=1&pret_min=&pret_max=&sortare=pret_asc&pagina=1
 *   GET api/produse.php?id=5                    detaliile unui produs
 *   GET api/produse.php?categorii=1             lista categoriilor
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

Api::permiteMetode(array('GET'));

if (param('categorii', '', $_GET) === '1') {
    $categorii = array();
    foreach (Produse::categorii() as $c) {
        $categorii[] = array('id' => (int) $c['id'], 'nume' => $c['nume'], 'slug' => $c['slug'], 'numar_produse' => (int) $c['numar_produse']);
    }
    Api::raspuns(200, array('categorii' => $categorii));
}

if (param('id', '', $_GET) !== '') {
    $produs = Produse::dupaId(param_int('id'));
    if (!$produs || !$produs['activ']) {
        Api::eroare(404, 'Produsul nu exista.');
    }
    Api::raspuns(200, array('produs' => Api::produs($produs, true)));
}

$pePagina = 20;
$pagina = max(1, param_int('pagina', 1));
$pretMin = param('pret_min', '', $_GET);
$pretMax = param('pret_max', '', $_GET);
$rezultat = Produse::cauta(array(
    'cautare'   => param('q', '', $_GET),
    'categorie' => param_int('categorie'),
    'pret_min'  => is_numeric($pretMin) ? $pretMin : '',
    'pret_max'  => is_numeric($pretMax) ? $pretMax : '',
    'sortare'   => param('sortare', 'recomandate', $_GET),
), $pePagina, ($pagina - 1) * $pePagina);

Api::raspuns(200, array(
    'total'       => $rezultat['total'],
    'pagina'      => $pagina,
    'pe_pagina'   => $pePagina,
    'produse'     => array_map(array('Api', 'produs'), $rezultat['produse']),
));
