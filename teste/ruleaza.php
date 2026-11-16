<?php
/**
 * Teste automate (A16) - fara dependinte externe (fara PHPUnit).
 *
 * Utilizare:  php teste/ruleaza.php
 *
 * Testele unitare verifica logica pura (calcule, validari, curier,
 * plati). Testele de integrare ruleaza pe o baza de date SQLite
 * TEMPORARA (creata si stearsa automat), astfel incat datele reale nu
 * sunt afectate: cos, plasarea comenzii, scaderea si restituirea
 * stocului, cupoane, cautare.
 *
 * Codul de iesire este 0 daca toate testele trec si 1 in caz contrar.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
if (PHP_SAPI !== 'cli') {
    exit('Testele se ruleaza din linia de comanda.');
}

// Baza de date temporara, izolata de cea a aplicatiei
$fisierTest = sys_get_temp_dir() . '/temelia_test_' . getmypid() . '.sqlite';
putenv('TEMELIA_DB_DRIVER=sqlite');
putenv('TEMELIA_DB_FISIER=' . $fisierTest);

require __DIR__ . '/../src/bootstrap.php';

$GLOBALS['rezultate'] = array('trecute' => 0, 'esuate' => 0, 'detalii' => array());

/**
 * Inregistreaza rezultatul unei verificari.
 *
 * @param bool   $conditie
 * @param string $descriere
 * @return void
 */
function verifica($conditie, $descriere)
{
    if ($conditie) {
        $GLOBALS['rezultate']['trecute']++;
        fwrite(STDOUT, "  [OK]   $descriere\n");
    } else {
        $GLOBALS['rezultate']['esuate']++;
        fwrite(STDOUT, "  [ESEC] $descriere\n");
    }
}

/**
 * Verifica egalitatea a doua valori (cu toleranta pentru numere reale).
 *
 * @param mixed  $asteptat
 * @param mixed  $obtinut
 * @param string $descriere
 * @return void
 */
function egal($asteptat, $obtinut, $descriere)
{
    $ok = (is_float($asteptat) || is_float($obtinut))
        ? abs((float) $asteptat - (float) $obtinut) < 0.001
        : $asteptat === $obtinut;
    verifica($ok, $descriere . ($ok ? '' : ' (asteptat: ' . var_export($asteptat, true) . ', obtinut: ' . var_export($obtinut, true) . ')'));
}

/**
 * Verifica faptul ca o functie arunca o exceptie.
 *
 * @param callable $functie
 * @param string   $descriere
 * @return void
 */
function arunca($functie, $descriere)
{
    try {
        $functie();
        verifica(false, $descriere);
    } catch (Exception $ex) {
        verifica(true, $descriere);
    }
}

/**
 * Titlul unui grup de teste.
 *
 * @param string $nume
 * @return void
 */
function grup($nume)
{
    fwrite(STDOUT, "\n== $nume ==\n");
}

/* ================================================================== */
/*  TESTE UNITARE                                                     */
/* ================================================================== */

grup('Calculator - totaluri si TVA');
egal(59.80, Calculator::totalLinie(29.90, 2), 'Total linie 29,90 x 2 = 59,80');
egal(0.30, Calculator::totalLinie(0.10, 3), 'Fara erori de rotunjire: 0,10 x 3 = 0,30');
egal(0.0, Calculator::totalLinie(10, -5), 'Cantitatea negativa este tratata ca 0');
egal(104.20, Calculator::subtotal(array(array('pret' => 34.50, 'cantitate' => 2), array('pret' => 35.20, 'cantitate' => 1))), 'Subtotal pentru doua linii');
egal(21.0, Calculator::tvaInclus(121, 21), 'TVA 21% inclus in 121 lei = 21 lei');
egal(17.36, Calculator::tvaInclus(100, 21), 'TVA 21% inclus in 100 lei = 17,36 lei');
egal(110.0, Calculator::total(100, 10, 20), 'Total = 100 - 10 + 20');
egal(20.0, Calculator::total(50, 80, 20), 'Reducerea mai mare decat subtotalul nu produce total negativ');

grup('Calculator - procent de reducere');
egal(17, Calculator::procentReducere(289.90, 349.90), 'Reducere 349,90 -> 289,90 = 17%');
egal(0, Calculator::procentReducere(100, null), 'Fara pret vechi nu exista reducere');
egal(0, Calculator::procentReducere(120, 100), 'Pret vechi mai mic nu inseamna reducere');

grup('Calculator - cupoane');
$procent10 = array('tip' => 'procent', 'valoare' => 10, 'minim_comanda' => 0, 'valabil_pana' => null, 'utilizari_max' => null, 'utilizari' => 0, 'activ' => 1);
$fix50 = array('tip' => 'valoare', 'valoare' => 50, 'minim_comanda' => 500, 'valabil_pana' => null, 'utilizari_max' => 100, 'utilizari' => 0, 'activ' => 1);
$r = Calculator::aplicaCupon($procent10, 250, '2026-11-16');
verifica($r['valid'], 'Cuponul de 10% este valid');
egal(25.0, $r['reducere'], 'Reducerea de 10% din 250 lei = 25 lei');
$r = Calculator::aplicaCupon($fix50, 499.99, '2026-11-16');
verifica(!$r['valid'], 'Cuponul de 50 lei este respins sub pragul de 500 lei');
$r = Calculator::aplicaCupon($fix50, 600, '2026-11-16');
egal(50.0, $r['reducere'], 'Cuponul de 50 lei se aplica peste prag');
$r = Calculator::aplicaCupon(array_merge($fix50, array('minim_comanda' => 0)), 30, '2026-11-16');
egal(30.0, $r['reducere'], 'Reducerea fixa nu depaseste subtotalul');
$r = Calculator::aplicaCupon(array_merge($procent10, array('valabil_pana' => '2024-08-31')), 100, '2026-11-16');
verifica(!$r['valid'], 'Cuponul expirat este respins');
$r = Calculator::aplicaCupon(array_merge($fix50, array('utilizari' => 100)), 600, '2026-11-16');
verifica(!$r['valid'], 'Cuponul epuizat (utilizari maxime) este respins');
$r = Calculator::aplicaCupon(array_merge($procent10, array('activ' => 0)), 100, '2026-11-16');
verifica(!$r['valid'], 'Cuponul inactiv este respins');
verifica(!Calculator::aplicaCupon(null, 100)['valid'], 'Un cod inexistent este respins');

grup('Validator');
$v = new Validator(array('email' => 'nu-este-email', 'telefon' => '12345', 'nume' => '', 'cod' => '20058'));
$v->obligatoriu('nume', 'Numele')->email('email', 'E-mailul')->telefon('telefon', 'Telefonul')->codPostal('cod', 'Codul postal');
verifica(!$v->esteValid(), 'Datele invalide sunt respinse');
egal(4, count($v->erori()), 'Sunt raportate 4 erori');
$v = new Validator(array('email' => 'ion@exemplu.ro', 'telefon' => '0722 123 456', 'nume' => 'Ion', 'cod' => '200585', 'tip' => 'card'));
$v->obligatoriu('nume', 'Numele')->email('email', 'E-mailul')->telefon('telefon', 'Telefonul')->codPostal('cod', 'Cod')->inLista('tip', 'Tip', array('ramburs', 'card'));
verifica($v->esteValid(), 'Datele valide sunt acceptate');
verifica(Validator::esteTelefonValid('+40722123456'), 'Telefon cu prefix +40 acceptat');
verifica(Validator::esteTelefonValid('0251-000-000'), 'Telefon fix cu cratime acceptat');
verifica(!Validator::esteTelefonValid('0822123456'), 'Prefix invalid 08 respins');
$v = new Validator(array('x' => array('injectie')));
$v->obligatoriu('x', 'X');
verifica(!$v->esteValid(), 'Un tablou trimis in locul unui text este respins');

grup('Procesator de plati (dummy)');
verifica(PlataDummy::luhn('4111111111111111'), 'Cardul de test Visa trece verificarea Luhn');
verifica(!PlataDummy::luhn('4111111111111112'), 'Cifra de control gresita este detectata');
$acum = strtotime('2026-11-16');
verifica(PlataDummy::expirareValida(11, 2026, $acum), 'Cardul care expira luna curenta este valid');
verifica(!PlataDummy::expirareValida(10, 26, $acum), 'Cardul expirat luna trecuta este respins');
$cerere = array('cheie_secreta' => 'k', 'suma' => 100, 'moneda' => 'RON', 'card_numar' => '4111 1111 1111 1111',
    'card_luna' => 12, 'card_an' => (int) date('Y') + 2, 'card_cvv' => '123', 'card_titular' => 'Ion Popescu');
$r = PlataDummy::autorizeaza($cerere, 'k');
verifica($r['aprobata'] && $r['cod'] === '00', 'Plata cu cardul de test este aprobata');
egal('1111', $r['ultimele4'], 'Se pastreaza doar ultimele 4 cifre');
$r = PlataDummy::autorizeaza(array_merge($cerere, array('card_numar' => '4000000000000002')), 'k');
verifica(!$r['aprobata'] && $r['cod'] === '51', 'Cardul de test fara fonduri este respins (cod 51)');
$r = PlataDummy::autorizeaza($cerere, 'alta-cheie');
verifica(!$r['aprobata'] && $r['cod'] === '401', 'Cheia de comerciant gresita este respinsa');
$r = PlataDummy::autorizeaza(array_merge($cerere, array('card_cvv' => '1')), 'k');
verifica(!$r['aprobata'], 'CVV invalid respins');

grup('Shim curier RapidCurier');
egal(19.99, CurierShim::calculeazaTarif('Cluj', 3, 100, false), 'Colet de 3 kg: tarif de baza');
egal(30.98, CurierShim::calculeazaTarif('Cluj', 10, 100, true), 'Colet de 10 kg cu ramburs: 19,99 + 5 x 1,20 + 4,99 = 30,98');
egal(15.99, CurierShim::calculeazaTarif('Dolj', 3, 100, false), 'Livrare locala in Dolj: reducere 20%');
egal(0.0, CurierShim::calculeazaTarif('Iasi', 50, 1600, true), 'Livrare gratuita peste 1.500 lei');
verifica(CurierShim::calculeazaTarif('Iasi', 800, 2000, false) > 150, 'Transportul paletizat (800 kg) nu este gratuit si include suprataxa');
$r = CurierShim::transport('/v1/awb', array('destinatar' => 'Ion', 'telefon' => '0722123456', 'adresa' => 'Str. X', 'judet' => 'Dolj', 'greutate_kg' => 2));
verifica($r['succes'] && preg_match('/^RC\d{14}$/', $r['awb']), 'Generarea AWB respecta formatul RC + 14 cifre');
$u = CurierShim::transport('/v1/urmarire', array('awb' => $r['awb']));
verifica($u['succes'] && count($u['istoric']) >= 1, 'AWB-ul generat poate fi urmarit');
verifica(!CurierShim::transport('/v1/urmarire', array('awb' => 'XYZ'))['succes'], 'AWB invalid respins');
verifica(!CurierShim::transport('/v1/inexistent', array())['succes'], 'Endpoint necunoscut respins');

grup('Functii ajutatoare');
egal('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'), 'Escaparea HTML neutralizeaza scripturile (XSS)');
egal('1.234,50 lei', pret(1234.5), 'Formatarea pretului in stil romanesc');
egal('masina-de-gaurit-750w', slugifica('Mașină de găurit 750W'), 'Generarea adresei (slug) elimina diacriticele');
egal('/index.php', adresa_locala('//site-rau.ro'), 'Redirectionarea catre alt domeniu este blocata');

/* ================================================================== */
/*  TESTE DE INTEGRARE (SQLite temporar)                              */
/* ================================================================== */

grup('Integrare - instalare baza de date temporara');
$iesire = array();
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(DIR_RADACINA . '/sql/instalare.php') . ' --fara-demo 2>&1', $iesire, $codIesire);
egal(0, $codIesire, 'Scriptul de instalare ruleaza fara erori pe SQLite');
egal(26, (int) Baza::valoare('SELECT COUNT(*) FROM produse'), 'Catalogul contine 26 de produse');
egal(6, (int) Baza::valoare('SELECT COUNT(*) FROM categorii'), 'Catalogul contine 6 categorii');
$admin = Autentificare::dupaEmail('admin@temelia.ro');
verifica($admin && $admin['rol'] === 'admin' && password_verify('Admin2026!', $admin['parola_hash']), 'Contul de administrator are parola stocata ca hash');
verifica(strpos($admin['parola_hash'], 'Admin2026!') === false, 'Parola nu este stocata in clar');

grup('Integrare - catalog si cautare');
$r = Produse::cauta(array('cautare' => 'ciment'));
egal(1, $r['total'], 'Cautarea dupa "ciment" gaseste un produs');
$r = Produse::cauta(array('cautare' => "' OR 1=1 --"));
egal(0, $r['total'], 'Tentativa de injectie SQL in cautare nu returneaza produse');
$r = Produse::cauta(array('cautare' => '%'));
egal(0, $r['total'], 'Caracterul "%" este tratat literal (escapare LIKE)');
$r = Produse::cauta(array('categorie' => 1, 'sortare' => 'pret_asc'));
verifica($r['total'] === 5 && (float) $r['produse'][0]['pret'] <= (float) $r['produse'][1]['pret'], 'Filtrarea pe categorie si sortarea crescatoare dupa pret');
$r = Produse::cauta(array('pret_min' => 100, 'pret_max' => 200));
$inInterval = true;
foreach ($r['produse'] as $p) {
    $inInterval = $inInterval && $p['pret'] >= 100 && $p['pret'] <= 200;
}
verifica($r['total'] > 0 && $inInterval, 'Filtrul de interval de pret');
$r = Produse::cauta(array('doar_reduceri' => true));
egal(6, $r['total'], 'Filtrul "doar reduceri" gaseste 6 produse');
$recomandari = Produse::recomandari(Produse::dupaId(1));
verifica(in_array('3', array_map('strval', array_column($recomandari, 'id')), true), 'Masina de gaurit recomanda setul de burghie (accesoriu compatibil)');

grup('Integrare - cos de cumparaturi');
$_SESSION = array();
egal('', Cos::adauga(1, 2), 'Adaugarea unui produs in cos');
Cos::adauga(1, 1);
egal(3, Cos::numarArticole(), 'Adaugarea repetata cumuleaza cantitatea');
verifica(Cos::adauga(1, 500) !== '', 'Cantitatea este limitata la stocul disponibil');
egal(25, Cos::numarArticole(), 'Cantitatea din cos = stocul (25)');
Cos::modifica(1, 2);
egal(2, Cos::numarArticole(), 'Modificarea cantitatii');
egal('Produsul nu este disponibil in stoc.', Cos::adauga(22, 1), 'Produsul cu stoc zero nu poate fi adaugat');
Cos::adauga(6, 10);
$linii = Cos::linii();
egal(924.80, Calculator::subtotal($linii), 'Subtotal cos: 2 x 289,90 + 10 x 34,50');
Cos::elimina(6);
egal(1, count(Cos::linii()), 'Eliminarea unui produs din cos');

grup('Integrare - comenzi si stoc');
$_SESSION['cupon'] = 'BINEVENIT10';
$linii = Cos::linii();
$rezumat = Cos::rezumat($linii, 'curier', 'Cluj', 'ramburs');
egal(57.98, $rezumat['reducere'], 'Cuponul BINEVENIT10 aplica 10% din 579,80');
$client = array('nume_client' => 'Test', 'telefon' => '0722123456', 'email' => 't@t.ro', 'adresa' => 'Str. Test 1',
    'oras' => 'Cluj-Napoca', 'judet' => 'Cluj', 'cod_postal' => '400001', 'metoda_livrare' => 'curier',
    'metoda_plata' => 'ramburs', 'observatii' => '');
$comanda = Comenzi::plaseaza($client, $linii, array('subtotal' => $rezumat['subtotal'], 'discount' => $rezumat['reducere'],
    'cost_livrare' => $rezumat['cost_livrare'], 'total' => $rezumat['total']), $rezumat['cupon'], null);
verifica((bool) preg_match('/^TM\d{4}\d{6}$/', $comanda['numar']), 'Numarul comenzii are formatul TM + an + 6 cifre');
egal(23, (int) Produse::dupaId(1)['stoc'], 'Stocul scade automat la plasarea comenzii (25 -> 23)');
egal(1, (int) Cupoane::dupaCod('BINEVENIT10')['utilizari'], 'Numarul de utilizari al cuponului creste');
egal('noua', Comenzi::dupaId($comanda['id'])['status'], 'Comanda noua are statusul "noua"');

$linieMare = array(array('produs' => Produse::dupaId(2), 'pret' => 459, 'cantitate' => 999, 'total' => 1));
arunca(function () use ($client, $linieMare) {
    Comenzi::plaseaza($client, $linieMare, array('subtotal' => 1, 'discount' => 0, 'cost_livrare' => 0, 'total' => 1), null, null);
}, 'Comanda care depaseste stocul este respinsa');
egal(14, (int) Produse::dupaId(2)['stoc'], 'Dupa comanda respinsa stocul ramane neschimbat (ROLLBACK)');

Comenzi::schimbaStatus($comanda['id'], 'confirmata');
Comenzi::schimbaStatus($comanda['id'], 'expediata');
$expediata = Comenzi::dupaId($comanda['id']);
verifica($expediata['status'] === 'expediata' && strpos((string) $expediata['awb'], 'RC') === 0, 'La expediere se genereaza AWB-ul RapidCurier');
Comenzi::schimbaStatus($comanda['id'], 'anulata', 'test');
egal(25, (int) Produse::dupaId(1)['stoc'], 'La anulare stocul este restituit (23 -> 25)');
arunca(function () use ($comanda) {
    Comenzi::schimbaStatus($comanda['id'], 'noua');
}, 'O comanda anulata nu mai poate fi reactivata');
egal(4, count(Comenzi::istoric($comanda['id'])), 'Istoricul retine toate cele 4 statusuri');

grup('Integrare - statistici');
$s = Comenzi::statistici();
egal(0.0, (float) $s['venit_total'], 'Comenzile anulate sunt excluse din cifra de afaceri');
egal(14, count($s['zilnic']), 'Graficul zilnic contine 14 zile');

/* ------------------------------------------------------------------ */
@unlink($fisierTest);
$r = $GLOBALS['rezultate'];
fwrite(STDOUT, sprintf("\nREZULTAT: %d teste trecute, %d esuate.\n", $r['trecute'], $r['esuate']));
exit($r['esuate'] > 0 ? 1 : 0);
