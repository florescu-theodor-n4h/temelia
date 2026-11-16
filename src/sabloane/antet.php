<?php
/**
 * Sablonul antetului comun tuturor paginilor publice.
 *
 * Variabile asteptate (optionale):
 *   $titlu          - titlul paginii;
 *   $descriereMeta  - descrierea pentru motoarele de cautare (SEO);
 *   $categorieActiva - identificatorul categoriei evidentiate in meniu;
 *   $cale           - lista de perechi array(eticheta, adresa) pentru calea de navigare.
 *
 * @package Temelia
 */
$titlu = isset($titlu) ? $titlu : config('firma.slogan');
$descriereMeta = isset($descriereMeta) ? $descriereMeta : 'TEMELIA - magazin online de materiale de constructii, scule, instalatii, vopsele si articole de gradina. Livrare in toata Romania.';
$categorieActiva = isset($categorieActiva) ? (int) $categorieActiva : 0;
$utilizatorCurent = Autentificare::utilizator();
$categoriiMeniu = Produse::categorii();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ro" lang="ro">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="<?php echo e($descriereMeta); ?>" />
    <title><?php echo e($titlu); ?> - <?php echo e(config('firma.marca')); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo e(url('css/stil.css')); ?>" />
    <link rel="icon" type="image/svg+xml" href="<?php echo e(url('img/favicon.svg')); ?>" />
    <?php if (!empty($adresaCanonica)): ?>
    <link rel="canonical" href="<?php echo e($adresaCanonica); ?>" />
    <?php endif; ?>
</head>
<body>
<div id="pagina">

<?php if (!isset($_COOKIE['consimtamant_cookie'])): ?>
<div id="banner-cookie">
    Folosim cookie-uri strict necesare pentru functionarea cosului si a contului. Cu acordul dumneavoastra
    putem folosi si cookie-uri de preferinte. Detalii in <a href="<?php echo e(url('cookies.php')); ?>">Politica de cookie-uri</a>.
    &nbsp;
    <form method="post" action="<?php echo e(url('cookie.php')); ?>">
        <?php echo csrf_camp(); ?>
        <input type="hidden" name="inapoi" value="<?php echo e($_SERVER['REQUEST_URI']); ?>" />
        <input type="submit" name="optiune" value="Accept" class="buton buton-principal" />
        <input type="submit" name="optiune" value="Refuz" class="buton" />
    </form>
</div>
<?php endif; ?>

<div id="bara-sus">
    <span class="stanga">Linia comerciala: <?php echo e(config('firma.telefon')); ?> &middot; <?php echo e(config('firma.program')); ?></span>
    <?php if ($utilizatorCurent): ?>
        Bun venit, <strong><?php echo e($utilizatorCurent['nume']); ?></strong>
        <a href="<?php echo e(url('cont/comenzi.php')); ?>">Comenzile mele</a>
        <a href="<?php echo e(url('favorite.php')); ?>">Favorite</a>
        <?php if ($utilizatorCurent['rol'] === 'admin'): ?>
            <a href="<?php echo e(url('admin/index.php')); ?>">[Administrare]</a>
        <?php endif; ?>
        <a href="<?php echo e(url('cont/deconectare.php')); ?>">Iesire</a>
    <?php else: ?>
        <a href="<?php echo e(url('cont/autentificare.php')); ?>">Autentificare</a>
        <a href="<?php echo e(url('cont/inregistrare.php')); ?>">Cont nou</a>
    <?php endif; ?>
    <a href="<?php echo e(url('comparare.php')); ?>">Comparare (<?php echo count(Favorite::deComparat()); ?>)</a>
    <a href="<?php echo e(url('contact.php')); ?>">Contact</a>
</div>

<div id="antet">
    <div class="sigla">
        <a href="<?php echo e(url('index.php')); ?>"><img src="<?php echo e(url('img/sigla.svg')); ?>" width="260" height="60" alt="TEMELIA - Materiale de constructii" /></a>
    </div>
    <div class="cautare">
        <form method="get" action="<?php echo e(url('catalog.php')); ?>">
            <input type="text" name="q" class="text" value="<?php echo e(param('q', '', $_GET)); ?>" maxlength="80" title="Cautare dupa denumire sau cod produs" />
            <input type="submit" value="Cauta" class="buton" />
        </form>
        <a class="cos-rapid" href="<?php echo e(url('cos.php')); ?>">Cos: <?php echo (int) Cos::numarArticole(); ?> articole</a>
    </div>
</div>

<ul id="meniu">
    <li><a href="<?php echo e(url('index.php')); ?>">Acasa</a></li>
    <?php foreach ($categoriiMeniu as $c): ?>
        <li><a href="<?php echo e(url('catalog.php', array('categorie' => $c['id']))); ?>"<?php echo $categorieActiva === (int) $c['id'] ? ' class="activ"' : ''; ?>><?php echo e($c['nume']); ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if (!empty($cale)): ?>
<div class="cale">
    Va aflati in: <a href="<?php echo e(url('index.php')); ?>">Acasa</a>
    <?php foreach ($cale as $element): ?>
        &raquo; <?php if ($element[1] !== ''): ?><a href="<?php echo e($element[1]); ?>"><?php echo e($element[0]); ?></a><?php else: ?><?php echo e($element[0]); ?><?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div id="continut">
<?php foreach (preia_flash() as $mesajFlash): ?>
    <div class="mesaj mesaj-<?php echo e($mesajFlash['tip']); ?>"><?php echo e($mesajFlash['text']); ?></div>
<?php endforeach; ?>
