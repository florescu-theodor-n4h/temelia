<?php
/**
 * Harta site-ului in format XML (protocolul sitemaps.org) pentru
 * motoarele de cautare (A12 - SEO): prima pagina, categoriile,
 * produsele active si paginile informative.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$baza = rtrim((string) config('url_baza'), '/');
if ($baza === '') {
    $schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baza = $schema . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . rtrim(RADACINA_WEB, '/');
}

$adrese = array(array('/index.php', date('Y-m-d'), '1.0'));
foreach (Produse::categorii() as $c) {
    $adrese[] = array('/catalog.php?categorie=' . (int) $c['id'], date('Y-m-d'), '0.8');
}
foreach (Baza::toate('SELECT slug, creat_la FROM produse WHERE activ = 1 ORDER BY id') as $p) {
    $adrese[] = array('/produs.php?p=' . rawurlencode($p['slug']), substr($p['creat_la'], 0, 10), '0.6');
}
foreach (array('livrare', 'retur', 'termeni', 'confidentialitate', 'cookies', 'contact') as $pagina) {
    $adrese[] = array('/' . $pagina . '.php', date('Y-m-d', filemtime(__DIR__ . '/' . $pagina . '.php')), '0.3');
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($adrese as $a) {
    echo '  <url><loc>' . e($baza . $a[0]) . '</loc><lastmod>' . e($a[1]) . '</lastmod><priority>' . $a[2] . "</priority></url>\n";
}
echo '</urlset>' . "\n";
