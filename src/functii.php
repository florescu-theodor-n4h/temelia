<?php
/**
 * Functii ajutatoare utilizate in intreaga aplicatie: escapare HTML,
 * generare de adrese, redirectionari, protectie CSRF, mesaje flash si
 * formatarea valorilor monetare.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */

/**
 * Escapeaza un text pentru afisare sigura in HTML (protectie XSS).
 *
 * @param mixed $text Valoarea de afisat.
 * @return string Textul cu caracterele speciale convertite in entitati.
 */
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/**
 * Construieste o adresa absoluta (fata de radacina site-ului) catre o
 * pagina, optional cu parametri de interogare.
 *
 * @param string $cale      Calea relativa, ex. 'produs.php'.
 * @param array  $parametri Parametri GET adaugati la adresa.
 * @return string
 */
function url($cale = '', array $parametri = array())
{
    $adresa = rtrim(RADACINA_WEB, '/') . '/' . ltrim($cale, '/');
    if (!empty($parametri)) {
        $adresa .= '?' . http_build_query($parametri);
    }
    return $adresa;
}

/**
 * Redirectioneaza browserul catre o alta pagina si opreste executia.
 *
 * @param string $cale      Calea relativa la radacina site-ului.
 * @param array  $parametri Parametri GET optionali.
 * @return void
 */
function redirectioneaza($cale, array $parametri = array())
{
    header('Location: ' . url($cale, $parametri));
    exit;
}

/**
 * Verifica daca cererea curenta este de tip POST.
 *
 * @return bool
 */
function este_post()
{
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Citeste un parametru din $_GET sau $_POST sub forma de text curatat.
 *
 * @param string $nume     Numele parametrului.
 * @param string $implicit Valoarea folosita daca parametrul lipseste.
 * @param array  $sursa    Sursa datelor (implicit $_REQUEST combinat).
 * @return string
 */
function param($nume, $implicit = '', $sursa = null)
{
    if ($sursa === null) {
        $sursa = array_merge($_GET, $_POST);
    }
    if (!isset($sursa[$nume]) || is_array($sursa[$nume])) {
        return $implicit;
    }
    return trim((string) $sursa[$nume]);
}

/**
 * Citeste un parametru numeric intreg.
 *
 * @param string $nume
 * @param int    $implicit
 * @return int
 */
function param_int($nume, $implicit = 0)
{
    $v = param($nume, '');
    return ($v !== '' && preg_match('/^-?\d+$/', $v)) ? (int) $v : $implicit;
}

/* ------------------------------------------------------------------ */
/*  Protectie impotriva atacurilor CSRF                               */
/* ------------------------------------------------------------------ */

/**
 * Returneaza jetonul CSRF al sesiunii curente, generandu-l la nevoie.
 *
 * @return string
 */
function csrf_jeton()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Genereaza campul ascuns care transporta jetonul CSRF in formulare.
 *
 * @return string Cod XHTML.
 */
function csrf_camp()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_jeton()) . '" />';
}

/**
 * Verifica jetonul CSRF trimis prin POST; opreste cererea daca nu este valid.
 *
 * @return void
 */
function csrf_verifica()
{
    $trimis = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if ($trimis === '' || !hash_equals(csrf_jeton(), $trimis)) {
        http_response_code(400);
        exit('Cererea a expirat sau nu este valida. Va rugam sa reincarcati pagina si sa incercati din nou.');
    }
}

/* ------------------------------------------------------------------ */
/*  Mesaje flash (afisate o singura data, dupa redirectionare)        */
/* ------------------------------------------------------------------ */

/**
 * Adauga un mesaj care va fi afisat la urmatoarea incarcare de pagina.
 *
 * @param string $tip   Tipul mesajului: 'succes', 'eroare' sau 'info'.
 * @param string $text  Continutul mesajului.
 * @return void
 */
function flash($tip, $text)
{
    $_SESSION['flash'][] = array('tip' => $tip, 'text' => $text);
}

/**
 * Extrage si sterge mesajele flash din sesiune.
 *
 * @return array
 */
function preia_flash()
{
    $mesaje = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $mesaje;
}

/* ------------------------------------------------------------------ */
/*  Formatare                                                          */
/* ------------------------------------------------------------------ */

/**
 * Formateaza o suma in lei, conform conventiei romanesti (1.234,50 lei).
 *
 * @param float|string $suma
 * @return string
 */
function pret($suma)
{
    return number_format((float) $suma, 2, ',', '.') . ' lei';
}

/**
 * Formateaza o data calendaristica in format romanesc (zz.ll.aaaa oo:mm).
 *
 * @param string $data   Data in format acceptat de strtotime().
 * @param bool   $cuOra  Include si ora.
 * @return string
 */
function data_ro($data, $cuOra = true)
{
    $t = strtotime($data);
    return $t ? date($cuOra ? 'd.m.Y H:i' : 'd.m.Y', $t) : '';
}

/**
 * Lista statusurilor de comanda cu denumirile afisate.
 *
 * @return array cod => eticheta
 */
function statusuri_comanda()
{
    return array(
        'noua'       => 'Noua',
        'confirmata' => 'Confirmata',
        'expediata'  => 'Expediata',
        'livrata'    => 'Livrata',
        'anulata'    => 'Anulata',
    );
}

/**
 * Returneaza eticheta lizibila a unui status de comanda.
 *
 * @param string $cod
 * @return string
 */
function eticheta_status($cod)
{
    $s = statusuri_comanda();
    return isset($s[$cod]) ? $s[$cod] : $cod;
}

/**
 * Denumirile metodelor de livrare si de plata.
 *
 * @param string $cod
 * @return string
 */
function eticheta_metoda($cod)
{
    $etichete = array(
        'curier'     => 'Livrare prin curier (RapidCurier)',
        'ridicare'   => 'Ridicare personala din depozitul Craiova',
        'ramburs'    => 'Plata ramburs (numerar la livrare)',
        'card'       => 'Plata online cu cardul',
        'neplatita'  => 'Neplatita',
        'platita'    => 'Platita',
        'respinsa'   => 'Plata respinsa',
        'rambursata' => 'Rambursata',
    );
    return isset($etichete[$cod]) ? $etichete[$cod] : $cod;
}

/**
 * Lista judetelor din Romania, folosita in formularele de adresa.
 *
 * @return array
 */
function judete()
{
    return array('Alba', 'Arad', 'Arges', 'Bacau', 'Bihor', 'Bistrita-Nasaud', 'Botosani',
        'Braila', 'Brasov', 'Bucuresti', 'Buzau', 'Calarasi', 'Caras-Severin', 'Cluj',
        'Constanta', 'Covasna', 'Dambovita', 'Dolj', 'Galati', 'Giurgiu', 'Gorj', 'Harghita',
        'Hunedoara', 'Ialomita', 'Iasi', 'Ilfov', 'Maramures', 'Mehedinti', 'Mures', 'Neamt',
        'Olt', 'Prahova', 'Salaj', 'Satu Mare', 'Sibiu', 'Suceava', 'Teleorman', 'Timis',
        'Tulcea', 'Valcea', 'Vaslui', 'Vrancea');
}

/**
 * Transforma un text intr-un identificator potrivit pentru adrese (slug).
 *
 * @param string $text
 * @return string
 */
function slugifica($text)
{
    $inlocuiri = array('ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'a', 'Â' => 'a', 'Î' => 'i', 'Ș' => 's', 'Ş' => 's', 'Ț' => 't', 'Ţ' => 't');
    $text = strtr($text, $inlocuiri);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Returneaza adresa imaginii unui produs sau imaginea implicita.
 *
 * @param array $produs Randul produsului din baza de date.
 * @return string
 */
function imagine_produs(array $produs)
{
    if (!empty($produs['imagine'])) {
        $fisier = basename($produs['imagine']);
        if (is_file(DIR_PUBLIC . '/img/incarcate/' . $fisier)) {
            return url('img/incarcate/' . $fisier);
        }
        if (is_file(DIR_PUBLIC . '/img/produse/' . $fisier)) {
            return url('img/produse/' . $fisier);
        }
    }
    return url('img/fara-imagine.svg');
}

/**
 * Inregistreaza un mesaj in jurnalul aplicatiei (jurnal/aplicatie.log).
 *
 * @param string $mesaj
 * @return void
 */
function jurnal($mesaj)
{
    $linie = '[' . date('Y-m-d H:i:s') . '] ' . $mesaj . PHP_EOL;
    @file_put_contents(DIR_RADACINA . '/jurnal/aplicatie.log', $linie, FILE_APPEND | LOCK_EX);
}

/**
 * Verifica daca vizitatorul curent are dreptul sa vada o comanda:
 * administratorii, titularul contului care a plasat comanda sau
 * vizitatorul care a plasat-o in sesiunea curenta (comanda fara cont).
 *
 * @param array $comanda
 * @return bool
 */
function poate_vedea_comanda(array $comanda)
{
    if (Autentificare::esteAdmin()) {
        return true;
    }
    $u = Autentificare::utilizator();
    if ($u && (int) $comanda['utilizator_id'] === (int) $u['id']) {
        return true;
    }
    $sesiune = isset($_SESSION['comenzi_sesiune']) ? (array) $_SESSION['comenzi_sesiune'] : array();
    return in_array($comanda['numar'], $sesiune, true);
}
