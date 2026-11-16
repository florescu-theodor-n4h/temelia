<?php
/**
 * Sablonul subsolului comun: informatii despre firma, legaturi catre
 * paginile legale si catre ANPC / platforma europeana SOL (obligatorii
 * conform legislatiei romanesti privind comertul electronic).
 *
 * @package Temelia
 */
?>
</div><!-- /continut -->

<div id="subsol">
    <div class="coloana">
        <h4><?php echo e(config('firma.marca')); ?></h4>
        <?php echo e(config('firma.nume')); ?><br />
        CUI: <?php echo e(config('firma.cui')); ?><br />
        Nr. Reg. Com.: <?php echo e(config('firma.reg_com')); ?><br />
        <?php echo e(config('firma.adresa')); ?>
    </div>
    <div class="coloana">
        <h4>Informatii clienti</h4>
        <ul>
            <li><a href="<?php echo e(url('termeni.php')); ?>">Termeni si conditii</a></li>
            <li><a href="<?php echo e(url('retur.php')); ?>">Politica de retur (14 zile)</a></li>
            <li><a href="<?php echo e(url('livrare.php')); ?>">Livrare si plata</a></li>
            <li><a href="<?php echo e(url('contact.php')); ?>">Contact</a></li>
        </ul>
    </div>
    <div class="coloana">
        <h4>Confidentialitate</h4>
        <ul>
            <li><a href="<?php echo e(url('confidentialitate.php')); ?>">Politica de confidentialitate (GDPR)</a></li>
            <li><a href="<?php echo e(url('cookies.php')); ?>">Politica de cookie-uri</a></li>
            <li><a href="<?php echo e(url('cookie.php', array('reseteaza' => 1))); ?>">Modifica preferintele cookie</a></li>
        </ul>
    </div>
    <div class="coloana">
        <h4>Contact rapid</h4>
        Telefon: <?php echo e(config('firma.telefon')); ?><br />
        E-mail: <a href="mailto:<?php echo e(config('firma.email')); ?>"><?php echo e(config('firma.email')); ?></a><br />
        <?php echo e(config('firma.program')); ?>
    </div>

    <div class="anpc">
        <a href="https://anpc.ro/ce-este-sal/" rel="nofollow noopener" title="Solutionarea alternativa a litigiilor (ANPC - SAL)"><img src="<?php echo e(url('img/anpc-sal.svg')); ?>" width="200" height="50" alt="ANPC - Solutionarea alternativa a litigiilor" /></a>
        <a href="https://ec.europa.eu/consumers/odr" rel="nofollow noopener" title="Solutionarea online a litigiilor (SOL)"><img src="<?php echo e(url('img/anpc-sol.svg')); ?>" width="200" height="50" alt="Solutionarea online a litigiilor - SOL" /></a>
    </div>

    <div class="drepturi">
        &copy; <?php echo date('Y'); ?> <?php echo e(config('firma.nume')); ?>. Toate drepturile rezervate.
        Preturile afisate sunt in lei (RON) si includ TVA <?php echo (int) config('cota_tva'); ?>%.
        Proiect didactic - comenzile si platile nu sunt reale.
    </div>
</div>

</div><!-- /pagina -->
</body>
</html>
