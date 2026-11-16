<?php
/**
 * Lista de favorite a clientului autentificat (A9).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

Autentificare::cereAutentificare();
$utilizator = Autentificare::utilizator();

if (este_post()) {
    csrf_verifica();
    $produsId = param_int('produs_id');
    if ($produsId && Favorite::contine($utilizator['id'], $produsId)) {
        Favorite::comuta($utilizator['id'], $produsId);
        flash('succes', 'Produsul a fost eliminat din favorite.');
    }
    redirectioneaza('favorite.php');
}

$produse = Favorite::lista($utilizator['id']);
$titlu = 'Produsele mele favorite';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Produsele mele favorite</h1>
<?php if (empty($produse)): ?>
    <div class="mesaj mesaj-info">Nu aveti produse salvate. Folositi butonul "Adauga la favorite" de pe pagina oricarui produs.</div>
<?php else: ?>
    <?php echo html_grila($produse); ?>
    <h3>Eliminare din lista</h3>
    <?php foreach ($produse as $p): ?>
    <form method="post" action="<?php echo e(url('favorite.php')); ?>" class="in-linie">
        <?php echo csrf_camp(); ?>
        <input type="hidden" name="produs_id" value="<?php echo (int) $p['id']; ?>" />
        <input type="submit" class="buton" value="x <?php echo e($p['nume']); ?>" />
    </form>
    <?php endforeach; ?>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/subsol.php';
