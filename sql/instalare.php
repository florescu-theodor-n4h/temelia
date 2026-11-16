<?php
/**
 * Script de instalare (linie de comanda) pentru baza de date TEMELIA.
 *
 * Utilizare:
 *     php sql/instalare.php            instalare (refuza daca exista deja date)
 *     php sql/instalare.php --forta    reinstalare: STERGE toate datele existente
 *     php sql/instalare.php --fara-demo  fara comenzile demonstrative
 *
 * Pasi:
 *   1. creeaza schema din sql/schema.sql, adaptand cheia primara la driver;
 *   2. incarca datele initiale din sql/date_initiale.sql;
 *   3. creeaza conturile de test (parole generate cu password_hash);
 *   4. optional, genereaza comenzi demonstrative pentru tabloul de bord.
 *
 * Functioneaza identic pe SQLite (implicit), PostgreSQL si MySQL.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Scriptul de instalare poate fi rulat doar din linia de comanda.');
}

require __DIR__ . '/../src/bootstrap.php';

$argumente = array_slice($argv, 1);
$forta = in_array('--forta', $argumente, true);
$cuDemo = !in_array('--fara-demo', $argumente, true);

/**
 * Afiseaza un mesaj de progres.
 *
 * @param string $text
 * @return void
 */
function scrie($text)
{
    fwrite(STDOUT, $text . PHP_EOL);
}

/**
 * Imparte un fisier SQL in instructiuni (separator: ';' la final de linie)
 * si elimina comentariile de tip "--".
 *
 * @param string $continut
 * @return array
 */
function instructiuni_sql($continut)
{
    $fara = preg_replace('/^\s*--.*$/m', '', $continut);
    $parti = preg_split('/;\s*$/m', $fara);
    return array_values(array_filter(array_map('trim', $parti), 'strlen'));
}

$driver = Baza::driver();
scrie('TEMELIA - instalare baza de date (driver: ' . $driver . ')');
if ($driver === 'sqlite') {
    scrie('Fisier SQLite: ' . config('db.fisier'));
}

// Protectie: nu se suprascriu datele existente fara --forta
try {
    $existente = (int) Baza::valoare('SELECT COUNT(*) FROM produse');
} catch (PDOException $ex) {
    $existente = -1;
}
if ($existente >= 0 && !$forta) {
    scrie('EROARE: baza de date contine deja tabele. Folositi --forta pentru reinstalare (datele vor fi sterse).');
    exit(1);
}

$cheiePrimara = array(
    'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
    'pgsql'  => 'SERIAL PRIMARY KEY',
    'mysql'  => 'INT AUTO_INCREMENT PRIMARY KEY',
);

// 1. Schema
$pdo = Baza::pdo();
if ($driver === 'sqlite') {
    $pdo->exec('PRAGMA foreign_keys = OFF');
}
if ($driver === 'mysql') {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
}
$schema = str_replace('{PK}', $cheiePrimara[$driver], file_get_contents(__DIR__ . '/schema.sql'));
foreach (instructiuni_sql($schema) as $sql) {
    if ($driver === 'pgsql' && stripos($sql, 'DROP TABLE') === 0) {
        $sql .= ' CASCADE';
    }
    $pdo->exec($sql);
}
if ($driver === 'sqlite') {
    $pdo->exec('PRAGMA foreign_keys = ON');
}
if ($driver === 'mysql') {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
scrie('[1/4] Schema a fost creata.');

// 2. Date initiale
Baza::tranzactie(function () use ($pdo) {
    foreach (instructiuni_sql(file_get_contents(__DIR__ . '/date_initiale.sql')) as $sql) {
        $pdo->exec($sql);
    }
});
// PostgreSQL: sincronizarea secventelor dupa inserarea cu ID-uri explicite
if ($driver === 'pgsql') {
    foreach (array('categorii', 'produse') as $tabel) {
        $pdo->exec("SELECT setval('{$tabel}_id_seq', (SELECT MAX(id) FROM {$tabel}))");
    }
}
scrie('[2/4] Datele initiale au fost incarcate: ' . Baza::valoare('SELECT COUNT(*) FROM produse') . ' produse in '
    . Baza::valoare('SELECT COUNT(*) FROM categorii') . ' categorii.');

// 3. Conturi de test
$conturi = array(
    array('Administrator TEMELIA', 'admin@temelia.ro', 'Admin2026!', '0251000000', 'admin'),
    array('Ion Popescu', 'client@temelia.ro', 'Client2026!', '0722123456', 'client'),
);
$iduri = array();
foreach ($conturi as $c) {
    $iduri[$c[4]] = Baza::insereaza('utilizatori', array(
        'nume' => $c[0], 'email' => $c[1], 'parola_hash' => password_hash($c[2], PASSWORD_DEFAULT),
        'telefon' => $c[3], 'rol' => $c[4], 'creat_la' => Baza::acum(),
    ));
}
scrie('[3/4] Conturi de test: admin@temelia.ro / Admin2026!  si  client@temelia.ro / Client2026!');

// 4. Comenzi demonstrative (pentru tabloul de bord si istoricul clientului)
if ($cuDemo) {
    $client = array(
        'nume_client' => 'Ion Popescu', 'telefon' => '0722123456', 'email' => 'client@temelia.ro',
        'adresa' => 'Str. Unirii nr. 15', 'oras' => 'Craiova', 'judet' => 'Dolj', 'cod_postal' => '200585',
        'metoda_livrare' => 'curier', 'metoda_plata' => 'ramburs', 'observatii' => 'Comanda demonstrativa',
    );
    // produs => cantitate, zile in urma, status final
    $scenarii = array(
        array(array(1 => 1, 3 => 1), 12, 'livrata'),
        array(array(6 => 20, 7 => 5), 9, 'livrata'),
        array(array(19 => 2, 20 => 2, 21 => 1), 6, 'expediata'),
        array(array(11 => 1, 12 => 6, 13 => 4), 4, 'confirmata'),
        array(array(25 => 1, 23 => 1), 2, 'noua'),
        array(array(16 => 1), 1, 'anulata'),
    );
    foreach ($scenarii as $s) {
        $produse = Produse::dupaIduri(array_keys($s[0]));
        $linii = array();
        foreach ($s[0] as $pid => $cant) {
            $linii[] = array('produs' => $produse[$pid], 'pret' => $produse[$pid]['pret'], 'cantitate' => $cant,
                'total' => Calculator::totalLinie($produse[$pid]['pret'], $cant));
        }
        $subtotal = Calculator::subtotal($linii);
        $kg = 0;
        foreach ($linii as $l) {
            $kg += $l['produs']['greutate_kg'] * $l['cantitate'];
        }
        $livrare = CurierShim::calculeazaTarif($client['judet'], $kg, $subtotal, true);
        $comanda = Comenzi::plaseaza($client, $linii, array('subtotal' => $subtotal, 'discount' => 0,
            'cost_livrare' => $livrare, 'total' => Calculator::total($subtotal, 0, $livrare)), null, $iduri['client']);

        $traseu = array('confirmata', 'expediata', 'livrata');
        foreach ($traseu as $pas) {
            if ($s[2] === 'anulata' || $s[2] === 'noua') {
                break;
            }
            Comenzi::schimbaStatus($comanda['id'], $pas, 'Demonstrativ');
            if ($pas === $s[2]) {
                break;
            }
        }
        if ($s[2] === 'anulata') {
            Comenzi::schimbaStatus($comanda['id'], 'anulata', 'Anulata la cererea clientului (demonstrativ)');
        }
        // Mutarea datelor in trecut, pentru graficul ultimelor 14 zile
        $data = date('Y-m-d H:i:s', strtotime('-' . $s[1] . ' days 10:30'));
        Baza::executa('UPDATE comenzi SET creat_la = ?, actualizat_la = ? WHERE id = ?', array($data, $data, $comanda['id']));
        Baza::executa('UPDATE istoric_status SET creat_la = ? WHERE comanda_id = ?', array($data, $comanda['id']));
    }
    Favorite::salveazaRecenzie($iduri['client'], 1, 5, 'Masina foarte buna pentru gaurit in beton. Livrare rapida, recomand!');
    Favorite::salveazaRecenzie($iduri['client'], 6, 4, 'Ciment de calitate, sacii au ajuns intacti pe palet.');
    scrie('[4/4] Au fost create ' . count($scenarii) . ' comenzi demonstrative si 2 recenzii.');
} else {
    scrie('[4/4] Comenzile demonstrative au fost omise (--fara-demo).');
}

scrie('Instalare finalizata. Porniti serverul: php -S localhost:8000 -t public');
