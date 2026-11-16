<?php
/**
 * Comparatorul de produse (A9): afiseaza alaturat pretul, stocul si
 * specificatiile tehnice ale produselor selectate (maximum 4).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

if (este_post()) {
    csrf_verifica();
    if (param('actiune') === 'goleste') {
        $_SESSION['comparare'] = array();
    } else {
        Favorite::eliminaComparare(param_int('produs_id'));
    }
    redirectioneaza('comparare.php');
}

$tabel = Favorite::tabelComparatie();
$titlu = 'Comparare produse';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Comparare produse</h1>
<?php if (empty($tabel['produse'])): ?>
    <div class="mesaj mesaj-info">Nu ati selectat produse pentru comparare. Folositi butonul "Compara" de pe pagina produselor (maximum <?php echo Favorite::MAXIM_COMPARARE; ?>).</div>
<?php else: ?>
<div class="tabel-derulant">
<table class="date">
    <tr><th>Produs</th>
        <?php foreach ($tabel['produse'] as $p): ?>
        <td class="centru">
            <a href="<?php echo e(url('produs.php', array('p' => $p['slug']))); ?>"><img src="<?php echo e(imagine_produs($p)); ?>" width="50" height="38" alt="" /><br /><strong><?php echo e($p['nume']); ?></strong></a>
        </td>
        <?php endforeach; ?>
    </tr>
    <tr class="impar"><th>Pret</th><?php foreach ($tabel['produse'] as $p): ?><td class="centru"><?php echo html_pret($p); ?></td><?php endforeach; ?></tr>
    <tr><th>Disponibilitate</th><?php foreach ($tabel['produse'] as $p): ?><td class="centru"><?php echo html_stoc($p); ?></td><?php endforeach; ?></tr>
    <tr class="impar"><th>Unitate de vanzare</th><?php foreach ($tabel['produse'] as $p): ?><td class="centru"><?php echo e($p['unitate']); ?></td><?php endforeach; ?></tr>
    <?php foreach ($tabel['caracteristici'] as $i => $caracteristica): ?>
    <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>><th><?php echo e($caracteristica); ?></th>
        <?php foreach ($tabel['produse'] as $id => $p): ?>
        <td class="centru"><?php echo isset($tabel['valori'][$id][$caracteristica]) ? e($tabel['valori'][$id][$caracteristica]) : '&ndash;'; ?></td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    <tr><th></th>
        <?php foreach ($tabel['produse'] as $p): ?>
        <td class="centru">
            <form method="post" action="<?php echo e(url('comparare.php')); ?>">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="produs_id" value="<?php echo (int) $p['id']; ?>" />
                <input type="submit" class="buton" value="Elimina" />
            </form>
        </td>
        <?php endforeach; ?>
    </tr>
</table>
</div>
<form method="post" action="<?php echo e(url('comparare.php')); ?>">
    <?php echo csrf_camp(); ?>
    <input type="hidden" name="actiune" value="goleste" />
    <input type="submit" class="buton" value="Goleste lista de comparare" />
</form>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/subsol.php';
