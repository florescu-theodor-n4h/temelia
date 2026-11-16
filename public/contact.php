<?php
/**
 * Pagina de contact: datele de identificare ale firmei si formular de
 * mesaj (validat pe server; mesajele sunt inregistrate in jurnal).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../src/bootstrap.php';

$erori = array();
if (este_post()) {
    csrf_verifica();
    $v = new Validator($_POST);
    $v->obligatoriu('nume', 'Numele')->lungime('nume', 'Numele', 3, 120)
      ->email('email', 'Adresa de e-mail')
      ->lungime('mesaj', 'Mesajul', 10, 2000);
    if ($v->esteValid()) {
        jurnal('Mesaj contact de la ' . $v->valoare('email') . ': ' . str_replace(array("\r", "\n"), ' ', $v->valoare('mesaj')));
        flash('succes', 'Mesajul a fost trimis. Va vom raspunde in cel mult o zi lucratoare.');
        redirectioneaza('contact.php');
    }
    $erori = $v->erori();
}

$titlu = 'Contact';
$cale = array(array($titlu, ''));
require DIR_SRC . '/sabloane/antet.php';
?>
<h1>Contact</h1>
<div id="coloana-stanga">
    <div class="caseta">
        <div class="titlu">Date firma</div>
        <div class="corp">
            <strong><?php echo e(config('firma.nume')); ?></strong><br />
            CUI: <?php echo e(config('firma.cui')); ?><br />
            Reg. Com.: <?php echo e(config('firma.reg_com')); ?><br /><br />
            <strong>Sediu si depozit:</strong><br /><?php echo e(config('firma.adresa')); ?><br /><br />
            <strong>Telefon:</strong> <?php echo e(config('firma.telefon')); ?><br />
            <strong>E-mail:</strong> <?php echo e(config('firma.email')); ?><br /><br />
            <strong>Program:</strong><br /><?php echo e(config('firma.program')); ?>
        </div>
    </div>
</div>
<div id="coloana-principala">
    <?php echo html_erori($erori); ?>
    <div class="caseta">
        <div class="titlu">Trimiteti-ne un mesaj</div>
        <div class="corp">
            <form method="post" action="<?php echo e(url('contact.php')); ?>">
                <?php echo csrf_camp(); ?>
                <table class="formular">
                    <tr><th>Nume <span class="obligatoriu">*</span></th><td><input type="text" name="nume" value="<?php echo e(param('nume', '', $_POST)); ?>" class="text mediu" maxlength="120" /></td></tr>
                    <tr><th>E-mail <span class="obligatoriu">*</span></th><td><input type="text" name="email" value="<?php echo e(param('email', '', $_POST)); ?>" class="text mediu" maxlength="160" /></td></tr>
                    <tr><th>Mesaj <span class="obligatoriu">*</span></th><td><textarea name="mesaj" rows="6" cols="50" class="lung"><?php echo e(param('mesaj', '', $_POST)); ?></textarea></td></tr>
                    <tr><th></th><td><input type="submit" class="buton buton-principal" value="Trimite mesajul" /></td></tr>
                </table>
            </form>
        </div>
    </div>
</div>
<?php require DIR_SRC . '/sabloane/subsol.php';
