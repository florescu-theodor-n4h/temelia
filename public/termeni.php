<?php
/**
 * Termeni si conditii de utilizare a magazinului online.
 *
 * @package Temelia
 */
require __DIR__ . '/../src/bootstrap.php';
$titlu = 'Termeni si conditii';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<div class="text-legal">
<h1>Termeni si conditii</h1>
<p><small>Ultima actualizare: <?php echo date('d.m.Y', filemtime(__FILE__)); ?></small></p>

<h2>1. Date de identificare a comerciantului</h2>
<p>Site-ul este administrat de <strong><?php echo e(config('firma.nume')); ?></strong>, cu sediul in <?php echo e(config('firma.adresa')); ?>,
CUI <?php echo e(config('firma.cui')); ?>, inregistrata la Registrul Comertului sub nr. <?php echo e(config('firma.reg_com')); ?>,
telefon <?php echo e(config('firma.telefon')); ?>, e-mail <?php echo e(config('firma.email')); ?>.</p>

<h2>2. Obiectul</h2>
<p>Prezentii termeni reglementeaza utilizarea site-ului si vanzarea la distanta a produselor din catalog (materiale de constructii,
scule, instalatii, vopsele, articole de gradina), in conformitate cu OUG nr. 34/2014 privind drepturile consumatorilor si
Legea nr. 365/2002 privind comertul electronic.</p>

<h2>3. Comanda si incheierea contractului</h2>
<ul>
    <li>Comanda poate fi plasata cu sau fara cont de client.</li>
    <li>Contractul se considera incheiat in momentul confirmarii comenzii de catre TEMELIA (statusul "Confirmata").</li>
    <li>Ne rezervam dreptul de a anula comenzile care contin date eronate sau produse indisponibile; in acest caz, sumele achitate se restituie integral.</li>
</ul>

<h2>4. Preturi si plata</h2>
<p>Preturile sunt exprimate in lei (RON) si includ TVA <?php echo (int) config('cota_tva'); ?>%. Costul livrarii este afisat separat inainte de
trimiterea comenzii. Plata se poate face ramburs (numerar la livrare / ridicare) sau online cu cardul bancar.</p>

<h2>5. Livrarea</h2>
<p>Livrarea se face prin firma de curierat RapidCurier, in 1-4 zile lucratoare de la confirmare, sau prin ridicare personala din depozitul Craiova.
Materialele grele (peste 100 kg) se livreaza paletizat, pana la bordura, fara descarcare manuala. Detalii pe pagina <a href="<?php echo e(url('livrare.php')); ?>">Livrare si plata</a>.</p>

<h2>6. Dreptul de retragere</h2>
<p>Consumatorul are dreptul de a se retrage din contract in termen de 14 zile, conform <a href="<?php echo e(url('retur.php')); ?>">Politicii de retur</a>.</p>

<h2>7. Garantie si conformitate</h2>
<p>Produsele beneficiaza de garantia legala de conformitate conform OUG nr. 140/2021. Sculele electrice beneficiaza in plus de garantia comerciala a producatorului.</p>

<h2>8. Solutionarea litigiilor</h2>
<p>Eventualele neintelegeri se solutioneaza pe cale amiabila. Consumatorii se pot adresa Autoritatii Nationale pentru Protectia Consumatorilor
(<a href="https://anpc.ro">ANPC</a>), procedurii de solutionare alternativa a litigiilor (<a href="https://anpc.ro/ce-este-sal/">SAL</a>) sau
platformei europene de solutionare online a litigiilor (<a href="https://ec.europa.eu/consumers/odr">SOL</a>).</p>

<h2>9. Mentiune</h2>
<p><strong>Acest site este un proiect didactic.</strong> Comenzile nu sunt onorate, iar platile se proceseaza exclusiv in mediu de test.</p>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
