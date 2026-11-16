<?php
/**
 * Administrarea codurilor de reducere (A8): procentuale sau valorice,
 * cu prag minim de comanda, data de expirare si numar maxim de utilizari.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';
Autentificare::cereAdmin();

$erori = array();
$editare = param_int('id') ? Cupoane::dupaId(param_int('id')) : null;

if (este_post()) {
    csrf_verifica();
    $id = param_int('cupon_id');

    if (param('actiune') === 'sterge') {
        Cupoane::sterge($id);
        flash('succes', 'Codul de reducere a fost sters.');
        redirectioneaza('admin/cupoane.php');
    }

    $date = array(
        'cod'           => strtoupper(param('cod', '', $_POST)),
        'descriere'     => param('descriere', '', $_POST),
        'tip'           => param('tip', '', $_POST),
        'valoare'       => str_replace(',', '.', param('valoare', '', $_POST)),
        'minim_comanda' => str_replace(',', '.', param('minim_comanda', '0', $_POST)),
        'valabil_pana'  => param('valabil_pana', '', $_POST),
        'utilizari_max' => param('utilizari_max', '', $_POST),
        'activ'         => !empty($_POST['activ']),
    );
    $v = new Validator(array_merge($_POST, $date));
    $v->obligatoriu('cod', 'Codul')->lungime('cod', 'Codul', 3, 30)
      ->inLista('tip', 'Tipul', array('procent', 'valoare'))
      ->numar('valoare', 'Valoarea', 0.01, $date['tip'] === 'procent' ? 100 : 100000)
      ->numar('minim_comanda', 'Pragul minim', 0, 1000000)
      ->lungime('descriere', 'Descrierea', 0, 200);
    if (!preg_match('/^[A-Z0-9_-]+$/', $date['cod'])) {
        $v->adaugaEroare('cod', 'Codul poate contine doar litere, cifre, "-" si "_".');
    }
    if ($date['valabil_pana'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date['valabil_pana'])) {
        $v->adaugaEroare('valabil_pana', 'Data de expirare trebuie sa aiba formatul AAAA-LL-ZZ.');
    }
    if ($date['utilizari_max'] !== '') {
        $v->intreg('utilizari_max', 'Numarul maxim de utilizari', 1, 1000000);
    }
    $existent = Cupoane::dupaCod($date['cod']);
    if ($existent && (int) $existent['id'] !== $id) {
        $v->adaugaEroare('cod', 'Exista deja un cupon cu acest cod.');
    }

    if ($v->esteValid()) {
        Cupoane::salveaza($id, $date);
        flash('succes', 'Codul de reducere a fost salvat.');
        redirectioneaza('admin/cupoane.php');
    }
    $erori = $v->erori();
    $editare = array_merge($date, array('id' => $id));
}

$cupoane = Cupoane::toate();
$titlu = 'Coduri de reducere';
$sectiune = 'cupoane';
require DIR_SRC . '/sabloane/admin_antet.php';
$e = $editare ? $editare : array('id' => 0, 'cod' => '', 'descriere' => '', 'tip' => 'procent', 'valoare' => '', 'minim_comanda' => '0', 'valabil_pana' => '', 'utilizari_max' => '', 'activ' => 1);
?>
<h1>Coduri de reducere</h1>
<table class="date">
    <thead><tr><th>Cod</th><th>Descriere</th><th>Reducere</th><th class="numar">Minim comanda</th><th>Expira</th><th class="numar">Utilizari</th><th>Stare</th><th>Actiuni</th></tr></thead>
    <?php foreach ($cupoane as $i => $c): ?>
    <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
        <td><strong><?php echo e($c['cod']); ?></strong></td><td><small><?php echo e($c['descriere']); ?></small></td>
        <td><?php echo $c['tip'] === 'procent' ? (float) $c['valoare'] . '%' : e(pret($c['valoare'])); ?></td>
        <td class="numar"><?php echo e(pret($c['minim_comanda'])); ?></td>
        <td><?php echo $c['valabil_pana'] ? e(data_ro($c['valabil_pana'], false)) : 'nelimitat'; ?></td>
        <td class="numar"><?php echo (int) $c['utilizari']; ?><?php echo $c['utilizari_max'] !== null ? ' / ' . (int) $c['utilizari_max'] : ''; ?></td>
        <td><?php echo $c['activ'] ? '<span class="stoc-da">activ</span>' : '<span class="stoc-nu">inactiv</span>'; ?></td>
        <td><a href="<?php echo e(url('admin/cupoane.php', array('id' => $c['id']))); ?>" class="buton">Editeaza</a>
            <form method="post" action="<?php echo e(url('admin/cupoane.php')); ?>" class="in-linie">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="sterge" />
                <input type="hidden" name="cupon_id" value="<?php echo (int) $c['id']; ?>" />
                <input type="submit" class="buton buton-pericol" value="Sterge" />
            </form></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php echo html_erori($erori); ?>
<div class="caseta">
    <div class="titlu"><?php echo !empty($e['id']) ? 'Editare cod' : 'Cod nou'; ?></div>
    <div class="corp">
        <form method="post" action="<?php echo e(url('admin/cupoane.php')); ?>">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="cupon_id" value="<?php echo (int) $e['id']; ?>" />
            <table class="formular">
                <tr><th>Cod *</th><td><input type="text" name="cod" value="<?php echo e($e['cod']); ?>" class="text mediu" maxlength="30" /></td></tr>
                <tr><th>Descriere</th><td><input type="text" name="descriere" value="<?php echo e($e['descriere']); ?>" class="text lung" maxlength="200" /></td></tr>
                <tr><th>Tip *</th><td><select name="tip"><?php echo html_optiuni(array('procent' => 'Procent din subtotal', 'valoare' => 'Suma fixa (lei)'), $e['tip']); ?></select></td></tr>
                <tr><th>Valoare *</th><td><input type="text" name="valoare" value="<?php echo e($e['valoare'] !== '' ? (float) $e['valoare'] : ''); ?>" class="text scurt" /></td></tr>
                <tr><th>Comanda minima (lei)</th><td><input type="text" name="minim_comanda" value="<?php echo e((float) $e['minim_comanda']); ?>" class="text scurt" /></td></tr>
                <tr><th>Valabil pana la</th><td><input type="text" name="valabil_pana" value="<?php echo e($e['valabil_pana']); ?>" class="text mediu" maxlength="10" /> <small>AAAA-LL-ZZ, gol = nelimitat</small></td></tr>
                <tr><th>Utilizari maxime</th><td><input type="text" name="utilizari_max" value="<?php echo e($e['utilizari_max']); ?>" class="text scurt" /> <small>gol = nelimitat</small></td></tr>
                <tr><th></th><td><label><input type="checkbox" name="activ" value="1"<?php echo $e['activ'] ? ' checked="checked"' : ''; ?> /> Activ</label></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Salveaza" />
                    <?php if (!empty($e['id'])): ?><a href="<?php echo e(url('admin/cupoane.php')); ?>">Renunta</a><?php endif; ?></td></tr>
            </table>
        </form>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
