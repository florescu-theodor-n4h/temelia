<?php
/**
 * Administrarea si cautarea codurilor de reducere (A8).
 * Regulile de valabilitate si calculul reducerii se afla in
 * Calculator::aplicaCupon(), pentru a putea fi testate automat.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Cupoane
{
    /**
     * Cauta un cupon dupa cod (fara a tine cont de majuscule).
     *
     * @param string $cod
     * @return array|null
     */
    public static function dupaCod($cod)
    {
        return Baza::unul('SELECT * FROM cupoane WHERE cod = ?', array(strtoupper(trim($cod))));
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function dupaId($id)
    {
        return Baza::unul('SELECT * FROM cupoane WHERE id = ?', array((int) $id));
    }

    /**
     * Toate cupoanele, pentru panoul de administrare.
     *
     * @return array
     */
    public static function toate()
    {
        return Baza::toate('SELECT * FROM cupoane ORDER BY activ DESC, cod');
    }

    /**
     * Adauga sau actualizeaza un cupon.
     *
     * @param int   $id   0 pentru cupon nou.
     * @param array $date Campurile validate.
     * @return void
     */
    public static function salveaza($id, array $date)
    {
        $valori = array(
            'cod'           => strtoupper($date['cod']),
            'descriere'     => $date['descriere'],
            'tip'           => $date['tip'],
            'valoare'       => $date['valoare'],
            'minim_comanda' => $date['minim_comanda'],
            'valabil_pana'  => $date['valabil_pana'] !== '' ? $date['valabil_pana'] : null,
            'utilizari_max' => $date['utilizari_max'] !== '' ? (int) $date['utilizari_max'] : null,
            'activ'         => $date['activ'] ? 1 : 0,
        );
        if ($id > 0) {
            $valori['id'] = (int) $id;
            Baza::executa(
                'UPDATE cupoane SET cod = ?, descriere = ?, tip = ?, valoare = ?, minim_comanda = ?,
                        valabil_pana = ?, utilizari_max = ?, activ = ? WHERE id = ?',
                array_values($valori)
            );
            return;
        }
        $valori['utilizari'] = 0;
        Baza::insereaza('cupoane', $valori);
    }

    /**
     * @param int $id
     * @return void
     */
    public static function sterge($id)
    {
        Baza::executa('DELETE FROM cupoane WHERE id = ?', array((int) $id));
    }
}
