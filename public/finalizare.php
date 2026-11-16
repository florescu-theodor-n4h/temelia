<?php
/**
 * Finalizarea comenzii (checkout): datele clientului, adresa de livrare,
 * metoda de livrare si de plata, acceptarea termenilor, validarea pe
 * server si plasarea comenzii.
 *
 * Dupa plasare:
 *   - plata ramburs -> pagina de confirmare;
 *   - plata cu cardul -> pagina procesatorului de plati de test.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$linii = Cos::linii();
if (empty($linii)) {
    flash('info', 'Cosul este gol. Adaugati produse inainte de a finaliza comanda.');
    redirectioneaza('cos.php');
}

$utilizator = Autentificare::utilizator();
$erori = array();

// Valorile implicite ale formularului (completate din cont, daca exista)
$date = array(
    'nume_client'    => $utilizator ? $utilizator['nume'] : '',
    'telefon'        => $utilizator ? (string) $utilizator['telefon'] : '',
    'email'          => $utilizator ? $utilizator['email'] : '',
    'adresa'         => '',
    'oras'           => '',
    'judet'          => '',
    'cod_postal'     => '',
    'metoda_livrare' => 'curier',
    'metoda_plata'   => 'ramburs',
    'observatii'     => '',
);

if (este_post()) {
    csrf_verifica();
    foreach ($date as $camp => $implicit) {
        $date[$camp] = param($camp, '', $_POST);
    }

    $v = new Validator($_POST);
    $v->obligatoriu('nume_client', 'Numele complet')->lungime('nume_client', 'Numele complet', 3, 120)
      ->obligatoriu('telefon', 'Numarul de telefon')->telefon('telefon', 'Numarul de telefon')
      ->obligatoriu('email', 'Adresa de e-mail')->email('email', 'Adresa de e-mail')
      ->inLista('metoda_livrare', 'Metoda de livrare', array('curier', 'ridicare'))
      ->inLista('metoda_plata', 'Metoda de plata', array('ramburs', 'card'))
      ->lungime('observatii', 'Observatiile', 0, 500)
      ->bifat('accept_termeni', 'Trebuie sa acceptati Termenii si conditiile si Politica de confidentialitate.');

    // Adresa este obligatorie doar pentru livrarea prin curier
    if ($date['metoda_livrare'] === 'curier') {
        $v->obligatoriu('adresa', 'Adresa de livrare')->lungime('adresa', 'Adresa de livrare', 5, 255)
          ->obligatoriu('oras', 'Localitatea')->lungime('oras', 'Localitatea', 2, 80)
          ->inLista('judet', 'Judetul', judete())
          ->obligatoriu('cod_postal', 'Codul postal')->codPostal('cod_postal', 'Codul postal');
    } else {
        $date['adresa'] = 'Ridicare din depozit: ' . config('firma.adresa');
        $date['oras'] = 'Craiova';
        $date['judet'] = 'Dolj';
        $date['cod_postal'] = '';
    }

    // Butonul "Recalculeaza transportul" doar actualizeaza rezumatul, fara a plasa comanda
    $doarRecalculare = param('recalculeaza', '', $_POST) !== '';

    if (!Cos::stocSuficient($linii)) {
        $v->adaugaEroare('stoc', 'Unele produse din cos nu mai au stoc suficient. Va rugam sa verificati cosul.');
    }

    if (!$doarRecalculare && $v->esteValid()) {
        $rezumat = Cos::rezumat($linii, $date['metoda_livrare'], $date['judet'], $date['metoda_plata']);
        try {
            $comanda = Comenzi::plaseaza($date, $linii, array(
                'subtotal'     => $rezumat['subtotal'],
                'discount'     => $rezumat['reducere'],
                'cost_livrare' => $rezumat['cost_livrare'],
                'total'        => $rezumat['total'],
            ), $rezumat['cupon'], $utilizator ? $utilizator['id'] : null);

            Cos::goleste();
            // Comenzile plasate in aceasta sesiune pot fi vizualizate si fara cont
            $_SESSION['comenzi_sesiune'][] = $comanda['numar'];
            jurnal('Comanda noua ' . $comanda['numar'] . ' total ' . $rezumat['total']);

            if ($date['metoda_plata'] === 'card') {
                redirectioneaza('plata.php', array('numar' => $comanda['numar']));
            }
            flash('succes', 'Comanda a fost inregistrata cu succes.');
            redirectioneaza('confirmare.php', array('numar' => $comanda['numar']));
        } catch (RuntimeException $ex) {
            $v->adaugaEroare('stoc', $ex->getMessage());
        }
    }
    $erori = $doarRecalculare ? array() : $v->erori();
}

$rezumat = Cos::rezumat($linii, $date['metoda_livrare'], $date['judet'], $date['metoda_plata']);
$titlu = 'Finalizare comanda';
$cale = array(array('Cos', url('cos.php')), array($titlu, ''));

/**
 * Clasa CSS pentru campurile cu eroare.
 *
 * @param array  $erori
 * @param string $camp
 * @return string
 */
function clasa_camp(array $erori, $camp)
{
    return isset($erori[$camp]) ? ' camp-eroare' : '';
}

require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Finalizare comanda</h1>
<?php echo html_erori($erori); ?>

<?php if (!$utilizator): ?>
<div class="mesaj mesaj-info">Aveti deja cont? <a href="<?php echo e(url('cont/autentificare.php', array('inapoi' => url('finalizare.php')))); ?>">Autentificati-va</a> pentru a urmari comanda in istoricul contului. Puteti comanda si fara cont.</div>
<?php endif; ?>

<form method="post" action="<?php echo e(url('finalizare.php')); ?>">
<?php echo csrf_camp(); ?>
<div id="coloana-principala">
    <fieldset>
        <legend>1. Date de contact</legend>
        <table class="formular">
            <tr><th>Nume complet <span class="obligatoriu">*</span></th>
                <td><input type="text" name="nume_client" value="<?php echo e($date['nume_client']); ?>" class="text lung<?php echo clasa_camp($erori, 'nume_client'); ?>" maxlength="120" /><?php echo html_eroare_camp($erori, 'nume_client'); ?></td></tr>
            <tr><th>Telefon <span class="obligatoriu">*</span></th>
                <td><input type="text" name="telefon" value="<?php echo e($date['telefon']); ?>" class="text mediu<?php echo clasa_camp($erori, 'telefon'); ?>" maxlength="20" /><?php echo html_eroare_camp($erori, 'telefon'); ?></td></tr>
            <tr><th>E-mail <span class="obligatoriu">*</span></th>
                <td><input type="text" name="email" value="<?php echo e($date['email']); ?>" class="text lung<?php echo clasa_camp($erori, 'email'); ?>" maxlength="160" /><?php echo html_eroare_camp($erori, 'email'); ?></td></tr>
        </table>
    </fieldset>

    <fieldset>
        <legend>2. Metoda de livrare</legend>
        <label><input type="radio" name="metoda_livrare" value="curier"<?php echo $date['metoda_livrare'] === 'curier' ? ' checked="checked"' : ''; ?> />
            <strong>Livrare prin curier RapidCurier</strong> - tarif calculat in functie de greutate (<?php echo e(number_format(Cos::greutate($linii), 2, ',', '.')); ?> kg) si judet; gratuit peste 1.500 lei.</label><br />
        <label><input type="radio" name="metoda_livrare" value="ridicare"<?php echo $date['metoda_livrare'] === 'ridicare' ? ' checked="checked"' : ''; ?> />
            <strong>Ridicare personala</strong> din depozitul Craiova - gratuit (<?php echo e(config('firma.program')); ?>).</label>
        <?php echo html_eroare_camp($erori, 'metoda_livrare'); ?>
    </fieldset>

    <fieldset>
        <legend>3. Adresa de livrare <small>(necesara doar pentru livrarea prin curier)</small></legend>
        <table class="formular">
            <tr><th>Strada, numar, bloc, ap. <span class="obligatoriu">*</span></th>
                <td><input type="text" name="adresa" value="<?php echo e(strpos($date['adresa'], 'Ridicare') === 0 ? '' : $date['adresa']); ?>" class="text lung<?php echo clasa_camp($erori, 'adresa'); ?>" maxlength="255" /><?php echo html_eroare_camp($erori, 'adresa'); ?></td></tr>
            <tr><th>Localitate <span class="obligatoriu">*</span></th>
                <td><input type="text" name="oras" value="<?php echo e($date['oras']); ?>" class="text mediu<?php echo clasa_camp($erori, 'oras'); ?>" maxlength="80" /><?php echo html_eroare_camp($erori, 'oras'); ?></td></tr>
            <tr><th>Judet <span class="obligatoriu">*</span></th>
                <td><select name="judet" class="mediu<?php echo clasa_camp($erori, 'judet'); ?>">
                    <option value="">- selectati -</option>
                    <?php echo html_optiuni(array_combine(judete(), judete()), $date['judet']); ?>
                </select><?php echo html_eroare_camp($erori, 'judet'); ?></td></tr>
            <tr><th>Cod postal <span class="obligatoriu">*</span></th>
                <td><input type="text" name="cod_postal" value="<?php echo e($date['cod_postal']); ?>" class="text scurt<?php echo clasa_camp($erori, 'cod_postal'); ?>" maxlength="6" /><?php echo html_eroare_camp($erori, 'cod_postal'); ?></td></tr>
        </table>
    </fieldset>

    <fieldset>
        <legend>4. Metoda de plata</legend>
        <label><input type="radio" name="metoda_plata" value="ramburs"<?php echo $date['metoda_plata'] === 'ramburs' ? ' checked="checked"' : ''; ?> />
            <strong>Ramburs</strong> - plata numerar la livrare (comision curier <?php echo e(pret(CurierShim::COMISION_RAMBURS)); ?>) sau la ridicare.</label><br />
        <label><input type="radio" name="metoda_plata" value="card"<?php echo $date['metoda_plata'] === 'card' ? ' checked="checked"' : ''; ?> />
            <strong>Card bancar online</strong> - Visa / Mastercard, prin procesatorul securizat (mediu de test).</label>
        <?php echo html_eroare_camp($erori, 'metoda_plata'); ?>
    </fieldset>

    <fieldset>
        <legend>5. Observatii si confirmare</legend>
        <textarea name="observatii" rows="3" cols="60" class="lung" title="Ex. interval orar preferat, etaj, acces camion"><?php echo e($date['observatii']); ?></textarea>
        <p><label><input type="checkbox" name="accept_termeni" value="1"<?php echo param('accept_termeni', '', $_POST) ? ' checked="checked"' : ''; ?> />
            Am citit si accept <a href="<?php echo e(url('termeni.php')); ?>">Termenii si conditiile</a> si
            <a href="<?php echo e(url('confidentialitate.php')); ?>">Politica de confidentialitate</a>. <span class="obligatoriu">*</span></label>
            <?php echo html_eroare_camp($erori, 'accept_termeni'); ?></p>
    </fieldset>
</div>

<div class="caseta">
    <div class="titlu">Rezumatul comenzii</div>
    <div class="corp">
        <table class="date">
            <tr><th>Produs</th><th class="numar">Cant.</th><th class="numar">Total</th></tr>
            <?php foreach ($linii as $l): ?>
            <tr><td><?php echo e($l['produs']['nume']); ?></td><td class="numar"><?php echo (int) $l['cantitate']; ?></td><td class="numar"><?php echo e(pret($l['total'])); ?></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="2">Subtotal</td><td class="numar"><?php echo e(pret($rezumat['subtotal'])); ?></td></tr>
            <?php if ($rezumat['reducere'] > 0): ?>
            <tr><td colspan="2">Reducere <?php echo e($rezumat['cupon']['cod']); ?></td><td class="numar">- <?php echo e(pret($rezumat['reducere'])); ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="2">Livrare <?php echo $rezumat['estimare'] ? '<small>(estimare)</small>' : ''; ?>
                <?php if ($rezumat['zile_livrare']): ?><small>- termen <?php echo (int) $rezumat['zile_livrare']; ?> zile lucratoare</small><?php endif; ?></td>
                <td class="numar"><?php echo $rezumat['cost_livrare'] > 0 ? e(pret($rezumat['cost_livrare'])) : 'GRATUIT'; ?></td></tr>
            <tfoot><tr><td colspan="2">TOTAL DE PLATA <small>(TVA inclus: <?php echo e(pret($rezumat['tva'])); ?>)</small></td><td class="numar pret"><?php echo e(pret($rezumat['total'])); ?></td></tr></tfoot>
        </table>
        <p class="mic">Costul final al livrarii este recalculat la trimiterea formularului, in functie de judet si de metoda de plata aleasa.</p>
        <input type="submit" name="recalculeaza" value="Recalculeaza transportul" class="buton" />
        <input type="submit" value="Trimite comanda" class="buton buton-principal" />
    </div>
</div>
</form>
<?php require DIR_SRC . '/sabloane/subsol.php';
