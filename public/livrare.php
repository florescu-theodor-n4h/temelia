<?php
/**
 * Informatii despre livrare (tarife RapidCurier) si metodele de plata.
 *
 * @package Temelia
 */
require __DIR__ . '/../src/bootstrap.php';
$titlu = 'Livrare si plata';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<div class="text-legal">
<h1>Livrare si plata</h1>
<h2>Livrare prin curier RapidCurier</h2>
<table class="date">
    <tr><th>Element</th><th class="numar">Tarif (TVA inclus)</th></tr>
    <tr><td>Colet pana la 5 kg</td><td class="numar"><?php echo e(pret(CurierShim::TARIF_BAZA)); ?></td></tr>
    <tr class="impar"><td>Fiecare kg suplimentar peste 5 kg</td><td class="numar"><?php echo e(pret(CurierShim::TARIF_KG_SUPLIMENTAR)); ?></td></tr>
    <tr><td>Transport paletizat (peste 100 kg)</td><td class="numar">+ <?php echo e(pret(CurierShim::SUPRATAXA_PALET)); ?></td></tr>
    <tr class="impar"><td>Comision ramburs</td><td class="numar"><?php echo e(pret(CurierShim::COMISION_RAMBURS)); ?></td></tr>
    <tr><td>Livrare in judetul <?php echo e(CurierShim::JUDET_DEPOZIT); ?></td><td class="numar">- 20%</td></tr>
    <tr class="impar"><td>Comenzi peste <?php echo e(pret(CurierShim::PRAG_LIVRARE_GRATUITA)); ?> (pana la 100 kg)</td><td class="numar"><strong>GRATUIT</strong></td></tr>
</table>
<p>Termen de livrare: 1-2 zile lucratoare pentru coletele standard, 3-4 zile lucratoare pentru transportul paletizat.</p>

<h2>Ridicare personala</h2>
<p>Gratuit, din depozitul: <?php echo e(config('firma.adresa')); ?>. Program: <?php echo e(config('firma.program')); ?>.</p>

<h2>Metode de plata</h2>
<ul>
    <li><strong>Ramburs</strong> - numerar la curier sau la ridicare.</li>
    <li><strong>Card bancar</strong> (Visa, Mastercard) - plata online securizata, imediat dupa plasarea comenzii.</li>
</ul>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
