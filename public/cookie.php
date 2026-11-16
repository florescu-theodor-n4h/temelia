<?php
/**
 * Inregistrarea optiunii vizitatorului privind cookie-urile (Accept /
 * Refuz). Functioneaza fara JavaScript: bannerul trimite un formular,
 * iar optiunea este retinuta 180 de zile intr-un cookie strict necesar.
 *
 * GET ?reseteaza=1 sterge optiunea, iar bannerul este afisat din nou.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$optiuniCookie = array('expires' => 0, 'path' => RADACINA_WEB, 'httponly' => true, 'samesite' => 'Lax');

if (param('reseteaza', '', $_GET) === '1') {
    setcookie('consimtamant_cookie', '', time() - 3600, RADACINA_WEB);
    flash('info', 'Preferintele privind cookie-urile au fost resetate.');
    redirectioneaza('cookies.php');
}

if (este_post()) {
    csrf_verifica();
    $valoare = param('optiune', '', $_POST) === 'Accept' ? 'acceptat' : 'refuzat';
    if (PHP_VERSION_ID >= 70300) {
        $optiuniCookie['expires'] = time() + 180 * 86400;
        setcookie('consimtamant_cookie', $valoare, $optiuniCookie);
    } else {
        setcookie('consimtamant_cookie', $valoare, time() + 180 * 86400, RADACINA_WEB, '', false, true);
    }
    flash('info', $valoare === 'acceptat'
        ? 'Va multumim! Ati acceptat folosirea cookie-urilor.'
        : 'Ati refuzat cookie-urile optionale. Vom folosi doar cookie-urile strict necesare.');
    header('Location: ' . adresa_locala(param('inapoi', '', $_POST)));
    exit;
}

redirectioneaza('cookies.php');
