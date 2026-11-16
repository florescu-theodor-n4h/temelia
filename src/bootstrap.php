<?php
/**
 * Fisierul de initializare inclus la inceputul fiecarei pagini.
 *
 * Responsabilitati:
 *   - definirea constantelor de cale;
 *   - incarcarea configurarii si a claselor (fara Composer, fara dependinte);
 *   - pornirea sesiunii cu parametri de securitate;
 *   - trimiterea antetelor HTTP de securitate;
 *   - tratarea centralizata a erorilor.
 *
 * Compatibil PHP 7.0 sau mai nou.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */

define('DIR_RADACINA', dirname(__DIR__));
define('DIR_SRC', __DIR__);
define('DIR_PUBLIC', DIR_RADACINA . '/public');

$GLOBALS['config'] = require DIR_SRC . '/config.php';

/**
 * Acces la o valoare din configurare, cu notatie cu puncte.
 *
 * @param string $cheie    Ex. 'firma.nume'.
 * @param mixed  $implicit
 * @return mixed
 */
function config($cheie, $implicit = null)
{
    $valoare = $GLOBALS['config'];
    foreach (explode('.', $cheie) as $parte) {
        if (!is_array($valoare) || !array_key_exists($parte, $valoare)) {
            return $implicit;
        }
        $valoare = $valoare[$parte];
    }
    return $valoare;
}

// Incarcarea automata a claselor din directorul src/ (NumeClasa.php)
spl_autoload_register(function ($clasa) {
    $fisier = DIR_SRC . '/' . str_replace(array('\\', '..'), '', $clasa) . '.php';
    if (is_file($fisier)) {
        require $fisier;
    }
});

require DIR_SRC . '/functii.php';
require DIR_SRC . '/vizual.php';

date_default_timezone_set('Europe/Bucharest');
mb_internal_encoding_sigur();

/**
 * Seteaza codificarea interna UTF-8 doar daca extensia mbstring exista.
 *
 * @return void
 */
function mb_internal_encoding_sigur()
{
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
}

// Raportarea erorilor: detaliata doar in modul de depanare
error_reporting(E_ALL);
ini_set('display_errors', config('depanare') ? '1' : '0');
ini_set('log_errors', '1');

Baza::configureaza(config('db'));

// Determinarea radacinii web, pentru a permite instalarea intr-un subdirector
if (PHP_SAPI !== 'cli') {
    $urlBaza = (string) config('url_baza');
    if ($urlBaza !== '') {
        $radacina = (string) parse_url($urlBaza, PHP_URL_PATH);
    } else {
        $script = realpath($_SERVER['SCRIPT_FILENAME']);
        $relativ = substr(str_replace('\\', '/', $script), strlen(str_replace('\\', '/', realpath(DIR_PUBLIC))));
        $radacina = substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - strlen($relativ));
    }
    define('RADACINA_WEB', rtrim($radacina, '/') . '/');

    // Sesiune: cookie inaccesibil din JavaScript, trimis doar pe HTTPS cand este disponibil
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $https ? '1' : '0');
    session_name('TEMELIASESIUNE');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array('lifetime' => 0, 'path' => RADACINA_WEB, 'secure' => $https,
            'httponly' => true, 'samesite' => 'Lax'));
    } else {
        session_set_cookie_params(0, RADACINA_WEB . '; samesite=Lax', '', $https, true);
    }
    session_start();

    // Antete de securitate
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'none'; form-action 'self'; frame-ancestors 'self'");

    // Pagina de eroare generica, fara detalii tehnice pentru vizitatori
    set_exception_handler(function ($ex) {
        jurnal('EXCEPTIE: ' . get_class($ex) . ': ' . $ex->getMessage() . ' in ' . $ex->getFile() . ':' . $ex->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>Eroare</title></head><body>'
            . '<h1>Ne pare rau, a aparut o eroare.</h1><p>Va rugam sa incercati din nou peste cateva momente.</p>';
        if (config('depanare')) {
            echo '<pre>' . e($ex->getMessage() . "\n" . $ex->getTraceAsString()) . '</pre>';
        }
        echo '</body></html>';
    });
} else {
    define('RADACINA_WEB', '/');
}
