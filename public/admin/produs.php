<?php
/**
 * Adaugarea si editarea unui produs: date generale, pret si reducere,
 * stoc, imagine (incarcare validata), specificatii tehnice si accesorii
 * compatibile (pentru recomandari).
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
require __DIR__ . '/../../src/bootstrap.php';
Autentificare::cereAdmin();

$id = param_int('id');
$produs = $id ? Produse::dupaId($id) : null;
if ($id && !$produs) {
    flash('eroare', 'Produsul nu exista.');
    redirectioneaza('admin/produse.php');
}

$categorii = Produse::categorii();
$toateProdusele = Produse::toateAdmin();
$erori = array();

// Valorile formularului: din baza de date (editare) sau implicite (adaugare)
$date = array(
    'categorie_id' => $produs ? $produs['categorie_id'] : '',
    'cod_sku'      => $produs ? $produs['cod_sku'] : '',
    'nume'         => $produs ? $produs['nume'] : '',
    'slug'         => $produs ? $produs['slug'] : '',
    'descriere'    => $produs ? $produs['descriere'] : '',
    'pret'         => $produs ? number_format((float) $produs['pret'], 2, '.', '') : '',
    'pret_vechi'   => $produs && $produs['pret_vechi'] !== null ? number_format((float) $produs['pret_vechi'], 2, '.', '') : '',
    'stoc'         => $produs ? $produs['stoc'] : '0',
    'unitate'      => $produs ? $produs['unitate'] : 'buc',
    'greutate_kg'  => $produs ? $produs['greutate_kg'] : '1',
    'imagine'      => $produs ? $produs['imagine'] : '',
    'recomandat'   => $produs ? (bool) $produs['recomandat'] : false,
    'activ'        => $produs ? (bool) $produs['activ'] : true,
);
$specificatiiText = '';
if ($produs) {
    foreach (Produse::specificatii($id) as $s) {
        $specificatiiText .= $s['nume'] . ': ' . $s['valoare'] . "\n";
    }
}
$asociate = $produs ? Produse::asociate($id) : array();

if (este_post()) {
    csrf_verifica();
    foreach (array('categorie_id', 'cod_sku', 'nume', 'slug', 'descriere', 'pret', 'pret_vechi', 'stoc', 'unitate', 'greutate_kg') as $camp) {
        $date[$camp] = param($camp, '', $_POST);
    }
    $date['recomandat'] = !empty($_POST['recomandat']);
    $date['activ'] = !empty($_POST['activ']);
    $date['slug'] = slugifica($date['slug'] !== '' ? $date['slug'] : $date['nume']);
    $date['pret'] = str_replace(',', '.', $date['pret']);
    $date['pret_vechi'] = str_replace(',', '.', $date['pret_vechi']);
    $date['greutate_kg'] = str_replace(',', '.', $date['greutate_kg']);
    $specificatiiText = param('specificatii', '', $_POST);
    $asociate = isset($_POST['asociate']) && is_array($_POST['asociate']) ? array_map('intval', $_POST['asociate']) : array();

    $v = new Validator($_POST);
    $v->inLista('categorie_id', 'Raionul', array_map('strval', array_column($categorii, 'id')))
      ->obligatoriu('cod_sku', 'Codul produsului')->lungime('cod_sku', 'Codul produsului', 3, 40)
      ->obligatoriu('nume', 'Denumirea')->lungime('nume', 'Denumirea', 3, 200)
      ->lungime('descriere', 'Descrierea', 10, 5000)
      ->numar('pret', 'Pretul', 0.01, 1000000)
      ->intreg('stoc', 'Stocul', 0, 1000000)
      ->obligatoriu('unitate', 'Unitatea de vanzare')->lungime('unitate', 'Unitatea de vanzare', 1, 30)
      ->numar('greutate_kg', 'Greutatea', 0, 5000);
    if ($date['pret_vechi'] !== '') {
        $v->numar('pret_vechi', 'Pretul vechi', 0.01, 1000000);
        if (is_numeric($date['pret_vechi']) && is_numeric($date['pret']) && (float) $date['pret_vechi'] <= (float) $date['pret']) {
            $v->adaugaEroare('pret_vechi', 'Pretul vechi (inainte de reducere) trebuie sa fie mai mare decat pretul curent.');
        }
    }
    if ($date['slug'] === '') {
        $v->adaugaEroare('slug', 'Adresa (slug) nu poate fi goala.');
    }
    // Unicitatea codului si a adresei
    if (Baza::valoare('SELECT COUNT(*) FROM produse WHERE cod_sku = ? AND id <> ?', array($date['cod_sku'], $id)) > 0) {
        $v->adaugaEroare('cod_sku', 'Exista deja un produs cu acest cod.');
    }
    if (Baza::valoare('SELECT COUNT(*) FROM produse WHERE slug = ? AND id <> ?', array($date['slug'], $id)) > 0) {
        $v->adaugaEroare('slug', 'Exista deja un produs cu aceasta adresa (slug).');
    }

    // Incarcarea imaginii: verificare tip real (getimagesize), extensie si dimensiune
    $imagineNoua = null;
    if (isset($_FILES['imagine']) && $_FILES['imagine']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['imagine'];
        $tipuri = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
        $info = $f['error'] === UPLOAD_ERR_OK ? @getimagesize($f['tmp_name']) : false;
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $v->adaugaEroare('imagine', 'Imaginea nu a putut fi incarcata (cod eroare ' . (int) $f['error'] . ').');
        } elseif ($f['size'] > (int) config('imagine_max_octeti')) {
            $v->adaugaEroare('imagine', 'Imaginea depaseste dimensiunea maxima de 2 MB.');
        } elseif (!$info || !isset($tipuri[$info[2]])) {
            $v->adaugaEroare('imagine', 'Sunt acceptate doar imagini JPG, PNG sau GIF.');
        } else {
            $imagineNoua = array('tmp' => $f['tmp_name'], 'ext' => $tipuri[$info[2]]);
        }
    }

    if ($v->esteValid()) {
        if ($imagineNoua) {
            // Nume aleator: previne suprascrierea si executarea fisierelor incarcate
            $numeFisier = $date['slug'] . '-' . bin2hex(random_bytes(4)) . '.' . $imagineNoua['ext'];
            if (move_uploaded_file($imagineNoua['tmp'], DIR_PUBLIC . '/img/incarcate/' . $numeFisier)) {
                $date['imagine'] = $numeFisier;
            }
        }
        $date['pret'] = round((float) $date['pret'], 2);
        $date['pret_vechi'] = $date['pret_vechi'] !== '' ? round((float) $date['pret_vechi'], 2) : null;
        $date['greutate_kg'] = round((float) $date['greutate_kg'], 2);

        // Specificatii: cate una pe linie, in formatul "Denumire: Valoare"
        $specificatii = array();
        foreach (preg_split('/\r\n|\r|\n/', $specificatiiText) as $linie) {
            $parti = explode(':', $linie, 2);
            if (count($parti) === 2) {
                $specificatii[] = array('nume' => trim($parti[0]), 'valoare' => trim($parti[1]));
            }
        }

        $idSalvat = Baza::tranzactie(function () use ($id, $date, $specificatii, $asociate) {
            $idNou = Produse::salveaza($id, $date);
            Produse::salveazaSpecificatii($idNou, $specificatii);
            Produse::salveazaAsociate($idNou, $asociate);
            return $idNou;
        });
        flash('succes', $id ? 'Produsul a fost actualizat.' : 'Produsul a fost adaugat in catalog.');
        redirectioneaza('admin/produs.php', array('id' => $idSalvat));
    }
    $erori = $v->erori();
}

$titlu = $produs ? 'Editare produs' : 'Produs nou';
$sectiune = 'produse';
require DIR_SRC . '/sabloane/admin_antet.php';
?>
<h1><?php echo e($titlu); ?><?php echo $produs ? ': ' . e($produs['nume']) : ''; ?></h1>
<div class="unelte">
    <a href="<?php echo e(url('admin/produse.php')); ?>" class="buton">&laquo; Lista produse</a>
    <?php if ($produs): ?><a href="<?php echo e(url('produs.php', array('p' => $produs['slug']))); ?>" class="buton">Vezi in magazin</a><?php endif; ?>
</div>
<?php echo html_erori($erori); ?>

<form method="post" action="<?php echo e(url('admin/produs.php', $id ? array('id' => $id) : array())); ?>" enctype="multipart/form-data">
<?php echo csrf_camp(); ?>
<fieldset>
    <legend>Date generale</legend>
    <table class="formular">
        <tr><th>Raion <span class="obligatoriu">*</span></th><td><select name="categorie_id">
            <option value="">- selectati -</option>
            <?php foreach ($categorii as $c): ?><option value="<?php echo (int) $c['id']; ?>"<?php echo (string) $date['categorie_id'] === (string) $c['id'] ? ' selected="selected"' : ''; ?>><?php echo e($c['nume']); ?></option><?php endforeach; ?>
        </select><?php echo html_eroare_camp($erori, 'categorie_id'); ?></td></tr>
        <tr><th>Cod produs (SKU) <span class="obligatoriu">*</span></th><td><input type="text" name="cod_sku" value="<?php echo e($date['cod_sku']); ?>" class="text mediu" maxlength="40" /><?php echo html_eroare_camp($erori, 'cod_sku'); ?></td></tr>
        <tr><th>Denumire <span class="obligatoriu">*</span></th><td><input type="text" name="nume" value="<?php echo e($date['nume']); ?>" class="text lung" maxlength="200" /><?php echo html_eroare_camp($erori, 'nume'); ?></td></tr>
        <tr><th>Adresa (slug)</th><td><input type="text" name="slug" value="<?php echo e($date['slug']); ?>" class="text lung" maxlength="200" /><br /><small>Se genereaza automat din denumire daca ramane gol. Folosita in adresele SEO.</small><?php echo html_eroare_camp($erori, 'slug'); ?></td></tr>
        <tr><th>Descriere <span class="obligatoriu">*</span></th><td><textarea name="descriere" rows="6" cols="60" class="lung"><?php echo e($date['descriere']); ?></textarea><?php echo html_eroare_camp($erori, 'descriere'); ?></td></tr>
    </table>
</fieldset>

<fieldset>
    <legend>Pret, stoc si livrare</legend>
    <table class="formular">
        <tr><th>Pret cu TVA (lei) <span class="obligatoriu">*</span></th><td><input type="text" name="pret" value="<?php echo e($date['pret']); ?>" class="text scurt" /><?php echo html_eroare_camp($erori, 'pret'); ?></td></tr>
        <tr><th>Pret vechi (lei)</th><td><input type="text" name="pret_vechi" value="<?php echo e($date['pret_vechi']); ?>" class="text scurt" /> <small>completati pentru a afisa o reducere (pretul vechi apare taiat)</small><?php echo html_eroare_camp($erori, 'pret_vechi'); ?></td></tr>
        <tr><th>Stoc <span class="obligatoriu">*</span></th><td><input type="text" name="stoc" value="<?php echo e($date['stoc']); ?>" class="text scurt" /><?php echo html_eroare_camp($erori, 'stoc'); ?></td></tr>
        <tr><th>Unitate de vanzare <span class="obligatoriu">*</span></th><td><input type="text" name="unitate" value="<?php echo e($date['unitate']); ?>" class="text mediu" maxlength="30" /> <small>ex. buc, sac 40 kg, colac 100 m</small><?php echo html_eroare_camp($erori, 'unitate'); ?></td></tr>
        <tr><th>Greutate (kg / unitate) <span class="obligatoriu">*</span></th><td><input type="text" name="greutate_kg" value="<?php echo e($date['greutate_kg']); ?>" class="text scurt" /> <small>folosita la calculul tarifului de curierat</small><?php echo html_eroare_camp($erori, 'greutate_kg'); ?></td></tr>
        <tr><th>Optiuni</th><td>
            <label><input type="checkbox" name="activ" value="1"<?php echo $date['activ'] ? ' checked="checked"' : ''; ?> /> Activ (vizibil in magazin)</label><br />
            <label><input type="checkbox" name="recomandat" value="1"<?php echo $date['recomandat'] ? ' checked="checked"' : ''; ?> /> Recomandat pe prima pagina</label>
        </td></tr>
    </table>
</fieldset>

<fieldset>
    <legend>Imagine</legend>
    <?php if ($date['imagine']): ?><p><img src="<?php echo e(imagine_produs($date)); ?>" width="140" height="105" alt="" /><br /><small><?php echo e($date['imagine']); ?></small></p><?php endif; ?>
    <input type="file" name="imagine" /> <small>JPG, PNG sau GIF, maximum 2 MB. Lasati gol pentru a pastra imaginea curenta.</small>
    <?php echo html_eroare_camp($erori, 'imagine'); ?>
</fieldset>

<fieldset>
    <legend>Specificatii tehnice</legend>
    <textarea name="specificatii" rows="6" cols="60" class="lung"><?php echo e($specificatiiText); ?></textarea>
    <br /><small>Cate o specificatie pe linie, in formatul <strong>Denumire: Valoare</strong> (ex. "Putere: 750 W"). Folosite si in comparatorul de produse.</small>
</fieldset>

<fieldset>
    <legend>Accesorii compatibile / produse recomandate</legend>
    <select name="asociate[]" multiple="multiple" size="8" class="lung">
        <?php foreach ($toateProdusele as $p): if ((int) $p['id'] === $id) { continue; } ?>
        <option value="<?php echo (int) $p['id']; ?>"<?php echo in_array((int) $p['id'], $asociate, true) ? ' selected="selected"' : ''; ?>><?php echo e($p['cod_sku'] . ' - ' . $p['nume']); ?></option>
        <?php endforeach; ?>
    </select>
    <br /><small>Tineti apasata tasta Ctrl pentru selectie multipla. Produsele alese apar la "Accesorii si produse recomandate".</small>
</fieldset>

<p><input type="submit" class="buton buton-principal" value="Salveaza produsul" /></p>
</form>
<?php require DIR_SRC . '/sabloane/admin_subsol.php';
