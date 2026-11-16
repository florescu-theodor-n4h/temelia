<?php
/**
 * Componente de prezentare reutilizabile (fragmente XHTML): cardul de
 * produs, afisarea pretului cu reducere, starea stocului, stelele de
 * evaluare, erorile de formular si paginarea.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */

/**
 * Afiseaza pretul unui produs; daca exista reducere, pretul vechi este
 * taiat, iar procentul de reducere este evidentiat.
 *
 * @param array $p    Produsul.
 * @param bool  $mare Varianta mare (pagina produsului).
 * @return string XHTML
 */
function html_pret(array $p, $mare = false)
{
    $procent = Calculator::procentReducere($p['pret'], $p['pret_vechi']);
    $html = '';
    if ($procent > 0) {
        $html .= '<span class="pret-vechi">' . e(pret($p['pret_vechi'])) . '</span> '
            . '<span class="reducere">-' . $procent . '%</span><br />';
    }
    $html .= '<span class="' . ($mare ? 'pret-mare' : 'pret') . '">' . e(pret($p['pret'])) . '</span>';
    if ($mare) {
        $html .= ' <span class="tva">/ ' . e($p['unitate']) . ' &middot; TVA inclus</span>';
    }
    return $html;
}

/**
 * Afiseaza disponibilitatea in stoc.
 *
 * @param array $p
 * @return string XHTML
 */
function html_stoc(array $p)
{
    $stoc = (int) $p['stoc'];
    if ($stoc <= 0) {
        return '<span class="stoc-nu">Stoc epuizat</span>';
    }
    if ($stoc < 10) {
        return '<span class="stoc-putin">Stoc limitat (' . $stoc . ' ' . e($p['unitate']) . ')</span>';
    }
    return '<span class="stoc-da">In stoc</span>';
}

/**
 * Afiseaza nota medie sub forma de stele (caractere text, fara imagini).
 *
 * @param float $nota 0-5
 * @return string XHTML
 */
function html_stele($nota)
{
    $pline = (int) round($nota);
    return '<span class="stele" title="' . e(number_format($nota, 1, ',', '')) . ' din 5">'
        . str_repeat('&#9733;', $pline) . str_repeat('&#9734;', 5 - $pline) . '</span>';
}

/**
 * Cardul unui produs in grila de catalog.
 *
 * @param array $p
 * @return string XHTML
 */
function html_card_produs(array $p)
{
    $adresa = url('produs.php', array('p' => $p['slug']));
    $procent = Calculator::procentReducere($p['pret'], $p['pret_vechi']);
    $html = '<div class="produs-card"><div class="interior">';
    if ($procent > 0) {
        $html .= '<span class="eticheta-reducere">-' . $procent . '%</span>';
    }
    $html .= '<a href="' . e($adresa) . '"><img src="' . e(imagine_produs($p)) . '" width="140" height="105" alt="' . e($p['nume']) . '" /></a>'
        . '<a class="nume" href="' . e($adresa) . '">' . e($p['nume']) . '</a>'
        . '<div>' . html_pret($p) . '</div>'
        . '<div class="mic">' . html_stoc($p) . '</div>';
    if ((int) $p['stoc'] > 0) {
        $html .= '<form method="post" action="' . e(url('cos.php')) . '">' . csrf_camp()
            . '<input type="hidden" name="actiune" value="adauga" />'
            . '<input type="hidden" name="produs_id" value="' . (int) $p['id'] . '" />'
            . '<input type="hidden" name="cantitate" value="1" />'
            . '<input type="submit" class="buton buton-principal" value="Adauga in cos" /></form>';
    } else {
        $html .= '<input type="button" class="buton inactiv" value="Indisponibil" disabled="disabled" />';
    }
    return $html . '</div></div>';
}

/**
 * Grila completa de produse.
 *
 * @param array $produse
 * @return string XHTML
 */
function html_grila(array $produse)
{
    if (empty($produse)) {
        return '<div class="mesaj mesaj-info">Nu au fost gasite produse conform criteriilor selectate.</div>';
    }
    $html = '<div class="grila-produse">';
    foreach ($produse as $p) {
        $html .= html_card_produs($p);
    }
    return $html . '</div>';
}

/**
 * Lista erorilor de validare, afisata deasupra formularului.
 *
 * @param array $erori
 * @return string XHTML
 */
function html_erori(array $erori)
{
    if (empty($erori)) {
        return '';
    }
    $html = '<div class="mesaj mesaj-eroare">Va rugam sa corectati urmatoarele campuri:<ul>';
    foreach ($erori as $mesaj) {
        $html .= '<li>' . e($mesaj) . '</li>';
    }
    return $html . '</ul></div>';
}

/**
 * Mesajul de eroare al unui singur camp (afisat sub camp).
 *
 * @param array  $erori
 * @param string $camp
 * @return string XHTML
 */
function html_eroare_camp(array $erori, $camp)
{
    return isset($erori[$camp]) ? '<span class="eroare-camp">' . e($erori[$camp]) . '</span>' : '';
}

/**
 * Eticheta colorata a statusului unei comenzi.
 *
 * @param string $status
 * @return string XHTML
 */
function html_status($status)
{
    return '<span class="status status-' . e($status) . '">' . e(eticheta_status($status)) . '</span>';
}

/**
 * Bara de paginare pentru liste lungi.
 *
 * @param int    $paginaCurenta
 * @param int    $totalPagini
 * @param string $cale
 * @param array  $parametri Parametrii GET pastrati intre pagini.
 * @return string XHTML
 */
function html_paginare($paginaCurenta, $totalPagini, $cale, array $parametri)
{
    if ($totalPagini <= 1) {
        return '';
    }
    $html = '<div class="paginare">Pagina: ';
    for ($i = 1; $i <= $totalPagini; $i++) {
        if ($i === $paginaCurenta) {
            $html .= '<strong>' . $i . '</strong>';
        } else {
            $parametri['pagina'] = $i;
            $html .= '<a href="' . e(url($cale, $parametri)) . '">' . $i . '</a>';
        }
    }
    return $html . '</div>';
}

/**
 * Optiunile unui camp select.
 *
 * @param array  $optiuni  valoare => eticheta
 * @param string $selectat
 * @return string XHTML
 */
function html_optiuni(array $optiuni, $selectat)
{
    $html = '';
    foreach ($optiuni as $valoare => $eticheta) {
        $html .= '<option value="' . e($valoare) . '"' . ((string) $valoare === (string) $selectat ? ' selected="selected"' : '') . '>'
            . e($eticheta) . '</option>';
    }
    return $html;
}
