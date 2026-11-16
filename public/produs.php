<?php
/**
 * Pagina de detaliu a unui produs: imagine, descriere, pret cu TVA,
 * reducere, stoc, specificatii tehnice, adaugare in cos, favorite,
 * comparare, accesorii recomandate (A6) si recenzii.
 *
 * Adresa: produs.php?p={slug} (adresa prietenoasa pentru motoarele de cautare).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$produs = param('p', '', $_GET) !== '' ? Produse::dupaSlug(param('p', '', $_GET)) : Produse::dupaId(param_int('id'));
if (!$produs || !$produs['activ']) {
    http_response_code(404);
    $titlu = 'Produs inexistent';
    require DIR_SRC . '/sabloane/antet.php';
    echo '<h1>Produsul cautat nu exista</h1><p>Este posibil ca produsul sa fi fost retras din oferta. '
        . '<a href="' . e(url('catalog.php')) . '">Inapoi la catalog</a></p>';
    require DIR_SRC . '/sabloane/subsol.php';
    exit;
}

$utilizator = Autentificare::utilizator();
$adresaProdus = url('produs.php', array('p' => $produs['slug']));
$eroriRecenzie = array();

// Actiunile trimise prin formular (toate protejate cu jeton CSRF)
if (este_post()) {
    csrf_verifica();
    $actiune = param('actiune');

    if ($actiune === 'favorit') {
        if (!$utilizator) {
            flash('info', 'Autentificati-va pentru a salva produse in lista de favorite.');
            redirectioneaza('cont/autentificare.php', array('inapoi' => $adresaProdus));
        }
        $adaugat = Favorite::comuta($utilizator['id'], $produs['id']);
        flash('succes', $adaugat ? 'Produsul a fost adaugat la favorite.' : 'Produsul a fost eliminat din favorite.');
        header('Location: ' . $adresaProdus);
        exit;
    }

    if ($actiune === 'comparare') {
        $eroare = Favorite::adaugaComparare($produs['id']);
        flash($eroare ? 'eroare' : 'succes', $eroare ? $eroare : 'Produsul a fost adaugat in lista de comparare.');
        header('Location: ' . $adresaProdus);
        exit;
    }

    if ($actiune === 'recenzie') {
        if (!$utilizator || !Comenzi::aCumparat($utilizator['id'], $produs['id'])) {
            flash('eroare', 'Doar clientii care au cumparat produsul pot adauga o recenzie.');
            header('Location: ' . $adresaProdus);
            exit;
        }
        $v = new Validator($_POST);
        $v->intreg('nota', 'Nota', 1, 5)->lungime('comentariu', 'Comentariul', 5, 1000);
        if ($v->esteValid()) {
            Favorite::salveazaRecenzie($utilizator['id'], $produs['id'], (int) $v->valoare('nota'), $v->valoare('comentariu'));
            flash('succes', 'Va multumim pentru recenzie!');
            header('Location: ' . $adresaProdus . '#recenzii');
            exit;
        }
        $eroriRecenzie = $v->erori();
    }
}

$specificatii = Produse::specificatii($produs['id']);
$recomandari = Produse::recomandari($produs, 4);
$recenzii = Produse::recenzii($produs['id']);
$esteFavorit = $utilizator ? Favorite::contine($utilizator['id'], $produs['id']) : false;
$poateEvalua = $utilizator ? Comenzi::aCumparat($utilizator['id'], $produs['id']) : false;

$titlu = $produs['nume'];
$descriereMeta = function_exists('mb_substr') ? mb_substr($produs['descriere'], 0, 155, 'UTF-8') : substr($produs['descriere'], 0, 155);
$adresaCanonica = rtrim((string) config('url_baza'), '/') !== '' ? rtrim(config('url_baza'), '/') . '/produs.php?p=' . rawurlencode($produs['slug']) : '';
$categorieActiva = $produs['categorie_id'];
$cale = array(
    array($produs['categorie_nume'], url('catalog.php', array('categorie' => $produs['categorie_id']))),
    array($produs['nume'], ''),
);

require DIR_SRC . '/sabloane/antet.php';
?>
<div class="produs-detalii">
    <div class="imagine">
        <img src="<?php echo e(imagine_produs($produs)); ?>" width="300" height="225" alt="<?php echo e($produs['nume']); ?>" />
    </div>
    <div class="informatii">
        <h1><?php echo e($produs['nume']); ?></h1>
        <p class="mic">Cod produs: <strong><?php echo e($produs['cod_sku']); ?></strong> &middot;
            Raion: <a href="<?php echo e(url('catalog.php', array('categorie' => $produs['categorie_id']))); ?>"><?php echo e($produs['categorie_nume']); ?></a>
            <?php if ($recenzii['numar']): ?> &middot; <?php echo html_stele($recenzii['medie']); ?> (<?php echo (int) $recenzii['numar']; ?> recenzii)<?php endif; ?>
        </p>

        <div class="caseta-cumparare">
            <?php echo html_pret($produs, true); ?><br />
            Disponibilitate: <?php echo html_stoc($produs); ?><br />
            <?php if ((int) $produs['stoc'] > 0): ?>
            <form method="post" action="<?php echo e(url('cos.php')); ?>">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="adauga" />
                <input type="hidden" name="produs_id" value="<?php echo (int) $produs['id']; ?>" />
                <label>Cantitate (<?php echo e($produs['unitate']); ?>):
                    <input type="text" name="cantitate" value="1" class="text scurt" maxlength="4" />
                </label>
                <input type="submit" class="buton buton-principal" value="Adauga in cos" />
            </form>
            <?php else: ?>
            <p><strong>Produsul nu este momentan disponibil.</strong> Va rugam sa reveniti sau sa ne contactati pentru termenul de reaprovizionare.</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo e($adresaProdus); ?>" class="in-linie">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="actiune" value="favorit" />
            <input type="submit" class="buton" value="<?php echo $esteFavorit ? 'Elimina din favorite' : 'Adauga la favorite'; ?>" />
        </form>
        <form method="post" action="<?php echo e($adresaProdus); ?>" class="in-linie">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="actiune" value="comparare" />
            <input type="submit" class="buton" value="Compara" />
        </form>
        <br /><br />

        <h2>Descriere</h2>
        <p><?php echo nl2br(e($produs['descriere'])); ?></p>
    </div>
</div>

<?php if ($specificatii): ?>
<div class="caseta">
    <div class="titlu">Specificatii tehnice</div>
    <div class="corp">
        <table class="date">
            <?php foreach ($specificatii as $i => $s): ?>
            <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>><th class="col-eticheta"><?php echo e($s['nume']); ?></th><td><?php echo e($s['valoare']); ?></td></tr>
            <?php endforeach; ?>
            <tr><th>Greutate de transport</th><td><?php echo e(number_format((float) $produs['greutate_kg'], 2, ',', '.')); ?> kg / <?php echo e($produs['unitate']); ?></td></tr>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($recomandari): ?>
<div class="caseta">
    <div class="titlu">Accesorii si produse recomandate</div>
    <div class="corp"><?php echo html_grila($recomandari); ?></div>
</div>
<?php endif; ?>

<div class="caseta" id="recenzii">
    <div class="titlu">Recenzii clienti</div>
    <div class="corp">
        <?php if (!$recenzii['numar']): ?>
            <p>Acest produs nu are inca recenzii.</p>
        <?php else: ?>
            <p>Nota medie: <?php echo html_stele($recenzii['medie']); ?> <strong><?php echo e(number_format($recenzii['medie'], 1, ',', '')); ?></strong> din 5</p>
            <?php foreach ($recenzii['lista'] as $r): ?>
            <hr />
            <p><?php echo html_stele($r['nota']); ?> <strong><?php echo e($r['autor']); ?></strong> <small><?php echo e(data_ro($r['creat_la'], false)); ?></small><br />
            <?php echo nl2br(e($r['comentariu'])); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($poateEvalua): ?>
        <hr />
        <h3>Adaugati recenzia dumneavoastra</h3>
        <?php echo html_erori($eroriRecenzie); ?>
        <form method="post" action="<?php echo e($adresaProdus); ?>#recenzii">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="actiune" value="recenzie" />
            <table class="formular">
                <tr><th>Nota:</th><td><select name="nota"><?php echo html_optiuni(array(5 => '5 - Excelent', 4 => '4 - Foarte bun', 3 => '3 - Bun', 2 => '2 - Satisfacator', 1 => '1 - Slab'), param('nota', '5')); ?></select></td></tr>
                <tr><th>Comentariu:</th><td><textarea name="comentariu" rows="4" cols="50" class="lung"><?php echo e(param('comentariu')); ?></textarea></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Trimite recenzia" /></td></tr>
            </table>
        </form>
        <?php elseif (!$utilizator): ?>
        <p class="mic">Recenziile pot fi adaugate de clientii autentificati care au cumparat produsul.</p>
        <?php endif; ?>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
