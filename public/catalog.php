<?php
/**
 * Catalogul de produse: afisare pe categorii, cautare dupa denumire,
 * filtrare dupa interval de pret, stoc si reduceri, sortare si paginare.
 *
 * Parametri GET: categorie, q, pret_min, pret_max, sortare, stoc,
 *                reduceri, pagina.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$categorieId = param_int('categorie');
$categorie = $categorieId ? Produse::categorie($categorieId) : null;
if ($categorieId && !$categorie) {
    http_response_code(404);
    $categorieId = 0;
}

// Valorile de pret sunt acceptate doar daca sunt numerice (validare pe server)
$pretMin = str_replace(',', '.', param('pret_min', '', $_GET));
$pretMax = str_replace(',', '.', param('pret_max', '', $_GET));
$pretMin = is_numeric($pretMin) && $pretMin >= 0 ? $pretMin : '';
$pretMax = is_numeric($pretMax) && $pretMax >= 0 ? $pretMax : '';
$cautare = function_exists('mb_substr') ? mb_substr(param('q', '', $_GET), 0, 80, 'UTF-8') : substr(param('q', '', $_GET), 0, 80);

$filtre = array(
    'cautare'       => $cautare,
    'categorie'     => $categorieId,
    'pret_min'      => $pretMin,
    'pret_max'      => $pretMax,
    'sortare'       => param('sortare', 'recomandate', $_GET),
    'doar_stoc'     => param('stoc', '', $_GET) === '1',
    'doar_reduceri' => param('reduceri', '', $_GET) === '1',
);

$pePagina = (int) config('produse_pe_pagina', 12);
$pagina = max(1, param_int('pagina', 1));
$rezultat = Produse::cauta($filtre, $pePagina, ($pagina - 1) * $pePagina);
$totalPagini = (int) ceil($rezultat['total'] / $pePagina);

// Parametrii pastrati in legaturile de paginare
$parametri = array_filter(array(
    'categorie' => $categorieId ?: '',
    'q'         => $cautare,
    'pret_min'  => $pretMin,
    'pret_max'  => $pretMax,
    'sortare'   => $filtre['sortare'],
    'stoc'      => $filtre['doar_stoc'] ? '1' : '',
    'reduceri'  => $filtre['doar_reduceri'] ? '1' : '',
), 'strlen');

if ($cautare !== '') {
    $titlu = 'Rezultate pentru "' . $cautare . '"';
} elseif ($categorie) {
    $titlu = $categorie['nume'];
} elseif ($filtre['doar_reduceri']) {
    $titlu = 'Produse la oferta';
} else {
    $titlu = 'Catalog produse';
}
$descriereMeta = $categorie ? $categorie['nume'] . ' - ' . $categorie['descriere'] : null;
$categorieActiva = $categorieId;
$cale = array(array($titlu, ''));

require DIR_SRC . '/sabloane/antet.php';
?>
<div id="coloana-stanga">
    <div class="caseta">
        <div class="titlu">Raioane</div>
        <div class="corp">
            <ul class="lista">
                <li<?php echo !$categorieId ? ' class="activ"' : ''; ?>><a href="<?php echo e(url('catalog.php')); ?>">Toate produsele</a></li>
                <?php foreach ($categoriiMeniu as $c): ?>
                <li<?php echo $categorieId === (int) $c['id'] ? ' class="activ"' : ''; ?>><a href="<?php echo e(url('catalog.php', array('categorie' => $c['id']))); ?>"><?php echo e($c['nume']); ?></a> <small>(<?php echo (int) $c['numar_produse']; ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div id="coloana-principala">
    <h1><?php echo e($titlu); ?></h1>
    <?php if ($categorie && $categorie['descriere']): ?><p><?php echo e($categorie['descriere']); ?></p><?php endif; ?>

    <form method="get" action="<?php echo e(url('catalog.php')); ?>" class="filtre">
        <label>Cauta: <input type="text" name="q" class="text mediu" value="<?php echo e($cautare); ?>" maxlength="80" /></label>
        <label>Raion:
            <select name="categorie">
                <option value="">- toate -</option>
                <?php foreach ($categoriiMeniu as $c): ?>
                <option value="<?php echo (int) $c['id']; ?>"<?php echo $categorieId === (int) $c['id'] ? ' selected="selected"' : ''; ?>><?php echo e($c['nume']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <br />
        <label>Pret intre <input type="text" name="pret_min" class="text scurt" value="<?php echo e($pretMin); ?>" /></label>
        <label>si <input type="text" name="pret_max" class="text scurt" value="<?php echo e($pretMax); ?>" /> lei</label>
        <label>Ordonare:
            <select name="sortare">
                <?php foreach (Produse::sortari() as $cheie => $s): ?>
                <option value="<?php echo e($cheie); ?>"<?php echo $filtre['sortare'] === $cheie ? ' selected="selected"' : ''; ?>><?php echo e($s[1]); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <br />
        <label><input type="checkbox" name="stoc" value="1"<?php echo $filtre['doar_stoc'] ? ' checked="checked"' : ''; ?> /> Doar produse in stoc</label>
        <label><input type="checkbox" name="reduceri" value="1"<?php echo $filtre['doar_reduceri'] ? ' checked="checked"' : ''; ?> /> Doar produse la oferta</label>
        <input type="submit" class="buton buton-principal" value="Aplica filtrele" />
        <a href="<?php echo e(url('catalog.php')); ?>">Reseteaza</a>
    </form>

    <p class="mic">Au fost gasite <strong><?php echo (int) $rezultat['total']; ?></strong> produse.</p>
    <?php echo html_grila($rezultat['produse']); ?>
    <?php echo html_paginare($pagina, $totalPagini, 'catalog.php', $parametri); ?>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
