<?php
/**
 * Administrarea produselor: lista completa, cautare, actualizare rapida
 * a stocului si a pretului, stergere.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';
Autentificare::cereAdmin();

if (este_post()) {
    csrf_verifica();
    $id = param_int('produs_id');
    $actiune = param('actiune');

    if ($actiune === 'sterge' && $id) {
        $rezultat = Produse::sterge($id);
        flash('succes', $rezultat === 'sters'
            ? 'Produsul a fost sters.'
            : 'Produsul apare in comenzi existente, de aceea a fost doar dezactivat (ascuns din magazin).');
    } elseif ($actiune === 'rapid' && $id) {
        $v = new Validator($_POST);
        $v->intreg('stoc', 'Stocul', 0, 1000000)->numar('pret', 'Pretul', 0.01, 1000000);
        if ($v->esteValid()) {
            Baza::executa('UPDATE produse SET stoc = ?, pret = ? WHERE id = ?',
                array((int) $v->valoare('stoc'), round((float) str_replace(',', '.', $v->valoare('pret')), 2), $id));
            flash('succes', 'Pretul si stocul au fost actualizate.');
        } else {
            flash('eroare', implode(' ', $v->erori()));
        }
    }
    redirectioneaza('admin/produse.php', array('q' => param('q')));
}

$cautare = param('q', '', $_GET);
$produse = Produse::toateAdmin($cautare);

$titlu = 'Produse';
$sectiune = 'produse';
require DIR_SRC . '/sabloane/admin_antet.php';
?>
<h1>Produse (<?php echo count($produse); ?>)</h1>
<div class="unelte">
    <a href="<?php echo e(url('admin/produs.php')); ?>" class="buton buton-principal">+ Adauga produs nou</a>
    <form method="get" action="<?php echo e(url('admin/produse.php')); ?>" class="in-linie">
        <input type="text" name="q" value="<?php echo e($cautare); ?>" class="text mediu" />
        <input type="submit" class="buton" value="Cauta" />
    </form>
</div>

<div class="tabel-derulant">
<table class="date">
    <thead><tr><th>ID</th><th></th><th>Cod</th><th>Denumire</th><th>Raion</th><th>Pret / stoc (actualizare rapida)</th><th>Stare</th><th>Actiuni</th></tr></thead>
    <tbody>
    <?php foreach ($produse as $i => $p): ?>
    <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
        <td><?php echo (int) $p['id']; ?></td>
        <td><img src="<?php echo e(imagine_produs($p)); ?>" width="50" height="38" alt="" /></td>
        <td><?php echo e($p['cod_sku']); ?></td>
        <td><a href="<?php echo e(url('admin/produs.php', array('id' => $p['id']))); ?>"><?php echo e($p['nume']); ?></a>
            <?php if (Calculator::procentReducere($p['pret'], $p['pret_vechi'])): ?><br /><span class="reducere">-<?php echo Calculator::procentReducere($p['pret'], $p['pret_vechi']); ?>%</span><?php endif; ?></td>
        <td><?php echo e($p['categorie_nume']); ?></td>
        <td>
            <form method="post" action="<?php echo e(url('admin/produse.php')); ?>">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="rapid" />
                <input type="hidden" name="produs_id" value="<?php echo (int) $p['id']; ?>" />
                <input type="hidden" name="q" value="<?php echo e($cautare); ?>" />
                <input type="text" name="pret" value="<?php echo e(number_format((float) $p['pret'], 2, '.', '')); ?>" class="text scurt" /> lei
                <input type="text" name="stoc" value="<?php echo (int) $p['stoc']; ?>" class="text scurt" />
                <input type="submit" class="buton" value="OK" />
            </form>
        </td>
        <td><?php echo $p['activ'] ? '<span class="stoc-da">activ</span>' : '<span class="stoc-nu">inactiv</span>'; ?><?php echo $p['recomandat'] ? '<br /><small>recomandat</small>' : ''; ?></td>
        <td>
            <a href="<?php echo e(url('admin/produs.php', array('id' => $p['id']))); ?>" class="buton">Editeaza</a>
            <form method="post" action="<?php echo e(url('admin/produse.php')); ?>" class="in-linie">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="sterge" />
                <input type="hidden" name="produs_id" value="<?php echo (int) $p['id']; ?>" />
                <input type="submit" class="buton buton-pericol" value="Sterge" title="Stergere definitiva (sau dezactivare daca exista comenzi)" />
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
