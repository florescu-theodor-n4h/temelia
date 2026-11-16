<?php
/**
 * Administrarea categoriilor (raioanelor): adaugare, editare, stergere.
 * O categorie care contine produse nu poate fi stearsa.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';
Autentificare::cereAdmin();

$erori = array();
$editare = param_int('id') ? Produse::categorie(param_int('id')) : null;

if (este_post()) {
    csrf_verifica();
    $id = param_int('categorie_id');

    if (param('actiune') === 'sterge') {
        $numar = (int) Baza::valoare('SELECT COUNT(*) FROM produse WHERE categorie_id = ?', array($id));
        if ($numar > 0) {
            flash('eroare', 'Categoria contine ' . $numar . ' produse si nu poate fi stearsa. Mutati mai intai produsele.');
        } else {
            Baza::executa('DELETE FROM categorii WHERE id = ?', array($id));
            flash('succes', 'Categoria a fost stearsa.');
        }
        redirectioneaza('admin/categorii.php');
    }

    $v = new Validator($_POST);
    $v->obligatoriu('nume', 'Denumirea')->lungime('nume', 'Denumirea', 2, 100)
      ->lungime('descriere', 'Descrierea', 0, 500)->intreg('ordine', 'Ordinea', 0, 999);
    $slug = slugifica(param('nume', '', $_POST));
    if (Baza::valoare('SELECT COUNT(*) FROM categorii WHERE slug = ? AND id <> ?', array($slug, $id)) > 0) {
        $v->adaugaEroare('nume', 'Exista deja o categorie cu aceasta denumire.');
    }
    if ($v->esteValid()) {
        if ($id) {
            Baza::executa('UPDATE categorii SET nume = ?, slug = ?, descriere = ?, ordine = ? WHERE id = ?',
                array($v->valoare('nume'), $slug, $v->valoare('descriere'), (int) $v->valoare('ordine'), $id));
        } else {
            Baza::insereaza('categorii', array('nume' => $v->valoare('nume'), 'slug' => $slug,
                'descriere' => $v->valoare('descriere'), 'ordine' => (int) $v->valoare('ordine')));
        }
        flash('succes', 'Categoria a fost salvata.');
        redirectioneaza('admin/categorii.php');
    }
    $erori = $v->erori();
    $editare = array('id' => $id, 'nume' => param('nume', '', $_POST), 'descriere' => param('descriere', '', $_POST), 'ordine' => param('ordine', '0', $_POST));
}

$categorii = Produse::categorii();
$titlu = 'Categorii';
$sectiune = 'categorii';
require DIR_SRC . '/sabloane/admin_antet.php';
?>
<h1>Categorii (raioane)</h1>
<table class="date">
    <thead><tr><th>Ordine</th><th>Denumire</th><th>Descriere</th><th class="numar">Produse active</th><th>Actiuni</th></tr></thead>
    <?php foreach ($categorii as $i => $c): ?>
    <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
        <td><?php echo (int) $c['ordine']; ?></td><td><strong><?php echo e($c['nume']); ?></strong></td><td><small><?php echo e($c['descriere']); ?></small></td>
        <td class="numar"><?php echo (int) $c['numar_produse']; ?></td>
        <td><a href="<?php echo e(url('admin/categorii.php', array('id' => $c['id']))); ?>" class="buton">Editeaza</a>
            <form method="post" action="<?php echo e(url('admin/categorii.php')); ?>" class="in-linie">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="sterge" />
                <input type="hidden" name="categorie_id" value="<?php echo (int) $c['id']; ?>" />
                <input type="submit" class="buton buton-pericol" value="Sterge" />
            </form></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php echo html_erori($erori); ?>
<div class="caseta">
    <div class="titlu"><?php echo $editare && !empty($editare['id']) ? 'Editare categorie' : 'Categorie noua'; ?></div>
    <div class="corp">
        <form method="post" action="<?php echo e(url('admin/categorii.php')); ?>">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="categorie_id" value="<?php echo $editare ? (int) $editare['id'] : 0; ?>" />
            <table class="formular">
                <tr><th>Denumire *</th><td><input type="text" name="nume" value="<?php echo e($editare ? $editare['nume'] : ''); ?>" class="text mediu" maxlength="100" /></td></tr>
                <tr><th>Descriere</th><td><textarea name="descriere" rows="3" cols="50" class="lung"><?php echo e($editare ? $editare['descriere'] : ''); ?></textarea></td></tr>
                <tr><th>Ordine afisare</th><td><input type="text" name="ordine" value="<?php echo e($editare ? $editare['ordine'] : '0'); ?>" class="text scurt" /></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Salveaza" />
                    <?php if ($editare): ?><a href="<?php echo e(url('admin/categorii.php')); ?>">Renunta</a><?php endif; ?></td></tr>
            </table>
        </form>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
