<?php
/**
 * Lista de favorite (pastrata in baza de date, pentru clientii
 * autentificati) si comparatorul de produse (pastrat in sesiune,
 * disponibil si vizitatorilor) - functionalitatea avansata A9.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Favorite
{
    /** Numarul maxim de produse comparate simultan. */
    const MAXIM_COMPARARE = 4;

    /**
     * Adauga sau elimina un produs din lista de favorite.
     *
     * @param int $utilizatorId
     * @param int $produsId
     * @return bool True daca produsul a fost adaugat, false daca a fost eliminat.
     */
    public static function comuta($utilizatorId, $produsId)
    {
        if (self::contine($utilizatorId, $produsId)) {
            Baza::executa('DELETE FROM favorite WHERE utilizator_id = ? AND produs_id = ?', array((int) $utilizatorId, (int) $produsId));
            return false;
        }
        Baza::executa('INSERT INTO favorite (utilizator_id, produs_id, adaugat_la) VALUES (?, ?, ?)',
            array((int) $utilizatorId, (int) $produsId, Baza::acum()));
        return true;
    }

    /**
     * @param int $utilizatorId
     * @param int $produsId
     * @return bool
     */
    public static function contine($utilizatorId, $produsId)
    {
        return (int) Baza::valoare('SELECT COUNT(*) FROM favorite WHERE utilizator_id = ? AND produs_id = ?',
            array((int) $utilizatorId, (int) $produsId)) > 0;
    }

    /**
     * Produsele favorite ale unui client.
     *
     * @param int $utilizatorId
     * @return array
     */
    public static function lista($utilizatorId)
    {
        return Baza::toate(
            'SELECT p.* FROM favorite f JOIN produse p ON p.id = f.produs_id
              WHERE f.utilizator_id = ? AND p.activ = 1 ORDER BY f.adaugat_la DESC',
            array((int) $utilizatorId)
        );
    }

    /* -------------------------------------------------------------- */
    /*  Comparator de produse (sesiune)                                */
    /* -------------------------------------------------------------- */

    /**
     * Identificatorii produselor din comparator.
     *
     * @return array
     */
    public static function deComparat()
    {
        return isset($_SESSION['comparare']) && is_array($_SESSION['comparare']) ? $_SESSION['comparare'] : array();
    }

    /**
     * Adauga un produs in comparator.
     *
     * @param int $produsId
     * @return string Mesaj de eroare sau sir gol.
     */
    public static function adaugaComparare($produsId)
    {
        $lista = self::deComparat();
        if (in_array((int) $produsId, $lista, true)) {
            return '';
        }
        if (count($lista) >= self::MAXIM_COMPARARE) {
            return 'Puteti compara cel mult ' . self::MAXIM_COMPARARE . ' produse simultan.';
        }
        $lista[] = (int) $produsId;
        $_SESSION['comparare'] = $lista;
        return '';
    }

    /**
     * Elimina un produs din comparator.
     *
     * @param int $produsId
     * @return void
     */
    public static function eliminaComparare($produsId)
    {
        $_SESSION['comparare'] = array_values(array_diff(self::deComparat(), array((int) $produsId)));
    }

    /**
     * Construieste tabelul de comparatie: produsele si reuniunea
     * denumirilor de specificatii ale acestora.
     *
     * @return array array('produse' => array, 'caracteristici' => array, 'valori' => array)
     */
    public static function tabelComparatie()
    {
        $produse = Produse::dupaIduri(self::deComparat());
        $caracteristici = array();
        $valori = array();
        foreach ($produse as $id => $p) {
            foreach (Produse::specificatii($id) as $s) {
                if (!in_array($s['nume'], $caracteristici, true)) {
                    $caracteristici[] = $s['nume'];
                }
                $valori[$id][$s['nume']] = $s['valoare'];
            }
        }
        return array('produse' => $produse, 'caracteristici' => $caracteristici, 'valori' => $valori);
    }

    /* -------------------------------------------------------------- */
    /*  Recenzii                                                       */
    /* -------------------------------------------------------------- */

    /**
     * Salveaza recenzia unui client (o singura recenzie per produs;
     * o noua trimitere o inlocuieste pe cea existenta).
     *
     * @param int    $utilizatorId
     * @param int    $produsId
     * @param int    $nota 1-5
     * @param string $comentariu
     * @return void
     */
    public static function salveazaRecenzie($utilizatorId, $produsId, $nota, $comentariu)
    {
        Baza::tranzactie(function () use ($utilizatorId, $produsId, $nota, $comentariu) {
            Baza::executa('DELETE FROM recenzii WHERE utilizator_id = ? AND produs_id = ?', array((int) $utilizatorId, (int) $produsId));
            Baza::insereaza('recenzii', array(
                'produs_id'     => (int) $produsId,
                'utilizator_id' => (int) $utilizatorId,
                'nota'          => max(1, min(5, (int) $nota)),
                'comentariu'    => $comentariu,
                'creat_la'      => Baza::acum(),
            ));
        });
    }
}
