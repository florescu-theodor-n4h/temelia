<?php
/**
 * Urmarirea unei comenzi (numar + e-mail, fara cont) si a coletului
 * dupa AWB, prin endpoint-ul /v1/urmarire al shim-ului RapidCurier.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$numar = strtoupper(param('numar', '', $_GET));
$email = strtolower(param('email', '', $_GET));
$awb = strtoupper(param('awb', '', $_GET));
$comanda = null;
$eroare = '';

if ($numar !== '') {
    $gasita = Comenzi::dupaNumar($numar);
    // Comparatia adresei de e-mail impiedica enumerarea comenzilor altor clienti
    if ($gasita && hash_equals(strtolower($gasita['email']), $email)) {
        $comanda = $gasita;
        if ($comanda['awb']) {
            $awb = $comanda['awb'];
        }
    } else {
        $eroare = 'Nu a fost gasita nicio comanda cu acest numar si aceasta adresa de e-mail.';
    }
}

$urmarire = $awb !== '' ? CurierShim::transport('/v1/urmarire', array('awb' => $awb)) : null;

$titlu = 'Urmarire comanda';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Urmarire comanda si colet</h1>
<?php if ($eroare): ?><div class="mesaj mesaj-eroare"><?php echo e($eroare); ?></div><?php endif; ?>

<form method="get" action="<?php echo e(url('urmarire.php')); ?>" class="filtre">
    <label>Numar comanda: <input type="text" name="numar" value="<?php echo e($numar); ?>" class="text mediu" maxlength="20" /></label>
    <label>E-mail: <input type="text" name="email" value="<?php echo e($email); ?>" class="text mediu" maxlength="160" /></label>
    <input type="submit" class="buton buton-principal" value="Cauta comanda" />
    <br />sau direct dupa AWB:
    <label><input type="text" name="awb" value="<?php echo e($comanda ? '' : $awb); ?>" class="text mediu" maxlength="20" /></label>
    <input type="submit" class="buton" value="Urmareste coletul" />
</form>

<?php if ($comanda): ?>
    <h2>Comanda <?php echo e($comanda['numar']); ?></h2>
    <p>Status curent: <?php echo html_status($comanda['status']); ?> &middot; plasata la <?php echo e(data_ro($comanda['creat_la'])); ?>
       &middot; total <?php echo e(pret($comanda['total'])); ?></p>
    <table class="date">
        <?php foreach (Comenzi::istoric($comanda['id']) as $h): ?>
        <tr><td class="numar"><?php echo e(data_ro($h['creat_la'])); ?></td><td><?php echo html_status($h['status']); ?></td><td><?php echo e($h['nota']); ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if ($urmarire): ?>
    <div class="caseta">
        <div class="titlu">RapidCurier - AWB <?php echo e($awb); ?></div>
        <div class="corp">
        <?php if (empty($urmarire['succes'])): ?>
            <p><?php echo e($urmarire['mesaj']); ?></p>
        <?php else: ?>
            <p><strong><?php echo $urmarire['livrat'] ? 'Coletul a fost livrat.' : 'Coletul este in curs de livrare.'; ?></strong></p>
            <table class="date">
                <?php foreach ($urmarire['istoric'] as $ev): ?>
                <tr><td class="numar"><?php echo e(data_ro($ev['data'])); ?></td><td><?php echo e($ev['eveniment']); ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/subsol.php';
