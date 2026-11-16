<?php
/**
 * Inregistrarea unui cont nou de client. Parola este salvata exclusiv
 * ca hash (password_hash), iar rolul atribuit este intotdeauna 'client'.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';

if (Autentificare::esteAutentificat()) {
    redirectioneaza('cont/comenzi.php');
}

$erori = array();
$date = array('nume' => '', 'email' => '', 'telefon' => '');

if (este_post()) {
    csrf_verifica();
    foreach ($date as $camp => $implicit) {
        $date[$camp] = param($camp, '', $_POST);
    }
    $v = new Validator($_POST);
    $v->obligatoriu('nume', 'Numele')->lungime('nume', 'Numele', 3, 120)
      ->obligatoriu('email', 'Adresa de e-mail')->email('email', 'Adresa de e-mail')
      ->telefon('telefon', 'Numarul de telefon')
      ->lungime('parola', 'Parola', 8, 72)
      ->identic('parola', 'parola2', 'Parolele introduse nu coincid.')
      ->bifat('accept', 'Trebuie sa acceptati Termenii si conditiile si Politica de confidentialitate.');

    $parola = isset($_POST['parola']) ? (string) $_POST['parola'] : '';
    if (!preg_match('/[A-Za-z]/', $parola) || !preg_match('/\d/', $parola)) {
        $v->adaugaEroare('parola', 'Parola trebuie sa contina cel putin o litera si o cifra.');
    }

    if ($v->esteValid()) {
        try {
            Autentificare::inregistreaza($date['nume'], $date['email'], $parola, $date['telefon']);
            Autentificare::autentifica($date['email'], $parola);
            flash('succes', 'Contul a fost creat. Bine ati venit la TEMELIA!');
            redirectioneaza('index.php');
        } catch (RuntimeException $ex) {
            $v->adaugaEroare('email', $ex->getMessage());
        }
    }
    $erori = $v->erori();
}

$titlu = 'Cont nou';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Creare cont nou</h1>
<?php echo html_erori($erori); ?>
<div class="caseta">
    <div class="titlu">Date cont</div>
    <div class="corp">
        <form method="post" action="<?php echo e(url('cont/inregistrare.php')); ?>">
            <?php echo csrf_camp(); ?>
            <table class="formular">
                <tr><th>Nume complet <span class="obligatoriu">*</span></th><td><input type="text" name="nume" value="<?php echo e($date['nume']); ?>" class="text mediu" maxlength="120" /><?php echo html_eroare_camp($erori, 'nume'); ?></td></tr>
                <tr><th>E-mail <span class="obligatoriu">*</span></th><td><input type="text" name="email" value="<?php echo e($date['email']); ?>" class="text mediu" maxlength="160" /><?php echo html_eroare_camp($erori, 'email'); ?></td></tr>
                <tr><th>Telefon <span class="obligatoriu">*</span></th><td><input type="text" name="telefon" value="<?php echo e($date['telefon']); ?>" class="text mediu" maxlength="20" /><?php echo html_eroare_camp($erori, 'telefon'); ?></td></tr>
                <tr><th>Parola <span class="obligatoriu">*</span></th><td><input type="password" name="parola" class="text mediu" maxlength="72" /> <small>minimum 8 caractere, litere si cifre</small><?php echo html_eroare_camp($erori, 'parola'); ?></td></tr>
                <tr><th>Confirmare parola <span class="obligatoriu">*</span></th><td><input type="password" name="parola2" class="text mediu" maxlength="72" /><?php echo html_eroare_camp($erori, 'parola2'); ?></td></tr>
                <tr><th></th><td><label><input type="checkbox" name="accept" value="1" /> Accept <a href="<?php echo e(url('termeni.php')); ?>">Termenii si conditiile</a> si <a href="<?php echo e(url('confidentialitate.php')); ?>">Politica de confidentialitate</a></label><?php echo html_eroare_camp($erori, 'accept'); ?></td></tr>
                <tr><th></th><td><input type="submit" class="buton buton-principal" value="Creeaza contul" /></td></tr>
            </table>
        </form>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
