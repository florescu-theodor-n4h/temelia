<?php
/**
 * Politica de retur: dreptul de retragere in 14 zile (OUG nr. 34/2014).
 *
 * @package Temelia
 */
require __DIR__ . '/../src/bootstrap.php';
$titlu = 'Politica de retur';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<div class="text-legal">
<h1>Politica de retur - dreptul de retragere in 14 zile</h1>

<h2>1. Termenul de retragere</h2>
<p>Conform OUG nr. 34/2014, aveti dreptul de a va retrage din contract, fara a preciza motivul, in termen de <strong>14 zile calendaristice</strong>
de la data la care intrati in posesia fizica a produselor.</p>

<h2>2. Cum exercitati dreptul de retragere</h2>
<ol>
    <li>Ne informati printr-o declaratie neechivoca (e-mail la <?php echo e(config('firma.email')); ?> sau telefon <?php echo e(config('firma.telefon')); ?>), mentionand numarul comenzii.</li>
    <li>Returnati produsele in maximum 14 zile de la comunicarea deciziei, la adresa: <?php echo e(config('firma.adresa')); ?>.</li>
    <li>Produsele trebuie returnate in starea in care au fost primite, cu accesoriile si documentele insotitoare.</li>
</ol>

<h2>3. Rambursarea</h2>
<p>Rambursam toate sumele primite, inclusiv costul livrarii initiale (la nivelul celei mai ieftine metode standard), in cel mult 14 zile de la
primirea notificarii, folosind aceeasi metoda de plata. Putem amana rambursarea pana la primirea produselor. Costul direct al returnarii
este suportat de client.</p>

<h2>4. Exceptii</h2>
<ul>
    <li>produse confectionate dupa specificatiile clientului (ex. vopsea nuantata la comanda, cabluri taiate la lungime);</li>
    <li>produse care, prin natura lor, se amesteca inseparabil cu alte elemente dupa livrare (ex. ciment sau adezivi din saci desfacuti).</li>
</ul>

<h2>5. Model de formular de retragere</h2>
<p><em>Catre <?php echo e(config('firma.nume')); ?>, <?php echo e(config('firma.adresa')); ?>, <?php echo e(config('firma.email')); ?>:<br />
Va informez prin prezenta cu privire la retragerea mea din contractul referitor la vanzarea urmatoarelor produse: ......,
comanda nr. ......, primita la data de ......<br />
Numele consumatorului: ...... Adresa: ...... Data: ......</em></p>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
