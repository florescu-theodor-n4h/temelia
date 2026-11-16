<?php
/**
 * Antetul panoului de administrare. Verifica drepturile de acces
 * (doar rolul 'admin') inainte de orice afisare.
 *
 * Variabile asteptate: $titlu, $sectiune (pentru evidentierea meniului).
 *
 * @package Temelia
 */
Autentificare::cereAdmin();
$titlu = isset($titlu) ? $titlu : 'Administrare';
$sectiune = isset($sectiune) ? $sectiune : '';
$administrator = Autentificare::utilizator();
$meniuAdmin = array(
    'tablou'    => array('Tablou de bord', 'admin/index.php'),
    'produse'   => array('Produse', 'admin/produse.php'),
    'comenzi'   => array('Comenzi', 'admin/comenzi.php'),
    'categorii' => array('Categorii', 'admin/categorii.php'),
    'cupoane'   => array('Coduri reducere', 'admin/cupoane.php'),
);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ro" lang="ro">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo e($titlu); ?> - Administrare TEMELIA</title>
    <link rel="stylesheet" type="text/css" href="<?php echo e(url('css/stil.css')); ?>" />
    <link rel="icon" type="image/svg+xml" href="<?php echo e(url('img/favicon.svg')); ?>" />
</head>
<body class="admin">
<div id="pagina">
<div id="bara-sus">
    <span class="stanga">PANOU DE ADMINISTRARE &middot; <?php echo e(config('firma.nume')); ?></span>
    Administrator: <strong><?php echo e($administrator['nume']); ?></strong>
    <a href="<?php echo e(url('index.php')); ?>">Vezi magazinul</a>
    <a href="<?php echo e(url('cont/deconectare.php', array('csrf' => csrf_jeton()))); ?>">Iesire</a>
</div>
<div id="antet">
    <div class="sigla"><a href="<?php echo e(url('admin/index.php')); ?>"><img src="<?php echo e(url('img/sigla.svg')); ?>" width="260" height="60" alt="TEMELIA" /></a></div>
</div>
<ul id="meniu">
    <?php foreach ($meniuAdmin as $cheie => $m): ?>
    <li><a href="<?php echo e(url($m[1])); ?>"<?php echo $sectiune === $cheie ? ' class="activ"' : ''; ?>><?php echo e($m[0]); ?></a></li>
    <?php endforeach; ?>
</ul>
<div id="continut">
<?php foreach (preia_flash() as $mesajFlash): ?>
    <div class="mesaj mesaj-<?php echo e($mesajFlash['tip']); ?>"><?php echo e($mesajFlash['text']); ?></div>
<?php endforeach; ?>
