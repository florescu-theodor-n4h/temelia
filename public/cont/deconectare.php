<?php
/**
 * Deconectarea utilizatorului. Legatura contine jetonul CSRF, astfel
 * incat un site extern nu poate deconecta utilizatorul fara stirea lui.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

$jeton = param('csrf', '', $_GET);
if ($jeton !== '' && hash_equals(csrf_jeton(), $jeton)) {
    Autentificare::deconecteaza();
    flash('info', 'Ati fost deconectat. Va asteptam cu drag!');
}
redirectioneaza('index.php');
