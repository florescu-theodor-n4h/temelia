<?php
/**
 * Gestionarea comenzilor: plasare (cu scaderea atomica a stocului),
 * consultare, schimbarea statusului, plati si statistici de vanzari.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Comenzi
{
    /**
     * Plaseaza o comanda noua intr-o singura tranzactie.
     *
     * Stocul fiecarui produs este scazut printr-o instructiune UPDATE
     * conditionata (stoc >= cantitate), care este atomica pe toate
     * motoarele SQL suportate. Daca un singur produs nu mai are stoc
     * suficient, intreaga comanda este anulata (ROLLBACK).
     *
     * @param array      $client Date validate: nume_client, telefon, email,
     *                           adresa, oras, judet, cod_postal, metoda_livrare,
     *                           metoda_plata, observatii.
     * @param array      $linii  Liniile cosului (Cos::linii()).
     * @param array      $sume   subtotal, discount, cost_livrare, total.
     * @param array|null $cupon  Cuponul aplicat sau null.
     * @param int|null   $utilizatorId
     * @return array Comanda creata (id, numar).
     * @throws RuntimeException Daca stocul este insuficient.
     */
    public static function plaseaza(array $client, array $linii, array $sume, $cupon, $utilizatorId)
    {
        return Baza::tranzactie(function () use ($client, $linii, $sume, $cupon, $utilizatorId) {
            foreach ($linii as $l) {
                $actualizate = Baza::executa(
                    'UPDATE produse SET stoc = stoc - ? WHERE id = ? AND activ = 1 AND stoc >= ?',
                    array((int) $l['cantitate'], (int) $l['produs']['id'], (int) $l['cantitate'])
                )->rowCount();
                if ($actualizate !== 1) {
                    throw new RuntimeException('Stoc insuficient pentru produsul "' . $l['produs']['nume'] . '".');
                }
            }

            $acum = Baza::acum();
            $id = Baza::insereaza('comenzi', array(
                'numar'          => 'TMP' . bin2hex(random_bytes(6)),
                'utilizator_id'  => $utilizatorId ? (int) $utilizatorId : null,
                'nume_client'    => $client['nume_client'],
                'telefon'        => $client['telefon'],
                'email'          => $client['email'],
                'adresa'         => $client['adresa'],
                'oras'           => $client['oras'],
                'judet'          => $client['judet'],
                'cod_postal'     => $client['cod_postal'],
                'metoda_livrare' => $client['metoda_livrare'],
                'metoda_plata'   => $client['metoda_plata'],
                'subtotal'       => $sume['subtotal'],
                'discount'       => $sume['discount'],
                'cost_livrare'   => $sume['cost_livrare'],
                'total'          => $sume['total'],
                'cod_cupon'      => $cupon ? $cupon['cod'] : null,
                'observatii'     => $client['observatii'],
                'status'         => 'noua',
                'creat_la'       => $acum,
                'actualizat_la'  => $acum,
            ));

            // Numarul comenzii: TM + an + identificator pe 6 cifre (ex. TM2026000042)
            $numar = self::formateazaNumar($id, date('Y'));
            Baza::executa('UPDATE comenzi SET numar = ? WHERE id = ?', array($numar, $id));

            foreach ($linii as $l) {
                Baza::insereaza('comenzi_produse', array(
                    'comanda_id'  => $id,
                    'produs_id'   => (int) $l['produs']['id'],
                    'nume_produs' => $l['produs']['nume'],
                    'cod_sku'     => $l['produs']['cod_sku'],
                    'pret_unitar' => $l['pret'],
                    'cantitate'   => (int) $l['cantitate'],
                    'total_linie' => $l['total'],
                ));
            }

            if ($cupon) {
                Baza::executa('UPDATE cupoane SET utilizari = utilizari + 1 WHERE id = ?', array((int) $cupon['id']));
            }
            self::adaugaIstoric($id, 'noua', 'Comanda a fost plasata.');

            return array('id' => $id, 'numar' => $numar);
        });
    }

    /**
     * Genereaza numarul public al comenzii.
     *
     * @param int        $id
     * @param string|int $an
     * @return string
     */
    public static function formateazaNumar($id, $an)
    {
        return sprintf('TM%s%06d', $an, (int) $id);
    }

    /**
     * Modifica statusul unei comenzi si aplica efectele asociate:
     *   - la anulare, stocul produselor este restituit;
     *   - la expediere prin curier, se genereaza AWB-ul prin CurierShim;
     *   - la livrarea unei comenzi ramburs, plata este marcata ca incasata.
     * O comanda anulata nu mai poate fi reactivata.
     *
     * @param int    $id
     * @param string $status Cod din staturi_comanda().
     * @param string $nota   Observatie optionala a administratorului.
     * @return void
     * @throws RuntimeException
     */
    public static function schimbaStatus($id, $status, $nota = '')
    {
        if (!array_key_exists($status, statusuri_comanda())) {
            throw new RuntimeException('Status necunoscut.');
        }
        Baza::tranzactie(function () use ($id, $status, $nota) {
            $comanda = Comenzi::dupaId($id);
            if (!$comanda) {
                throw new RuntimeException('Comanda nu exista.');
            }
            if ($comanda['status'] === $status) {
                return;
            }
            if ($comanda['status'] === 'anulata') {
                throw new RuntimeException('O comanda anulata nu mai poate fi modificata.');
            }

            $extra = '';
            if ($status === 'anulata') {
                foreach (Comenzi::linii($id) as $l) {
                    if ($l['produs_id']) {
                        Baza::executa('UPDATE produse SET stoc = stoc + ? WHERE id = ?', array((int) $l['cantitate'], (int) $l['produs_id']));
                    }
                }
                if ($comanda['stare_plata'] === 'platita') {
                    Baza::executa("UPDATE comenzi SET stare_plata = 'rambursata' WHERE id = ?", array($id));
                    $extra = ' Suma platita cu cardul va fi rambursata.';
                }
            }

            if ($status === 'expediata' && $comanda['metoda_livrare'] === 'curier' && empty($comanda['awb'])) {
                $raspuns = CurierShim::transport('/v1/awb', array(
                    'destinatar'  => $comanda['nume_client'],
                    'telefon'     => $comanda['telefon'],
                    'adresa'      => $comanda['adresa'] . ', ' . $comanda['oras'],
                    'judet'       => $comanda['judet'],
                    'greutate_kg' => Comenzi::greutate($id),
                    'ramburs'     => $comanda['metoda_plata'] === 'ramburs' ? (float) $comanda['total'] : 0,
                    'referinta'   => $comanda['numar'],
                ));
                if (empty($raspuns['succes'])) {
                    throw new RuntimeException('Curierul a respins expedierea: ' . $raspuns['mesaj']);
                }
                Baza::executa('UPDATE comenzi SET awb = ? WHERE id = ?', array($raspuns['awb'], $id));
                $extra = ' AWB RapidCurier: ' . $raspuns['awb'] . '.';
            }

            if ($status === 'livrata' && $comanda['metoda_plata'] === 'ramburs') {
                Baza::executa("UPDATE comenzi SET stare_plata = 'platita' WHERE id = ?", array($id));
            }

            Baza::executa('UPDATE comenzi SET status = ?, actualizat_la = ? WHERE id = ?', array($status, Baza::acum(), $id));
            Comenzi::adaugaIstoric($id, $status, trim($nota . $extra));
        });
    }

    /**
     * Adauga o inregistrare in istoricul statusurilor.
     *
     * @param int    $comandaId
     * @param string $status
     * @param string $nota
     * @return void
     */
    public static function adaugaIstoric($comandaId, $status, $nota)
    {
        Baza::insereaza('istoric_status', array(
            'comanda_id' => (int) $comandaId,
            'status'     => $status,
            'nota'       => mb_substr($nota, 0, 255, 'UTF-8'),
            'creat_la'   => Baza::acum(),
        ));
    }

    /**
     * Inregistreaza rezultatul unei plati cu cardul.
     *
     * @param int   $comandaId
     * @param array $raspuns Raspunsul PlataDummy::autorizeaza().
     * @param float $suma
     * @return void
     */
    public static function inregistreazaPlata($comandaId, array $raspuns, $suma)
    {
        Baza::tranzactie(function () use ($comandaId, $raspuns, $suma) {
            Baza::insereaza('tranzactii_plata', array(
                'comanda_id'     => (int) $comandaId,
                'referinta'      => $raspuns['referinta'],
                'suma'           => $suma,
                'stare'          => $raspuns['aprobata'] ? 'aprobata' : 'respinsa',
                'mesaj'          => $raspuns['cod'] . ' - ' . $raspuns['mesaj'],
                'card_ultimele4' => $raspuns['ultimele4'] !== '' ? $raspuns['ultimele4'] : null,
                'creat_la'       => Baza::acum(),
            ));
            Baza::executa('UPDATE comenzi SET stare_plata = ?, actualizat_la = ? WHERE id = ?',
                array($raspuns['aprobata'] ? 'platita' : 'respinsa', Baza::acum(), (int) $comandaId));
            if ($raspuns['aprobata']) {
                Comenzi::adaugaIstoric($comandaId, 'noua', 'Plata cu cardul a fost aprobata (ref. ' . $raspuns['referinta'] . ').');
            }
        });
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function dupaId($id)
    {
        return Baza::unul('SELECT * FROM comenzi WHERE id = ?', array((int) $id));
    }

    /**
     * @param string $numar
     * @return array|null
     */
    public static function dupaNumar($numar)
    {
        return Baza::unul('SELECT * FROM comenzi WHERE numar = ?', array($numar));
    }

    /**
     * Liniile (produsele) unei comenzi.
     *
     * @param int $comandaId
     * @return array
     */
    public static function linii($comandaId)
    {
        return Baza::toate('SELECT * FROM comenzi_produse WHERE comanda_id = ? ORDER BY id', array((int) $comandaId));
    }

    /**
     * Istoricul statusurilor unei comenzi, in ordine cronologica.
     *
     * @param int $comandaId
     * @return array
     */
    public static function istoric($comandaId)
    {
        return Baza::toate('SELECT * FROM istoric_status WHERE comanda_id = ? ORDER BY id', array((int) $comandaId));
    }

    /**
     * Tranzactiile de plata ale unei comenzi.
     *
     * @param int $comandaId
     * @return array
     */
    public static function tranzactii($comandaId)
    {
        return Baza::toate('SELECT * FROM tranzactii_plata WHERE comanda_id = ? ORDER BY id', array((int) $comandaId));
    }

    /**
     * Greutatea totala a produselor dintr-o comanda (pentru AWB).
     *
     * @param int $comandaId
     * @return float
     */
    public static function greutate($comandaId)
    {
        return (float) Baza::valoare(
            'SELECT COALESCE(SUM(cp.cantitate * p.greutate_kg), 0) FROM comenzi_produse cp
               LEFT JOIN produse p ON p.id = cp.produs_id WHERE cp.comanda_id = ?',
            array((int) $comandaId)
        );
    }

    /**
     * Comenzile unui client, cele mai recente primele.
     *
     * @param int $utilizatorId
     * @return array
     */
    public static function aleUtilizatorului($utilizatorId)
    {
        return Baza::toate('SELECT * FROM comenzi WHERE utilizator_id = ? ORDER BY id DESC', array((int) $utilizatorId));
    }

    /**
     * Verifica daca un client a cumparat un produs (conditie pentru recenzie).
     * Se iau in calcul doar comenzile care nu au fost anulate.
     *
     * @param int $utilizatorId
     * @param int $produsId
     * @return bool
     */
    public static function aCumparat($utilizatorId, $produsId)
    {
        return (int) Baza::valoare(
            "SELECT COUNT(*) FROM comenzi c JOIN comenzi_produse cp ON cp.comanda_id = c.id
              WHERE c.utilizator_id = ? AND cp.produs_id = ? AND c.status <> 'anulata'",
            array((int) $utilizatorId, (int) $produsId)
        ) > 0;
    }

    /**
     * Lista comenzilor pentru administrare, optional filtrata dupa status
     * sau dupa un termen de cautare (numar, nume client, e-mail).
     *
     * @param string $status
     * @param string $cautare
     * @return array
     */
    public static function toate($status = '', $cautare = '')
    {
        $conditii = array('1 = 1');
        $param = array();
        if ($status !== '' && array_key_exists($status, statusuri_comanda())) {
            $conditii[] = 'status = ?';
            $param[] = $status;
        }
        if ($cautare !== '') {
            $conditii[] = '(LOWER(numar) LIKE ? OR LOWER(nume_client) LIKE ? OR LOWER(email) LIKE ?)';
            $t = '%' . strtolower(str_replace(array('%', '_'), '', $cautare)) . '%';
            array_push($param, $t, $t, $t);
        }
        return Baza::toate('SELECT * FROM comenzi WHERE ' . implode(' AND ', $conditii) . ' ORDER BY id DESC', $param);
    }

    /**
     * Indicatorii pentru tabloul de bord al vanzarilor (A15).
     * Comenzile anulate sunt excluse din cifra de afaceri.
     *
     * @return array
     */
    public static function statistici()
    {
        $s = array();
        $s['venit_total'] = (float) Baza::valoare("SELECT COALESCE(SUM(total), 0) FROM comenzi WHERE status <> 'anulata'");
        $s['numar_comenzi'] = (int) Baza::valoare('SELECT COUNT(*) FROM comenzi');
        $s['comenzi_valide'] = (int) Baza::valoare("SELECT COUNT(*) FROM comenzi WHERE status <> 'anulata'");
        $s['valoare_medie'] = $s['comenzi_valide'] ? round($s['venit_total'] / $s['comenzi_valide'], 2) : 0;
        $s['comenzi_noi'] = (int) Baza::valoare("SELECT COUNT(*) FROM comenzi WHERE status = 'noua'");
        $s['numar_clienti'] = (int) Baza::valoare("SELECT COUNT(*) FROM utilizatori WHERE rol = 'client'");

        $s['pe_status'] = array();
        foreach (Baza::toate('SELECT status, COUNT(*) AS numar FROM comenzi GROUP BY status') as $r) {
            $s['pe_status'][$r['status']] = (int) $r['numar'];
        }

        $s['top_produse'] = Baza::toate(
            "SELECT cp.nume_produs, cp.cod_sku, SUM(cp.cantitate) AS bucati, SUM(cp.total_linie) AS valoare
               FROM comenzi_produse cp JOIN comenzi c ON c.id = cp.comanda_id
              WHERE c.status <> 'anulata'
              GROUP BY cp.nume_produs, cp.cod_sku
              ORDER BY bucati DESC, valoare DESC LIMIT 5"
        );

        $s['pe_categorii'] = Baza::toate(
            "SELECT cat.nume, SUM(cp.total_linie) AS valoare
               FROM comenzi_produse cp
               JOIN comenzi c ON c.id = cp.comanda_id
               JOIN produse p ON p.id = cp.produs_id
               JOIN categorii cat ON cat.id = p.categorie_id
              WHERE c.status <> 'anulata'
              GROUP BY cat.nume ORDER BY valoare DESC"
        );

        // Vanzarile din ultimele 14 zile; zilele fara vanzari apar cu 0
        $deLa = date('Y-m-d', strtotime('-13 days'));
        $zilnic = array();
        foreach (Baza::toate(
            "SELECT SUBSTR(creat_la, 1, 10) AS zi, COUNT(*) AS numar, SUM(total) AS valoare
               FROM comenzi WHERE status <> 'anulata' AND creat_la >= ?
              GROUP BY SUBSTR(creat_la, 1, 10)",
            array($deLa . ' 00:00:00')
        ) as $r) {
            $zilnic[$r['zi']] = $r;
        }
        $s['zilnic'] = array();
        for ($i = 13; $i >= 0; $i--) {
            $zi = date('Y-m-d', strtotime("-$i days"));
            $s['zilnic'][] = array(
                'zi'      => $zi,
                'numar'   => isset($zilnic[$zi]) ? (int) $zilnic[$zi]['numar'] : 0,
                'valoare' => isset($zilnic[$zi]) ? (float) $zilnic[$zi]['valoare'] : 0.0,
            );
        }

        $s['stoc_redus'] = Baza::toate('SELECT id, nume, cod_sku, stoc, unitate FROM produse WHERE activ = 1 AND stoc < 10 ORDER BY stoc, nume');
        return $s;
    }
}
