<?php
/**
 * Confirmarea comenzii: numarul comenzii si rezumatul acesteia.
 * Accesibila titularului comenzii (cont sau sesiune) si administratorilor.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$comanda = Comenzi::dupaNumar(param('numar', '', $_GET));
if (!$comanda || !poate_vedea_comanda($comanda)) {
    http_response_code(404);
    $titlu = 'Comanda inexistenta';
    require DIR_SRC . '/sabloane/antet.php';
    echo '<h1>Comanda nu a fost gasita</h1><p>Verificati numarul comenzii sau folositi pagina de '
        . '<a href="' . e(url('urmarire.php')) . '">urmarire a comenzii</a>.</p>';
    require DIR_SRC . '/sabloane/subsol.php';
    exit;
}

$titlu = 'Confirmare comanda ' . $comanda['numar'];
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Va multumim pentru comanda!</h1>
<div class="banner">
    <h2>Numarul comenzii: <?php echo e($comanda['numar']); ?></h2>
    <p>Comanda a fost inregistrata. Un consultant TEMELIA va confirma telefonic disponibilitatea si termenul de livrare
       in maximum o zi lucratoare. Pastrati numarul comenzii pentru urmarire.</p>
    <?php if ($comanda['metoda_plata'] === 'card' && $comanda['stare_plata'] !== 'platita' && $comanda['status'] !== 'anulata'): ?>
        <p><strong>Plata cu cardul nu a fost inca finalizata.</strong>
        <a href="<?php echo e(url('plata.php', array('numar' => $comanda['numar']))); ?>" class="buton buton-principal">Plateste acum</a></p>
    <?php endif; ?>
</div>

<?php require DIR_SRC . '/sabloane/detalii_comanda.php'; ?>

<p class="fara-tiparire">
    <a href="<?php echo e(url('index.php')); ?>" class="buton">&laquo; Inapoi la magazin</a>
    <?php if (Autentificare::esteAutentificat()): ?><a href="<?php echo e(url('cont/comenzi.php')); ?>" class="buton">Comenzile mele</a><?php endif; ?>
    <small>Pentru a tipari confirmarea folositi comanda de tiparire a browserului (Ctrl+P).</small>
</p>
<?php require DIR_SRC . '/sabloane/subsol.php';
