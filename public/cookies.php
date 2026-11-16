<?php
/**
 * Politica de cookie-uri si gestionarea consimtamantului.
 *
 * @package Temelia
 */
require __DIR__ . '/../src/bootstrap.php';
$optiune = isset($_COOKIE['consimtamant_cookie']) ? $_COOKIE['consimtamant_cookie'] : '';
$titlu = 'Politica de cookie-uri';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<div class="text-legal">
<h1>Politica de cookie-uri</h1>
<p>Un cookie este un fisier text de mici dimensiuni salvat de browser la vizitarea unui site. Site-ul TEMELIA foloseste un numar minim de cookie-uri.</p>

<h2>Cookie-uri folosite</h2>
<table class="date">
    <tr><th>Nume</th><th>Tip</th><th>Scop</th><th>Durata</th></tr>
    <tr><td>TEMELIASESIUNE</td><td>strict necesar</td><td>pastrarea cosului de cumparaturi, a autentificarii si a protectiei CSRF</td><td>pana la inchiderea browserului</td></tr>
    <tr class="impar"><td>consimtamant_cookie</td><td>strict necesar</td><td>retinerea optiunii dumneavoastra (accept / refuz)</td><td>180 de zile</td></tr>
</table>
<p>Site-ul <strong>nu</strong> foloseste cookie-uri de publicitate sau de urmarire de la terti. Cookie-urile strict necesare nu necesita consimtamant
(art. 4 alin. (5) din Legea nr. 506/2004).</p>

<h2>Optiunea dumneavoastra</h2>
<p>Optiune curenta: <strong><?php echo $optiune === 'acceptat' ? 'acceptat' : ($optiune === 'refuzat' ? 'refuzat' : 'nicio optiune exprimata'); ?></strong>.</p>
<form method="post" action="<?php echo e(url('cookie.php')); ?>">
    <?php echo csrf_camp(); ?>
    <input type="hidden" name="inapoi" value="<?php echo e(url('cookies.php')); ?>" />
    <input type="submit" name="optiune" value="Accept" class="buton buton-principal" />
    <input type="submit" name="optiune" value="Refuz" class="buton" />
</form>
<p>Puteti sterge oricand cookie-urile din setarile browserului.</p>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
