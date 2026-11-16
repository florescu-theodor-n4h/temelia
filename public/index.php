<?php
/**
 * Pagina principala: prezentarea magazinului, categoriile, produsele
 * recomandate, ofertele si noutatile.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$titlu = 'Materiale de constructii, scule si instalatii';
$recomandate = Produse::recomandate(8);
$oferte = Produse::cauta(array('doar_reduceri' => true, 'sortare' => 'pret_asc'), 4);
$noutati = Produse::noutati(4);

require DIR_SRC . '/sabloane/antet.php';
?>
<div id="coloana-stanga">
    <div class="caseta">
        <div class="titlu">Raioane</div>
        <div class="corp">
            <ul class="lista">
                <?php foreach ($categoriiMeniu as $c): ?>
                <li><a href="<?php echo e(url('catalog.php', array('categorie' => $c['id']))); ?>"><?php echo e($c['nume']); ?></a> <small>(<?php echo (int) $c['numar_produse']; ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="caseta">
        <div class="titlu">De ce TEMELIA?</div>
        <div class="corp">
            <ul class="lista">
                <li>Livrare prin curier in toata tara</li>
                <li>Transport gratuit peste 1.500 lei</li>
                <li>Retur in 14 zile</li>
                <li>Plata ramburs sau cu cardul</li>
                <li>Consultanta tehnica gratuita</li>
            </ul>
        </div>
    </div>
    <div class="caseta">
        <div class="titlu">Urmarire comanda</div>
        <div class="corp">
            <form method="get" action="<?php echo e(url('urmarire.php')); ?>">
                <label>Numar comanda:<br /><input type="text" name="numar" class="text lung" maxlength="20" /></label><br />
                <label>E-mail:<br /><input type="text" name="email" class="text lung" maxlength="160" /></label><br />
                <input type="submit" class="buton" value="Verifica" />
            </form>
        </div>
    </div>
</div>

<div id="coloana-principala">
    <div class="banner">
        <h2>Tot ce va trebuie pentru constructie si renovare, intr-un singur loc</h2>
        <p>De la ciment si BCA pana la scule electrice, instalatii si vopsele. Peste <?php echo (int) array_sum(array_column($categoriiMeniu, 'numar_produse')); ?> de produse disponibile cu livrare rapida prin RapidCurier.</p>
        <p><strong>Oferta de bun venit:</strong> folositi codul <strong>BINEVENIT10</strong> pentru 10% reducere la prima comanda.</p>
        <a href="<?php echo e(url('catalog.php')); ?>" class="buton buton-principal">Vezi tot catalogul &raquo;</a>
        <a href="<?php echo e(url('catalog.php', array('reduceri' => 1))); ?>" class="buton">Produse la oferta</a>
    </div>

    <div class="caseta">
        <div class="titlu">Produse recomandate</div>
        <div class="corp"><?php echo html_grila($recomandate); ?></div>
    </div>

    <?php if (!empty($oferte['produse'])): ?>
    <div class="caseta">
        <div class="titlu">Oferte speciale</div>
        <div class="corp"><?php echo html_grila($oferte['produse']); ?></div>
    </div>
    <?php endif; ?>

    <div class="caseta">
        <div class="titlu">Noutati in catalog</div>
        <div class="corp"><?php echo html_grila($noutati); ?></div>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
