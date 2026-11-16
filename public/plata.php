<?php
/**
 * Pagina de plata cu cardul (A1), conectata la procesatorul de plati de
 * TEST (PlataDummy). Datele cardului sunt transmise direct procesatorului
 * si nu sunt salvate: se pastreaza doar ultimele 4 cifre si referinta.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$comanda = Comenzi::dupaNumar(param('numar'));
if (!$comanda || !poate_vedea_comanda($comanda)) {
    http_response_code(404);
    exit('Comanda nu a fost gasita.');
}
if ($comanda['metoda_plata'] !== 'card' || $comanda['stare_plata'] === 'platita' || $comanda['status'] === 'anulata') {
    redirectioneaza('confirmare.php', array('numar' => $comanda['numar']));
}

$eroare = '';
if (este_post()) {
    csrf_verifica();
    $raspuns = PlataDummy::autorizeaza(array(
        'cheie_secreta'     => config('plata_cheie_secreta'),
        'suma'              => $comanda['total'],
        'moneda'            => 'RON',
        'referinta_comanda' => $comanda['numar'],
        'card_numar'        => param('card_numar', '', $_POST),
        'card_luna'         => param('card_luna', '', $_POST),
        'card_an'           => param('card_an', '', $_POST),
        'card_cvv'          => param('card_cvv', '', $_POST),
        'card_titular'      => param('card_titular', '', $_POST),
    ), config('plata_cheie_secreta'));

    Comenzi::inregistreazaPlata($comanda['id'], $raspuns, $comanda['total']);
    if ($raspuns['aprobata']) {
        flash('succes', 'Plata a fost aprobata. Referinta tranzactiei: ' . $raspuns['referinta'] . '.');
        redirectioneaza('confirmare.php', array('numar' => $comanda['numar']));
    }
    $eroare = 'Plata a fost respinsa: ' . $raspuns['mesaj'] . ' Puteti incerca din nou cu alt card.';
}

$titlu = 'Plata cu cardul';
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Plata securizata cu cardul</h1>
<?php if ($eroare): ?><div class="mesaj mesaj-eroare"><?php echo e($eroare); ?></div><?php endif; ?>

<div class="mesaj mesaj-info">
    MEDIU DE TEST - nu introduceti date reale. Carduri de test:
    <strong>4111 1111 1111 1111</strong> (aprobat), <strong>4000 0000 0000 0002</strong> (fonduri insuficiente),
    <strong>4000 0000 0000 0069</strong> (card blocat). Orice data de expirare viitoare si orice CVV de 3 cifre.
</div>

<div class="caseta">
    <div class="titlu">Comanda <?php echo e($comanda['numar']); ?> - suma de plata: <?php echo e(pret($comanda['total'])); ?></div>
    <div class="corp">
        <form method="post" action="<?php echo e(url('plata.php', array('numar' => $comanda['numar']))); ?>" autocomplete="off">
            <?php echo csrf_camp(); ?>
            <table class="formular">
                <tr><th>Numar card</th><td><input type="text" name="card_numar" class="text mediu" maxlength="23" /></td></tr>
                <tr><th>Titular card</th><td><input type="text" name="card_titular" class="text mediu" maxlength="80" value="<?php echo e($comanda['nume_client']); ?>" /></td></tr>
                <tr><th>Data expirarii</th><td>
                    <select name="card_luna"><?php for ($l = 1; $l <= 12; $l++): ?><option value="<?php echo $l; ?>"><?php echo sprintf('%02d', $l); ?></option><?php endfor; ?></select> /
                    <select name="card_an"><?php for ($a = (int) date('Y'); $a <= (int) date('Y') + 10; $a++): ?><option value="<?php echo $a; ?>"><?php echo $a; ?></option><?php endfor; ?></select>
                </td></tr>
                <tr><th>CVV</th><td><input type="password" name="card_cvv" class="text scurt" maxlength="4" /></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Plateste <?php echo e(pret($comanda['total'])); ?>" /></td></tr>
            </table>
        </form>
        <p class="mic">Daca nu doriti sa platiti acum, comanda ramane inregistrata. O puteti plati ulterior din pagina
            <a href="<?php echo e(url('confirmare.php', array('numar' => $comanda['numar']))); ?>">detaliilor comenzii</a>.</p>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
