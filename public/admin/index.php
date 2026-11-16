<?php
/**
 * Tabloul de bord al vanzarilor (A15): cifra de afaceri, numarul de
 * comenzi, valoarea medie, cele mai vandute produse, vanzarile pe
 * categorii, evolutia zilnica (grafic din tabele, fara JavaScript) si
 * produsele cu stoc redus.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

$titlu = 'Tablou de bord';
$sectiune = 'tablou';
require DIR_SRC . '/sabloane/admin_antet.php';

$s = Comenzi::statistici();
$maximZilnic = max(1, max(array_column($s['zilnic'], 'valoare')));
$maximCategorie = !empty($s['pe_categorii']) ? max(1, max(array_column($s['pe_categorii'], 'valoare'))) : 1;
?>
<h1>Tablou de bord - vanzari</h1>

<div class="indicatori">
    <div class="indicator"><div class="interior">Cifra de afaceri<span class="valoare"><?php echo e(pret($s['venit_total'])); ?></span><small>fara comenzile anulate</small></div></div>
    <div class="indicator"><div class="interior">Comenzi totale<span class="valoare"><?php echo (int) $s['numar_comenzi']; ?></span><small><?php echo (int) $s['comenzi_noi']; ?> noi, in asteptare</small></div></div>
    <div class="indicator"><div class="interior">Valoare medie comanda<span class="valoare"><?php echo e(pret($s['valoare_medie'])); ?></span><small>cos mediu</small></div></div>
    <div class="indicator"><div class="interior">Clienti inregistrati<span class="valoare"><?php echo (int) $s['numar_clienti']; ?></span><small>conturi active</small></div></div>
</div>

<div class="caseta">
    <div class="titlu">Vanzari in ultimele 14 zile (lei)</div>
    <div class="corp">
        <table class="grafic">
            <tr>
            <?php foreach ($s['zilnic'] as $z): $inaltime = (int) round($z['valoare'] / $maximZilnic * 130); ?>
                <td title="<?php echo e(data_ro($z['zi'], false) . ': ' . pret($z['valoare']) . ', ' . $z['numar'] . ' comenzi'); ?>">
                    <?php if ($z['valoare'] > 0): ?><small><?php echo (int) round($z['valoare']); ?></small><?php endif; ?>
                    <img src="<?php echo e(url('img/bara.svg')); ?>" alt="" class="bara-img" width="18" height="<?php echo max(1, $inaltime); ?>" /><br />
                    <?php echo e(date('d.m', strtotime($z['zi']))); ?>
                </td>
            <?php endforeach; ?>
            </tr>
        </table>
    </div>
</div>

<div id="coloana-principala">
    <div class="caseta">
        <div class="titlu">Cele mai vandute produse</div>
        <div class="corp">
            <?php if (empty($s['top_produse'])): ?><p>Nu exista inca vanzari.</p><?php else: ?>
            <table class="date">
                <tr><th>#</th><th>Produs</th><th class="numar">Bucati</th><th class="numar">Valoare</th></tr>
                <?php foreach ($s['top_produse'] as $i => $p): ?>
                <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>><td><?php echo $i + 1; ?></td><td><?php echo e($p['nume_produs']); ?> <small>(<?php echo e($p['cod_sku']); ?>)</small></td>
                    <td class="numar"><?php echo (int) $p['bucati']; ?></td><td class="numar"><?php echo e(pret($p['valoare'])); ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="caseta">
        <div class="titlu">Vanzari pe raioane</div>
        <div class="corp">
            <table class="date">
                <?php foreach ($s['pe_categorii'] as $c): ?>
                <tr><td><?php echo e($c['nume']); ?></td>
                    <td><img src="<?php echo e(url('img/bara-orizontala.svg')); ?>" alt="" width="<?php echo max(1, (int) round($c['valoare'] / $maximCategorie * 250)); ?>" height="12" /></td>
                    <td class="numar"><?php echo e(pret($c['valoare'])); ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<div class="caseta">
    <div class="titlu">Comenzi pe status</div>
    <div class="corp">
        <table class="date">
            <?php foreach (statusuri_comanda() as $cod => $eticheta): ?>
            <tr><td><?php echo html_status($cod); ?></td>
                <td class="numar"><a href="<?php echo e(url('admin/comenzi.php', array('status' => $cod))); ?>"><?php echo isset($s['pe_status'][$cod]) ? (int) $s['pe_status'][$cod] : 0; ?></a></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<div class="caseta">
    <div class="titlu">Atentie: produse cu stoc redus (sub 10)</div>
    <div class="corp">
        <?php if (empty($s['stoc_redus'])): ?><p>Toate produsele au stoc suficient.</p><?php else: ?>
        <table class="date">
            <?php foreach ($s['stoc_redus'] as $p): ?>
            <tr><td><?php echo e($p['cod_sku']); ?></td><td><a href="<?php echo e(url('admin/produs.php', array('id' => $p['id']))); ?>"><?php echo e($p['nume']); ?></a></td>
                <td class="numar <?php echo (int) $p['stoc'] === 0 ? 'stoc-nu' : 'stoc-putin'; ?>"><?php echo (int) $p['stoc']; ?> <?php echo e($p['unitate']); ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
