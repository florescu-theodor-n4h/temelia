<?php
/**
 * Detaliile unei comenzi si schimbarea statusului. La trecerea in
 * statusul "Expediata" se genereaza automat AWB-ul RapidCurier, iar la
 * anulare stocul produselor este restituit.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';
Autentificare::cereAdmin();

$comanda = Comenzi::dupaId(param_int('id'));
if (!$comanda) {
    flash('eroare', 'Comanda nu exista.');
    redirectioneaza('admin/comenzi.php');
}

if (este_post()) {
    csrf_verifica();
    $v = new Validator($_POST);
    $v->inLista('status', 'Statusul', array_keys(statusuri_comanda()))->lungime('nota', 'Nota', 0, 200);
    if ($v->esteValid()) {
        try {
            Comenzi::schimbaStatus($comanda['id'], $v->valoare('status'), $v->valoare('nota'));
            flash('succes', 'Statusul comenzii a fost actualizat: ' . eticheta_status($v->valoare('status')) . '.');
        } catch (RuntimeException $ex) {
            flash('eroare', $ex->getMessage());
        }
    } else {
        flash('eroare', implode(' ', $v->erori()));
    }
    redirectioneaza('admin/comanda.php', array('id' => $comanda['id']));
}

$tranzactii = Comenzi::tranzactii($comanda['id']);
$titlu = 'Comanda ' . $comanda['numar'];
$sectiune = 'comenzi';
require DIR_SRC . '/sabloane/admin_antet.php';
?>
<h1>Comanda <?php echo e($comanda['numar']); ?></h1>
<div class="unelte"><a href="<?php echo e(url('admin/comenzi.php')); ?>" class="buton">&laquo; Lista comenzi</a></div>

<div class="caseta">
    <div class="titlu">Actualizare status</div>
    <div class="corp">
        <?php if ($comanda['status'] === 'anulata'): ?>
            <p>Comanda este anulata si nu mai poate fi modificata.</p>
        <?php else: ?>
        <form method="post" action="<?php echo e(url('admin/comanda.php', array('id' => $comanda['id']))); ?>">
            <?php echo csrf_camp(); ?>
            <label>Status nou: <select name="status"><?php echo html_optiuni(statusuri_comanda(), $comanda['status']); ?></select></label>
            <label>Nota (optional): <input type="text" name="nota" class="text lung" maxlength="200" /></label>
            <input type="submit" class="buton buton-principal" value="Actualizeaza" />
        </form>
        <p class="mic">Expediata = se genereaza AWB prin API-ul RapidCurier (pentru livrarea prin curier).
            Anulata = stocul este restituit, iar plata cu cardul este marcata pentru rambursare.
            Livrata (ramburs) = plata este marcata ca incasata.</p>
        <?php endif; ?>
    </div>
</div>

<?php require DIR_SRC . '/sabloane/detalii_comanda.php'; ?>

<?php if ($tranzactii): ?>
<h3>Tranzactii de plata</h3>
<table class="date">
    <tr><th>Data</th><th>Referinta</th><th>Card</th><th class="numar">Suma</th><th>Rezultat</th></tr>
    <?php foreach ($tranzactii as $t): ?>
    <tr><td><?php echo e(data_ro($t['creat_la'])); ?></td><td><?php echo e($t['referinta']); ?></td>
        <td><?php echo $t['card_ultimele4'] ? '**** ' . e($t['card_ultimele4']) : '-'; ?></td>
        <td class="numar"><?php echo e(pret($t['suma'])); ?></td>
        <td><?php echo $t['stare'] === 'aprobata' ? '<span class="stoc-da">aprobata</span>' : '<span class="stoc-nu">respinsa</span>'; ?> <small><?php echo e($t['mesaj']); ?></small></td></tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
