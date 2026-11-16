<?php
/**
 * Istoricul comenzilor clientului autentificat, cu statusul fiecareia.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

Autentificare::cereAutentificare();
$utilizator = Autentificare::utilizator();
$comenzi = Comenzi::aleUtilizatorului($utilizator['id']);

$titlu = 'Comenzile mele';
$cale = array(array('Contul meu', ''), array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Comenzile mele</h1>
<p>Cont: <strong><?php echo e($utilizator['nume']); ?></strong> (<?php echo e($utilizator['email']); ?>) &middot; client din <?php echo e(data_ro($utilizator['creat_la'], false)); ?></p>

<?php if (empty($comenzi)): ?>
    <div class="mesaj mesaj-info">Nu ati plasat inca nicio comanda. <a href="<?php echo e(url('catalog.php')); ?>">Descoperiti catalogul</a>.</div>
<?php else: ?>
<div class="tabel-derulant">
<table class="date">
    <thead><tr><th>Numar</th><th>Data</th><th>Livrare</th><th>Plata</th><th class="numar">Total</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($comenzi as $i => $c): ?>
        <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
            <td><strong><?php echo e($c['numar']); ?></strong></td>
            <td><?php echo e(data_ro($c['creat_la'])); ?></td>
            <td><?php echo $c['metoda_livrare'] === 'curier' ? 'Curier' : 'Ridicare'; ?><?php if ($c['awb']): ?><br /><small>AWB <?php echo e($c['awb']); ?></small><?php endif; ?></td>
            <td><?php echo e(eticheta_metoda($c['stare_plata'])); ?></td>
            <td class="numar"><?php echo e(pret($c['total'])); ?></td>
            <td><?php echo html_status($c['status']); ?></td>
            <td><a href="<?php echo e(url('confirmare.php', array('numar' => $c['numar']))); ?>">Detalii</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/subsol.php';
