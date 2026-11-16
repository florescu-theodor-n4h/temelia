<?php
/**
 * Cosul de cumparaturi: adaugare, modificarea cantitatilor, eliminare,
 * aplicarea unui cod de reducere si afisarea totalurilor.
 *
 * Toate modificarile se fac prin POST (cu jeton CSRF), urmate de o
 * redirectionare (modelul Post/Redirect/Get), pentru ca reincarcarea
 * paginii sa nu repete operatia.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

if (este_post()) {
    csrf_verifica();
    $actiune = param('actiune');

    switch ($actiune) {
        case 'adauga':
            $cantitate = max(1, min(Cos::CANTITATE_MAXIMA, param_int('cantitate', 1)));
            $mesaj = Cos::adauga(param_int('produs_id'), $cantitate);
            if ($mesaj === 'Produsul nu exista.' || $mesaj === 'Produsul nu este disponibil in stoc.') {
                flash('eroare', $mesaj);
            } else {
                flash($mesaj ? 'info' : 'succes', $mesaj ? $mesaj : 'Produsul a fost adaugat in cos.');
            }
            break;

        case 'actualizeaza':
            $cantitati = isset($_POST['cantitate']) && is_array($_POST['cantitate']) ? $_POST['cantitate'] : array();
            foreach ($cantitati as $id => $cant) {
                if (preg_match('/^\d+$/', (string) $cant)) {
                    Cos::modifica((int) $id, (int) $cant);
                }
            }
            flash('succes', 'Cosul a fost actualizat.');
            break;

        case 'elimina':
            Cos::elimina(param_int('produs_id'));
            flash('succes', 'Produsul a fost eliminat din cos.');
            break;

        case 'cupon':
            $cod = strtoupper(param('cod_cupon'));
            $rezultat = Calculator::aplicaCupon($cod !== '' ? Cupoane::dupaCod($cod) : null, Calculator::subtotal(Cos::linii()));
            if ($rezultat['valid']) {
                $_SESSION['cupon'] = $cod;
                flash('succes', $rezultat['mesaj'] . ' Reducere: ' . pret($rezultat['reducere']) . '.');
            } else {
                unset($_SESSION['cupon']);
                flash('eroare', $rezultat['mesaj']);
            }
            break;

        case 'sterge_cupon':
            unset($_SESSION['cupon']);
            flash('info', 'Codul de reducere a fost eliminat.');
            break;
    }
    redirectioneaza('cos.php');
}

$linii = Cos::linii();
$rezumat = Cos::rezumat($linii);
if ($rezumat['mesaj_cupon'] !== '') {
    flash('eroare', $rezumat['mesaj_cupon']);
}
$titlu = 'Cosul de cumparaturi';
$cale = array(array($titlu, ''));

require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Cosul de cumparaturi</h1>

<?php if (empty($linii)): ?>
    <div class="mesaj mesaj-info">Cosul dumneavoastra este gol.</div>
    <p><a href="<?php echo e(url('catalog.php')); ?>" class="buton buton-principal">Continuati cumparaturile</a></p>
<?php else: ?>
<form method="post" action="<?php echo e(url('cos.php')); ?>">
    <?php echo csrf_camp(); ?>
    <input type="hidden" name="actiune" value="actualizeaza" />
    <div class="tabel-derulant">
    <table class="date">
        <thead>
            <tr><th></th><th>Produs</th><th class="numar">Pret unitar</th><th>Cantitate</th><th class="numar">Total</th></tr>
        </thead>
        <tbody>
        <?php foreach ($linii as $i => $l): $p = $l['produs']; ?>
            <tr<?php echo $i % 2 ? ' class="impar"' : ''; ?>>
                <td><img src="<?php echo e(imagine_produs($p)); ?>" width="50" height="38" alt="" /></td>
                <td>
                    <a href="<?php echo e(url('produs.php', array('p' => $p['slug']))); ?>"><?php echo e($p['nume']); ?></a><br />
                    <small>Cod: <?php echo e($p['cod_sku']); ?></small>
                    <?php if ($l['avertizare']): ?><br /><span class="stoc-nu"><?php echo e($l['avertizare']); ?></span><?php endif; ?>
                </td>
                <td class="numar"><?php echo e(pret($l['pret'])); ?></td>
                <td><input type="text" name="cantitate[<?php echo (int) $p['id']; ?>]" value="<?php echo (int) $l['cantitate']; ?>" class="text scurt" maxlength="3" /> <small><?php echo e($p['unitate']); ?></small></td>
                <td class="numar"><strong><?php echo e(pret($l['total'])); ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p><input type="submit" class="buton" value="Actualizeaza cantitatile" /> <small>Pentru a elimina un produs, setati cantitatea 0 sau folositi butoanele de mai jos.</small></p>
</form>

<p class="mic">Eliminare rapida:
<?php foreach ($linii as $l): ?>
    <form method="post" action="<?php echo e(url('cos.php')); ?>" class="in-linie">
        <?php echo csrf_camp(); ?>
        <input type="hidden" name="actiune" value="elimina" />
        <input type="hidden" name="produs_id" value="<?php echo (int) $l['produs']['id']; ?>" />
        <input type="submit" class="buton" value="x <?php echo e($l['produs']['cod_sku']); ?>" title="Elimina <?php echo e($l['produs']['nume']); ?>" />
    </form>
<?php endforeach; ?>
</p>

<div class="caseta">
    <div class="titlu">Cod de reducere</div>
    <div class="corp">
        <?php if ($rezumat['cupon']): ?>
            Cod aplicat: <strong><?php echo e($rezumat['cupon']['cod']); ?></strong> - <?php echo e($rezumat['cupon']['descriere']); ?>
            <form method="post" action="<?php echo e(url('cos.php')); ?>" class="in-linie">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="sterge_cupon" />
                <input type="submit" class="buton" value="Elimina codul" />
            </form>
        <?php else: ?>
            <form method="post" action="<?php echo e(url('cos.php')); ?>">
                <?php echo csrf_camp(); ?>
                <input type="hidden" name="actiune" value="cupon" />
                <input type="text" name="cod_cupon" class="text mediu" maxlength="30" />
                <input type="submit" class="buton" value="Aplica" />
            </form>
        <?php endif; ?>
    </div>
</div>

<table class="date">
    <tr><td>Subtotal produse</td><td class="numar"><?php echo e(pret($rezumat['subtotal'])); ?></td></tr>
    <?php if ($rezumat['reducere'] > 0): ?>
    <tr><td>Reducere (<?php echo e($rezumat['cupon']['cod']); ?>)</td><td class="numar">- <?php echo e(pret($rezumat['reducere'])); ?></td></tr>
    <?php endif; ?>
    <tr><td>Livrare prin curier <small>(estimare; costul final se calculeaza la finalizare, in functie de judet si metoda de plata)</small></td>
        <td class="numar"><?php echo $rezumat['cost_livrare'] > 0 ? e(pret($rezumat['cost_livrare'])) : 'GRATUIT'; ?></td></tr>
    <tfoot>
        <tr><td>TOTAL ESTIMAT <small>(din care TVA: <?php echo e(pret($rezumat['tva'])); ?>)</small></td><td class="numar pret"><?php echo e(pret($rezumat['total'])); ?></td></tr>
    </tfoot>
</table>

<p>
    <a href="<?php echo e(url('catalog.php')); ?>" class="buton">&laquo; Continuati cumparaturile</a>
    <?php if (Cos::stocSuficient($linii)): ?>
        <a href="<?php echo e(url('finalizare.php')); ?>" class="buton buton-principal">Finalizeaza comanda &raquo;</a>
    <?php else: ?>
        <span class="stoc-nu">Ajustati cantitatile marcate pentru a putea finaliza comanda.</span>
    <?php endif; ?>
</p>
<?php endif; ?>
<?php require DIR_SRC . '/sabloane/subsol.php';
