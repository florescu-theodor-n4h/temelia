<?php
/**
 * Configurarea generala a aplicatiei TEMELIA.
 *
 * Valorile implicite pot fi suprascrise in doua moduri, in ordinea
 * prioritatii:
 *   1. prin variabile de mediu (ex. TEMELIA_DB_HOST), utile pe gazduire;
 *   2. prin fisierul src/config.local.php, care returneaza un tablou
 *      cu aceleasi chei si NU se adauga in depozitul Git.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */

$config = array(
    // Date de identificare ale societatii (afisate in subsol si pe pagina de contact)
    'firma' => array(
        'nume'       => 'TEMELIA Materiale de Constructii S.R.L.',
        'marca'      => 'TEMELIA',
        'slogan'     => 'Materiale de constructii pentru profesionisti si amatori',
        'cui'        => 'RO 12345678',
        'reg_com'    => 'J16/1234/2026',
        'adresa'     => 'Str. Depozitelor nr. 10, Craiova, jud. Dolj, 200000',
        'telefon'    => '0251 000 000',
        'email'      => 'contact@temelia.example.ro',
        'program'    => 'Luni - Vineri 08:00 - 18:00, Sambata 08:00 - 14:00',
    ),

    // Conexiunea la baza de date PostgreSQL
    'db' => array(
        'host'   => '127.0.0.1',
        'port'   => '5432',
        'nume'   => 'temelia',
        'user'   => 'temelia',
        'parola' => 'temelia',
    ),

    // Adresa de baza a site-ului, fara bara finala (ex. https://temelia.example.ro)
    'url_baza' => '',

    // Cota de TVA aplicata (preturile din catalog includ deja TVA)
    'cota_tva' => 21,

    // Cheia necesara pentru endpoint-urile protejate ale API-ului REST
    'cheie_api' => 'temelia-cheie-test-2026',

    // Procesatorul de plati de test: cheia secreta a comerciantului
    'plata_cheie_secreta' => 'sk_test_temelia_0000',

    // Limita pentru fisierele imagine incarcate din administrare (octeti)
    'imagine_max_octeti' => 2097152,

    // Numar de produse afisate pe o pagina de catalog
    'produse_pe_pagina' => 12,

    // Mod depanare: afiseaza erorile (a se dezactiva in productie)
    'depanare' => false,
);

// Suprascrierea din fisierul local, daca exista
$fisierLocal = __DIR__ . '/config.local.php';
if (is_file($fisierLocal)) {
    $local = require $fisierLocal;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

// Suprascrierea din variabile de mediu (prioritate maxima)
$harta = array(
    'TEMELIA_DB_HOST'   => array('db', 'host'),
    'TEMELIA_DB_PORT'   => array('db', 'port'),
    'TEMELIA_DB_NUME'   => array('db', 'nume'),
    'TEMELIA_DB_USER'   => array('db', 'user'),
    'TEMELIA_DB_PAROLA' => array('db', 'parola'),
    'TEMELIA_URL_BAZA'  => array('url_baza'),
    'TEMELIA_CHEIE_API' => array('cheie_api'),
);
foreach ($harta as $variabila => $cale) {
    $valoare = getenv($variabila);
    if ($valoare === false || $valoare === '') {
        continue;
    }
    if (count($cale) === 2) {
        $config[$cale[0]][$cale[1]] = $valoare;
    } else {
        $config[$cale[0]] = $valoare;
    }
}

return $config;
