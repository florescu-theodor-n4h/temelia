<?php
/**
 * Shim (adaptor de simulare) pentru API-ul firmei de curierat RapidCurier.
 *
 * In productie, magazinul ar comunica prin HTTPS/JSON cu API-ul real al
 * curierului. Pentru proiectul de fata, acest shim imita fidel contractul
 * API-ului (endpoint-uri, cereri si raspunsuri sub forma de tablouri
 * echivalente JSON), fara a efectua apeluri de retea. Inlocuirea cu
 * integrarea reala presupune doar rescrierea metodei transport().
 *
 * Endpoint-uri emulate:
 *   POST /v1/tarif     - calculeaza costul de livrare
 *   POST /v1/awb       - genereaza scrisoarea de transport (AWB)
 *   GET  /v1/urmarire  - returneaza istoricul de livrare al unui AWB
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class CurierShim
{
    /** Tarif de baza pentru colete de pana la 5 kg (lei, TVA inclus). */
    const TARIF_BAZA = 19.99;

    /** Cost suplimentar pentru fiecare kilogram peste 5 kg. */
    const TARIF_KG_SUPLIMENTAR = 1.20;

    /** Suprataxa pentru transport paletizat (colete peste 100 kg). */
    const SUPRATAXA_PALET = 150.00;

    /** Comision pentru serviciul de ramburs (incasare numerar). */
    const COMISION_RAMBURS = 4.99;

    /** Pragul de la care livrarea este gratuita (pentru colete sub 100 kg). */
    const PRAG_LIVRARE_GRATUITA = 1500.00;

    /** Judetul depozitului expeditor; livrarile locale au reducere de 20%. */
    const JUDET_DEPOZIT = 'Dolj';

    /**
     * Punctul unic de intrare, echivalentul unui apel HTTP catre curier.
     *
     * @param string $endpoint Ex. '/v1/tarif'.
     * @param array  $cerere   Corpul cererii (echivalent JSON).
     * @return array Raspunsul API: contine intotdeauna cheia 'succes'.
     */
    public static function transport($endpoint, array $cerere)
    {
        jurnal('CurierShim ' . $endpoint . ' ' . json_encode($cerere));
        switch ($endpoint) {
            case '/v1/tarif':
                return self::endpointTarif($cerere);
            case '/v1/awb':
                return self::endpointAwb($cerere);
            case '/v1/urmarire':
                return self::endpointUrmarire($cerere);
            default:
                return array('succes' => false, 'cod' => 404, 'mesaj' => 'Endpoint necunoscut.');
        }
    }

    /**
     * Metoda de conveninta: costul livrarii pentru o comanda.
     *
     * @param string $judet
     * @param float  $greutateKg
     * @param float  $valoare  Valoarea marfii (dupa reducere).
     * @param bool   $ramburs  Plata se face la livrare.
     * @return array Raspunsul endpoint-ului /v1/tarif.
     */
    public static function tarif($judet, $greutateKg, $valoare, $ramburs)
    {
        return self::transport('/v1/tarif', array(
            'judet_destinatie' => $judet,
            'greutate_kg'      => $greutateKg,
            'valoare_declarata' => $valoare,
            'ramburs'          => (bool) $ramburs,
        ));
    }

    /**
     * Calculul propriu-zis al tarifului (functie pura, testabila automat).
     *
     * @param string $judet
     * @param float  $greutateKg
     * @param float  $valoare
     * @param bool   $ramburs
     * @return float Costul livrarii in lei.
     */
    public static function calculeazaTarif($judet, $greutateKg, $valoare, $ramburs)
    {
        $greutateKg = max(0.0, (float) $greutateKg);
        if ((float) $valoare >= self::PRAG_LIVRARE_GRATUITA && $greutateKg <= 100) {
            return 0.0;
        }
        $cost = self::TARIF_BAZA;
        if ($greutateKg > 5) {
            $cost += ceil($greutateKg - 5) * self::TARIF_KG_SUPLIMENTAR;
        }
        if ($greutateKg > 100) {
            $cost += self::SUPRATAXA_PALET;
        }
        if (strcasecmp($judet, self::JUDET_DEPOZIT) === 0) {
            $cost *= 0.8;
        }
        if ($ramburs) {
            $cost += self::COMISION_RAMBURS;
        }
        return round($cost, 2);
    }

    /**
     * Estimeaza termenul de livrare in zile lucratoare.
     *
     * @param string $judet
     * @param float  $greutateKg
     * @return int
     */
    public static function zileLivrare($judet, $greutateKg)
    {
        $zile = strcasecmp($judet, self::JUDET_DEPOZIT) === 0 ? 1 : 2;
        return $greutateKg > 100 ? $zile + 2 : $zile;
    }

    /**
     * POST /v1/tarif
     *
     * @param array $c
     * @return array
     */
    private static function endpointTarif(array $c)
    {
        if (empty($c['judet_destinatie'])) {
            return array('succes' => false, 'cod' => 422, 'mesaj' => 'Judetul de destinatie lipseste.');
        }
        $kg = isset($c['greutate_kg']) ? (float) $c['greutate_kg'] : 0;
        $val = isset($c['valoare_declarata']) ? (float) $c['valoare_declarata'] : 0;
        $ramburs = !empty($c['ramburs']);
        return array(
            'succes'       => true,
            'cod'          => 200,
            'tarif'        => self::calculeazaTarif($c['judet_destinatie'], $kg, $val, $ramburs),
            'moneda'       => 'RON',
            'zile_livrare' => self::zileLivrare($c['judet_destinatie'], $kg),
            'serviciu'     => $kg > 100 ? 'Paletizat' : 'Standard',
        );
    }

    /**
     * POST /v1/awb
     *
     * Numarul AWB are formatul RC + data (aaaallzz) + 6 cifre, astfel
     * incat endpoint-ul de urmarire poate deduce data expedierii.
     *
     * @param array $c
     * @return array
     */
    private static function endpointAwb(array $c)
    {
        foreach (array('destinatar', 'telefon', 'adresa', 'judet', 'greutate_kg') as $camp) {
            if (!isset($c[$camp]) || $c[$camp] === '') {
                return array('succes' => false, 'cod' => 422, 'mesaj' => 'Campul obligatoriu ' . $camp . ' lipseste.');
            }
        }
        $awb = 'RC' . date('Ymd') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        return array(
            'succes' => true,
            'cod'    => 201,
            'awb'    => $awb,
            'eticheta_url' => 'https://rapidcurier.example.ro/eticheta/' . $awb . '.pdf',
        );
    }

    /**
     * GET /v1/urmarire
     *
     * Istoricul este simulat in functie de zilele scurse de la emiterea AWB.
     *
     * @param array $c
     * @return array
     */
    private static function endpointUrmarire(array $c)
    {
        $awb = isset($c['awb']) ? (string) $c['awb'] : '';
        if (!preg_match('/^RC(\d{8})\d{6}$/', $awb, $m)) {
            return array('succes' => false, 'cod' => 404, 'mesaj' => 'AWB inexistent.');
        }
        $emis = DateTime::createFromFormat('Ymd H:i', $m[1] . ' 00:00');
        if (!$emis) {
            return array('succes' => false, 'cod' => 404, 'mesaj' => 'AWB inexistent.');
        }
        $etape = array(
            array(0, 'Expeditie inregistrata in sistemul RapidCurier'),
            array(0, 'Colet preluat de la expeditor (depozit Craiova)'),
            array(1, 'Colet in tranzit catre hub-ul regional'),
            array(1, 'Colet predat curierului pentru livrare'),
            array(2, 'Colet livrat destinatarului'),
        );
        $acum = time();
        $istoric = array();
        foreach ($etape as $i => $etapa) {
            $moment = clone $emis;
            $moment->modify('+' . $etapa[0] . ' day +' . ($i * 2) . ' hour');
            if ($moment->getTimestamp() <= $acum) {
                $istoric[] = array('data' => $moment->format('Y-m-d H:i'), 'eveniment' => $etapa[1]);
            }
        }
        $livrat = count($istoric) === count($etape);
        return array('succes' => true, 'cod' => 200, 'awb' => $awb, 'livrat' => $livrat, 'istoric' => $istoric);
    }
}
