<?php
/**
 * Depozitul de date pentru catalogul de produse: cautare, filtrare,
 * sortare, specificatii, recomandari si administrare.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Produse
{
    /**
     * Criteriile de sortare permise (protectie: valoarea din URL nu este
     * niciodata concatenata direct in SQL, ci doar cheile acestei liste).
     *
     * @return array cod => array(clauza SQL, eticheta)
     */
    public static function sortari()
    {
        return array(
            'recomandate' => array('p.recomandat DESC, p.nume ASC', 'Recomandate'),
            'pret_asc'    => array('p.pret ASC, p.id ASC',          'Pret crescator'),
            'pret_desc'   => array('p.pret DESC, p.id ASC',         'Pret descrescator'),
            'noi'         => array('p.creat_la DESC, p.id DESC',    'Cele mai noi'),
            'nume'        => array('p.nume ASC',                    'Denumire (A-Z)'),
        );
    }

    /**
     * Cauta produsele active conform filtrelor primite.
     *
     * Filtre acceptate: cautare (text), categorie (id), pret_min, pret_max,
     * sortare (cheie din sortari()), doar_stoc (bool), doar_reduceri (bool).
     *
     * @param array $filtre
     * @param int   $limita
     * @param int   $deplasare
     * @return array array('produse' => array, 'total' => int)
     */
    public static function cauta(array $filtre, $limita = 12, $deplasare = 0)
    {
        $conditii = array('p.activ = 1');
        $param = array();

        if (!empty($filtre['cautare'])) {
            $conditii[] = "(LOWER(p.nume) LIKE ? ESCAPE '!' OR LOWER(p.cod_sku) LIKE ? ESCAPE '!')";
            $termen = '%' . self::escapeLike(strtolower($filtre['cautare'])) . '%';
            $param[] = $termen;
            $param[] = $termen;
        }
        if (!empty($filtre['categorie'])) {
            $conditii[] = 'p.categorie_id = ?';
            $param[] = (int) $filtre['categorie'];
        }
        if (isset($filtre['pret_min']) && $filtre['pret_min'] !== '') {
            $conditii[] = 'p.pret >= ?';
            $param[] = (float) $filtre['pret_min'];
        }
        if (isset($filtre['pret_max']) && $filtre['pret_max'] !== '') {
            $conditii[] = 'p.pret <= ?';
            $param[] = (float) $filtre['pret_max'];
        }
        if (!empty($filtre['doar_stoc'])) {
            $conditii[] = 'p.stoc > 0';
        }
        if (!empty($filtre['doar_reduceri'])) {
            $conditii[] = 'p.pret_vechi IS NOT NULL AND p.pret_vechi > p.pret';
        }

        $sortari = self::sortari();
        $cheie = (isset($filtre['sortare']) && isset($sortari[$filtre['sortare']])) ? $filtre['sortare'] : 'recomandate';
        $where = implode(' AND ', $conditii);

        $total = (int) Baza::valoare("SELECT COUNT(*) FROM produse p WHERE $where", $param);
        $produse = Baza::toate(
            "SELECT p.*, c.nume AS categorie_nume, c.slug AS categorie_slug
               FROM produse p JOIN categorii c ON c.id = p.categorie_id
              WHERE $where
              ORDER BY {$sortari[$cheie][0]}
              LIMIT " . (int) $limita . ' OFFSET ' . (int) $deplasare,
            $param
        );
        return array('produse' => $produse, 'total' => $total);
    }

    /**
     * Escapeaza caracterele speciale ale operatorului LIKE (%, _ si !).
     * Se foloseste '!' drept caracter de escapare, declarat explicit prin
     * clauza ESCAPE, deoarece motoarele SQL au comportamente implicite
     * diferite pentru caracterul '\\'.
     *
     * @param string $text
     * @return string
     */
    private static function escapeLike($text)
    {
        return strtr($text, array('!' => '!!', '%' => '!%', '_' => '!_'));
    }

    /**
     * Produsele marcate ca recomandate, pentru pagina principala.
     *
     * @param int $limita
     * @return array
     */
    public static function recomandate($limita = 8)
    {
        return Baza::toate(
            'SELECT * FROM produse WHERE activ = 1 AND recomandat = 1
              ORDER BY creat_la DESC LIMIT ' . (int) $limita
        );
    }

    /**
     * Cele mai noi produse adaugate in catalog.
     *
     * @param int $limita
     * @return array
     */
    public static function noutati($limita = 4)
    {
        return Baza::toate('SELECT * FROM produse WHERE activ = 1 ORDER BY creat_la DESC LIMIT ' . (int) $limita);
    }

    /**
     * Returneaza un produs dupa identificator (inclusiv numele categoriei).
     *
     * @param int $id
     * @return array|null
     */
    public static function dupaId($id)
    {
        return Baza::unul(
            'SELECT p.*, c.nume AS categorie_nume, c.slug AS categorie_slug
               FROM produse p JOIN categorii c ON c.id = p.categorie_id WHERE p.id = ?',
            array((int) $id)
        );
    }

    /**
     * Returneaza un produs dupa slug (adrese prietenoase pentru motoarele de cautare).
     *
     * @param string $slug
     * @return array|null
     */
    public static function dupaSlug($slug)
    {
        return Baza::unul(
            'SELECT p.*, c.nume AS categorie_nume, c.slug AS categorie_slug
               FROM produse p JOIN categorii c ON c.id = p.categorie_id WHERE p.slug = ?',
            array($slug)
        );
    }

    /**
     * Returneaza mai multe produse, indexate dupa identificator.
     *
     * @param array $iduri
     * @return array id => produs
     */
    public static function dupaIduri(array $iduri)
    {
        $iduri = array_values(array_filter(array_map('intval', $iduri)));
        if (empty($iduri)) {
            return array();
        }
        $marcaje = implode(',', array_fill(0, count($iduri), '?'));
        $rezultat = array();
        foreach (Baza::toate("SELECT * FROM produse WHERE id IN ($marcaje)", $iduri) as $p) {
            $rezultat[(int) $p['id']] = $p;
        }
        return $rezultat;
    }

    /**
     * Specificatiile tehnice ale unui produs.
     *
     * @param int $produsId
     * @return array
     */
    public static function specificatii($produsId)
    {
        return Baza::toate('SELECT nume, valoare FROM specificatii WHERE produs_id = ? ORDER BY ordine, id', array((int) $produsId));
    }

    /**
     * Recomandari pentru un produs (A6): mai intai accesoriile compatibile
     * definite explicit, apoi produsele cumparate frecvent impreuna cu el,
     * iar la final produse din aceeasi categorie.
     *
     * @param array $produs
     * @param int   $limita
     * @return array
     */
    public static function recomandari(array $produs, $limita = 4)
    {
        $id = (int) $produs['id'];
        $rezultat = Baza::toate(
            'SELECT p.* FROM produse_asociate a JOIN produse p ON p.id = a.asociat_id
              WHERE a.produs_id = ? AND p.activ = 1
              ORDER BY CASE WHEN p.stoc > 0 THEN 0 ELSE 1 END, p.nume LIMIT ' . (int) $limita,
            array($id)
        );

        // Completare cu produse "cumparate impreuna" (aceeasi comanda)
        if (count($rezultat) < $limita) {
            $excluse = array_merge(array($id), array_map('intval', array_column($rezultat, 'id')));
            $marcaje = implode(',', array_fill(0, count($excluse), '?'));
            $suplimentare = Baza::toate(
                "SELECT p.*, COUNT(*) AS frecventa
                   FROM comenzi_produse a
                   JOIN comenzi_produse b ON b.comanda_id = a.comanda_id AND b.produs_id <> a.produs_id
                   JOIN produse p ON p.id = b.produs_id
                  WHERE a.produs_id = ? AND p.activ = 1 AND p.id NOT IN ($marcaje)
                  GROUP BY p.id ORDER BY frecventa DESC, p.id LIMIT " . (int) ($limita - count($rezultat)),
                array_merge(array($id), $excluse)
            );
            $rezultat = array_merge($rezultat, $suplimentare);
        }

        // Completare cu produse din aceeasi categorie
        if (count($rezultat) < $limita) {
            $excluse = array_merge(array($id), array_map('intval', array_column($rezultat, 'id')));
            $marcaje = implode(',', array_fill(0, count($excluse), '?'));
            $dinCategorie = Baza::toate(
                "SELECT * FROM produse WHERE categorie_id = ? AND activ = 1 AND id NOT IN ($marcaje)
                  ORDER BY recomandat DESC, CASE WHEN stoc > 0 THEN 0 ELSE 1 END, id LIMIT " . (int) ($limita - count($rezultat)),
                array_merge(array((int) $produs['categorie_id']), $excluse)
            );
            $rezultat = array_merge($rezultat, $dinCategorie);
        }
        return $rezultat;
    }

    /**
     * Lista categoriilor, cu numarul de produse active din fiecare.
     *
     * @return array
     */
    public static function categorii()
    {
        return Baza::toate(
            'SELECT c.*, (SELECT COUNT(*) FROM produse p WHERE p.categorie_id = c.id AND p.activ = 1) AS numar_produse
               FROM categorii c ORDER BY c.ordine, c.nume'
        );
    }

    /**
     * Returneaza o categorie dupa identificator.
     *
     * @param int $id
     * @return array|null
     */
    public static function categorie($id)
    {
        return Baza::unul('SELECT * FROM categorii WHERE id = ?', array((int) $id));
    }

    /**
     * Recenziile unui produs si media notelor.
     *
     * @param int $produsId
     * @return array array('lista' => array, 'medie' => float, 'numar' => int)
     */
    public static function recenzii($produsId)
    {
        $lista = Baza::toate(
            'SELECT r.*, u.nume AS autor FROM recenzii r JOIN utilizatori u ON u.id = r.utilizator_id
              WHERE r.produs_id = ? ORDER BY r.creat_la DESC',
            array((int) $produsId)
        );
        $numar = count($lista);
        $medie = $numar ? array_sum(array_column($lista, 'nota')) / $numar : 0;
        return array('lista' => $lista, 'medie' => round($medie, 1), 'numar' => $numar);
    }

    /* -------------------------------------------------------------- */
    /*  Operatii de administrare                                       */
    /* -------------------------------------------------------------- */

    /**
     * Toate produsele (inclusiv cele inactive), pentru panoul de administrare.
     *
     * @param string $cautare
     * @return array
     */
    public static function toateAdmin($cautare = '')
    {
        $param = array();
        $where = '';
        if ($cautare !== '') {
            $where = "WHERE LOWER(p.nume) LIKE ? ESCAPE '!' OR LOWER(p.cod_sku) LIKE ? ESCAPE '!'";
            $t = '%' . self::escapeLike(strtolower($cautare)) . '%';
            $param = array($t, $t);
        }
        return Baza::toate(
            "SELECT p.*, c.nume AS categorie_nume FROM produse p JOIN categorii c ON c.id = p.categorie_id
             $where ORDER BY p.id DESC",
            $param
        );
    }

    /**
     * Salveaza un produs (inserare daca $id este 0, altfel actualizare).
     *
     * @param int   $id
     * @param array $date Campurile validate ale produsului.
     * @return int Identificatorul produsului salvat.
     */
    public static function salveaza($id, array $date)
    {
        $valori = array(
            (int) $date['categorie_id'], $date['cod_sku'], $date['nume'], $date['slug'], $date['descriere'],
            $date['pret'], $date['pret_vechi'], (int) $date['stoc'], $date['unitate'], $date['greutate_kg'],
            $date['imagine'], $date['recomandat'] ? 1 : 0, $date['activ'] ? 1 : 0,
        );
        if ($id > 0) {
            $valori[] = $id;
            Baza::executa(
                'UPDATE produse SET categorie_id = ?, cod_sku = ?, nume = ?, slug = ?, descriere = ?, pret = ?,
                        pret_vechi = ?, stoc = ?, unitate = ?, greutate_kg = ?, imagine = ?, recomandat = ?, activ = ?
                  WHERE id = ?',
                $valori
            );
            return $id;
        }
        $coloane = array('categorie_id', 'cod_sku', 'nume', 'slug', 'descriere', 'pret', 'pret_vechi', 'stoc',
            'unitate', 'greutate_kg', 'imagine', 'recomandat', 'activ');
        $rand = array_combine($coloane, $valori);
        $rand['creat_la'] = Baza::acum();
        return Baza::insereaza('produse', $rand);
    }

    /**
     * Inlocuieste specificatiile unui produs.
     *
     * @param int   $produsId
     * @param array $specificatii Lista de perechi array('nume' => .., 'valoare' => ..).
     * @return void
     */
    public static function salveazaSpecificatii($produsId, array $specificatii)
    {
        Baza::executa('DELETE FROM specificatii WHERE produs_id = ?', array((int) $produsId));
        $ordine = 0;
        foreach ($specificatii as $s) {
            if ($s['nume'] === '' || $s['valoare'] === '') {
                continue;
            }
            Baza::executa('INSERT INTO specificatii (produs_id, nume, valoare, ordine) VALUES (?, ?, ?, ?)',
                array((int) $produsId, $s['nume'], $s['valoare'], ++$ordine));
        }
    }

    /**
     * Inlocuieste lista de accesorii compatibile ale unui produs.
     *
     * @param int   $produsId
     * @param array $asociate Identificatorii produselor asociate.
     * @return void
     */
    public static function salveazaAsociate($produsId, array $asociate)
    {
        Baza::executa('DELETE FROM produse_asociate WHERE produs_id = ?', array((int) $produsId));
        foreach (array_unique(array_map('intval', $asociate)) as $a) {
            if ($a > 0 && $a !== (int) $produsId) {
                Baza::executa('INSERT INTO produse_asociate (produs_id, asociat_id) VALUES (?, ?)', array((int) $produsId, $a));
            }
        }
    }

    /**
     * Identificatorii accesoriilor asociate unui produs.
     *
     * @param int $produsId
     * @return array
     */
    public static function asociate($produsId)
    {
        return array_map('intval', array_column(
            Baza::toate('SELECT asociat_id FROM produse_asociate WHERE produs_id = ?', array((int) $produsId)),
            'asociat_id'
        ));
    }

    /**
     * Sterge un produs. Daca produsul apare in comenzi, este doar
     * dezactivat, pentru a pastra integritatea istoricului.
     *
     * @param int $id
     * @return string 'sters' sau 'dezactivat'
     */
    public static function sterge($id)
    {
        $folosit = (int) Baza::valoare('SELECT COUNT(*) FROM comenzi_produse WHERE produs_id = ?', array((int) $id));
        if ($folosit > 0) {
            Baza::executa('UPDATE produse SET activ = 0 WHERE id = ?', array((int) $id));
            return 'dezactivat';
        }
        Baza::executa('DELETE FROM produse WHERE id = ?', array((int) $id));
        return 'sters';
    }
}
