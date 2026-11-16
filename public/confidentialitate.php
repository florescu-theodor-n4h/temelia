<?php
/**
 * Politica de confidentialitate (GDPR - Regulamentul UE 2016/679).
 *
 * @package Temelia
 */
require __DIR__ . '/../src/bootstrap.php';
$titlu = 'Politica de confidentialitate';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<div class="text-legal">
<h1>Politica de confidentialitate (GDPR)</h1>

<h2>1. Operatorul de date</h2>
<p><?php echo e(config('firma.nume')); ?>, <?php echo e(config('firma.adresa')); ?>, CUI <?php echo e(config('firma.cui')); ?>,
prelucreaza datele cu caracter personal in conformitate cu Regulamentul (UE) 2016/679 (GDPR) si Legea nr. 190/2018.
Contact pentru protectia datelor: <?php echo e(config('firma.email')); ?>.</p>

<h2>2. Ce date prelucram</h2>
<table class="date">
    <tr><th>Categorie</th><th>Date</th><th>Scop</th><th>Temei legal</th></tr>
    <tr><td>Cont client</td><td>nume, e-mail, telefon, parola (doar hash)</td><td>administrarea contului si a istoricului de comenzi</td><td>art. 6 alin. (1) lit. b) - contract</td></tr>
    <tr class="impar"><td>Comenzi</td><td>nume, telefon, e-mail, adresa de livrare</td><td>procesarea si livrarea comenzii, facturare</td><td>art. 6 alin. (1) lit. b) si c)</td></tr>
    <tr><td>Plati</td><td>ultimele 4 cifre ale cardului, referinta tranzactiei</td><td>evidenta platilor</td><td>art. 6 alin. (1) lit. b) si c)</td></tr>
    <tr class="impar"><td>Recenzii</td><td>numele afisat, nota, comentariul</td><td>publicarea opiniilor clientilor</td><td>art. 6 alin. (1) lit. a) - consimtamant</td></tr>
</table>
<p>Nu stocam niciodata numarul complet al cardului sau codul CVV.</p>

<h2>3. Destinatari</h2>
<p>Datele necesare livrarii sunt transmise firmei de curierat RapidCurier; datele de plata sunt transmise procesatorului de plati.
Nu vindem si nu inchiriem datele personale catre terti.</p>

<h2>4. Durata stocarii</h2>
<p>Datele contului se pastreaza pana la stergerea acestuia; datele din comenzi si documentele contabile se pastreaza 10 ani, conform Legii contabilitatii nr. 82/1991.</p>

<h2>5. Drepturile dumneavoastra</h2>
<ul>
    <li>dreptul de acces, rectificare si stergere ("dreptul de a fi uitat");</li>
    <li>dreptul la restrictionarea prelucrarii si la portabilitatea datelor;</li>
    <li>dreptul de opozitie si de retragere a consimtamantului;</li>
    <li>dreptul de a depune plangere la ANSPDCP (<a href="https://www.dataprotection.ro">www.dataprotection.ro</a>).</li>
</ul>
<p>Cererile se transmit la <?php echo e(config('firma.email')); ?> si primesc raspuns in maximum 30 de zile.</p>

<h2>6. Securitatea datelor</h2>
<p>Parolele sunt stocate sub forma de hash bcrypt, conexiunea este criptata (HTTPS), iar accesul la panoul de administrare este restrictionat.</p>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
