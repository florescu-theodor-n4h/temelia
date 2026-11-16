<?php
/**
 * Autentificarea clientilor si a administratorilor.
 *
 * Masuri de securitate: jeton CSRF, mesaj de eroare generic (nu dezvaluie
 * daca adresa de e-mail exista), limitarea incercarilor esuate per sesiune
 * si regenerarea identificatorului de sesiune la autentificare.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

if (Autentificare::esteAutentificat()) {
    redirectioneaza('cont/comenzi.php');
}

$inapoi = adresa_locala(param('inapoi'));
$eroare = '';
$email = '';

// Limitare simpla: dupa 5 incercari esuate, asteptare 5 minute
$incercari = isset($_SESSION['autentificare_esuata']) ? $_SESSION['autentificare_esuata'] : array('numar' => 0, 'moment' => 0);
$blocat = $incercari['numar'] >= 5 && (time() - $incercari['moment']) < 300;

if (este_post()) {
    csrf_verifica();
    $email = param('email', '', $_POST);
    if ($blocat) {
        $eroare = 'Prea multe incercari esuate. Va rugam sa asteptati 5 minute.';
    } elseif (Autentificare::autentifica($email, isset($_POST['parola']) ? (string) $_POST['parola'] : '')) {
        unset($_SESSION['autentificare_esuata']);
        flash('succes', 'Autentificare reusita. Bine ati revenit!');
        if (Autentificare::esteAdmin() && strpos($inapoi, '/admin/') === false && $inapoi === url('index.php')) {
            redirectioneaza('admin/index.php');
        }
        header('Location: ' . $inapoi);
        exit;
    } else {
        $_SESSION['autentificare_esuata'] = array('numar' => $incercari['numar'] + 1, 'moment' => time());
        $eroare = 'Adresa de e-mail sau parola sunt incorecte.';
    }
}

$titlu = 'Autentificare';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Autentificare client</h1>
<?php if ($eroare): ?><div class="mesaj mesaj-eroare"><?php echo e($eroare); ?></div><?php endif; ?>
<div class="caseta">
    <div class="titlu">Intrati in contul dumneavoastra</div>
    <div class="corp">
        <form method="post" action="<?php echo e(url('cont/autentificare.php')); ?>">
            <?php echo csrf_camp(); ?>
            <input type="hidden" name="inapoi" value="<?php echo e($inapoi); ?>" />
            <table class="formular">
                <tr><th>E-mail</th><td><input type="text" name="email" value="<?php echo e($email); ?>" class="text mediu" maxlength="160" /></td></tr>
                <tr><th>Parola</th><td><input type="password" name="parola" class="text mediu" maxlength="72" /></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Autentificare" /></td></tr>
            </table>
        </form>
        <p>Nu aveti cont? <a href="<?php echo e(url('cont/inregistrare.php')); ?>">Creati un cont nou</a> in mai putin de un minut.</p>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
