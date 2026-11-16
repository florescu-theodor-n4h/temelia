<?php
/**
 * Fragment reutilizabil: detaliile unei comenzi (date client, produse,
 * totaluri, istoric status). Folosit de pagina de confirmare, de
 * istoricul clientului si de panoul de administrare.
 *
 * Variabile asteptate: $comanda (array).
 *
 * @package Temelia
 */
$liniiComanda = Comenzi::linii($comanda['id']);
$istoricComanda = Comenzi::istoric($comanda['id']);
?>
<table class="date">
    <tr><th>Numar comanda</th><td><strong><?php echo e($comanda['numar']); ?></strong></td>
        <th>Data</th><td><?php echo e(data_ro($comanda['creat_la'])); ?></td></tr>
    <tr><th>Status</th><td><?php echo html_status($comanda['status']); ?></td>
        <th>Plata</th><td><?php echo e(eticheta_metoda($comanda['metoda_plata'])); ?> - <strong><?php echo e(eticheta_metoda($comanda['stare_plata'])); ?></strong></td></tr>
    <tr><th>Client</th><td><?php echo e($comanda['nume_client']); ?><br /><?php echo e($comanda['telefon']); ?><br /><?php echo e($comanda['email']); ?></td>
        <th>Livrare</th><td><?php echo e(eticheta_metoda($comanda['metoda_livrare'])); ?><br />
            <?php echo e($comanda['adresa']); ?><?php if ($comanda['metoda_livrare'] === 'curier'): ?>, <?php echo e($comanda['oras']); ?>, jud. <?php echo e($comanda['judet']); ?> <?php echo e($comanda['cod_postal']); ?><?php endif; ?>
            <?php if ($comanda['awb']): ?><br />AWB: <a href="<?php echo e(url('urmarire.php', array('awb' => $comanda['awb']))); ?>"><?php echo e($comanda['awb']); ?></a><?php endif; ?></td></tr>
    <?php if ($comanda['observatii'] !== ''): ?>
    <tr><th>Observatii</th><td colspan="3"><?php echo nl2br(e($comanda['observatii'])); ?></td></tr>
    <?php endif; ?>
</table>

<div class="tabel-derulant">
<table class="date">
    <thead><tr><th>Cod</th><th>Produs</th><th class="numar">Pret unitar</th><th class="numar">Cantitate</th><th class="numar">Total</th></tr></thead>
    <tbody>
    <?php foreach ($liniiComanda as $i => $l): ?>
        <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>><td><?php echo e($l['cod_sku']); ?></td><td><?php echo e($l['nume_produs']); ?></td>
            <td class="numar"><?php echo e(pret($l['pret_unitar'])); ?></td><td class="numar"><?php echo (int) $l['cantitate']; ?></td>
            <td class="numar"><?php echo e(pret($l['total_linie'])); ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="4" class="numar">Subtotal</td><td class="numar"><?php echo e(pret($comanda['subtotal'])); ?></td></tr>
        <?php if ((float) $comanda['discount'] > 0): ?>
        <tr><td colspan="4" class="numar">Reducere <?php echo e($comanda['cod_cupon']); ?></td><td class="numar">- <?php echo e(pret($comanda['discount'])); ?></td></tr>
        <?php endif; ?>
        <tr><td colspan="4" class="numar">Livrare</td><td class="numar"><?php echo e(pret($comanda['cost_livrare'])); ?></td></tr>
        <tr><td colspan="4" class="numar">TOTAL (din care TVA <?php echo (int) config('cota_tva'); ?>%: <?php echo e(pret(Calculator::tvaInclus($comanda['total'], config('cota_tva')))); ?>)</td>
            <td class="numar pret"><?php echo e(pret($comanda['total'])); ?></td></tr>
    </tfoot>
</table>
</div>

<h3>Istoricul comenzii</h3>
<table class="date">
    <?php foreach ($istoricComanda as $h): ?>
    <tr><td class="numar"><?php echo e(data_ro($h['creat_la'])); ?></td><td><?php echo html_status($h['status']); ?></td><td><?php echo e($h['nota']); ?></td></tr>
    <?php endforeach; ?>
</table>
