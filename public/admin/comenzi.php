<?php
/**
 * Lista comenzilor primite, cu filtrare dupa status si cautare.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

$status = param('status', '', $_GET);
$cautare = param('q', '', $_GET);

$titlu = 'Comenzi';
$sectiune = 'comenzi';
require DIR_SRC . '/sabloane/admin_antet.php';
$comenzi = Comenzi::toate($status, $cautare);
?>
<h1>Comenzi primite</h1>
<form method="get" action="<?php echo e(url('admin/comenzi.php')); ?>" class="filtre">
    <label>Status: <select name="status"><option value="">- toate -</option><?php echo html_optiuni(statusuri_comanda(), $status); ?></select></label>
    <label>Cautare: <input type="text" name="q" value="<?php echo e($cautare); ?>" class="text mediu" title="numar comanda, nume client sau e-mail" /></label>
    <input type="submit" class="buton" value="Filtreaza" />
</form>

<?php if (empty($comenzi)): ?>
    <div class="mesaj mesaj-info">Nu exista comenzi conform filtrului selectat.</div>
<?php else: ?>
<div class="tabel-derulant">
<table class="date">
    <thead><tr><th>Numar</th><th>Data</th><th>Client</th><th>Livrare</th><th>Plata</th><th class="numar">Total</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($comenzi as $i => $c): ?>
    <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
        <td><strong><?php echo e($c['numar']); ?></strong></td>
        <td><?php echo e(data_ro($c['creat_la'])); ?></td>
        <td><?php echo e($c['nume_client']); ?><br /><small><?php echo e($c['telefon']); ?></small></td>
        <td><?php echo $c['metoda_livrare'] === 'curier' ? 'Curier, jud. ' . e($c['judet']) : 'Ridicare'; ?></td>
        <td><?php echo $c['metoda_plata'] === 'card' ? 'Card' : 'Ramburs'; ?><br /><small><?php echo e(eticheta_metoda($c['stare_plata'])); ?></small></td>
        <td class="numar"><?php echo e(pret($c['total'])); ?></td>
        <td><?php echo html_status($c['status']); ?></td>
        <td><a href="<?php echo e(url('admin/comanda.php', array('id' => $c['id']))); ?>" class="buton">Detalii</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
