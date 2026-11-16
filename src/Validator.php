<?php
/**
 * Validarea pe server a datelor primite din formulare.
 *
 * Fiecare regula adauga, in caz de esec, un mesaj de eroare asociat
 * campului verificat. Validarea pe server este obligatorie indiferent
 * de eventualele verificari facute in browser.
 *
 * Exemplu de utilizare:
 * <pre>
 *   $v = new Validator($_POST);
 *   $v->obligatoriu('nume', 'Numele')->email('email', 'Adresa de e-mail');
 *   if ($v->esteValid()) { ... }
 * </pre>
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Validator
{
    /**
     * Datele supuse validarii.
     *
     * @var array
     */
    private $date;

    /**
     * Erorile gasite, indexate dupa numele campului.
     *
     * @var array
     */
    private $erori = array();

    /**
     * @param array $date De regula $_POST.
     */
    public function __construct(array $date)
    {
        $this->date = $date;
    }

    /**
     * Returneaza valoarea curatata (trim) a unui camp.
     *
     * @param string $camp
     * @return string
     */
    public function valoare($camp)
    {
        return (isset($this->date[$camp]) && !is_array($this->date[$camp]))
            ? trim((string) $this->date[$camp]) : '';
    }

    /**
     * Inregistreaza o eroare (doar prima eroare a fiecarui camp este pastrata).
     *
     * @param string $camp
     * @param string $mesaj
     * @return Validator
     */
    public function adaugaEroare($camp, $mesaj)
    {
        if (!isset($this->erori[$camp])) {
            $this->erori[$camp] = $mesaj;
        }
        return $this;
    }

    /**
     * Campul trebuie sa fie completat.
     *
     * @param string $camp
     * @param string $eticheta Denumirea afisata a campului.
     * @return Validator
     */
    public function obligatoriu($camp, $eticheta)
    {
        if ($this->valoare($camp) === '') {
            $this->adaugaEroare($camp, $eticheta . ' este obligatoriu.');
        }
        return $this;
    }

    /**
     * Lungimea textului trebuie sa fie in intervalul [min, max].
     *
     * @param string $camp
     * @param string $eticheta
     * @param int    $min
     * @param int    $max
     * @return Validator
     */
    public function lungime($camp, $eticheta, $min, $max)
    {
        $n = function_exists('mb_strlen') ? mb_strlen($this->valoare($camp), 'UTF-8') : strlen($this->valoare($camp));
        if ($n < $min || $n > $max) {
            $this->adaugaEroare($camp, sprintf('%s trebuie sa aiba intre %d si %d caractere.', $eticheta, $min, $max));
        }
        return $this;
    }

    /**
     * Campul trebuie sa contina o adresa de e-mail valida.
     *
     * @param string $camp
     * @param string $eticheta
     * @return Validator
     */
    public function email($camp, $eticheta)
    {
        if (filter_var($this->valoare($camp), FILTER_VALIDATE_EMAIL) === false) {
            $this->adaugaEroare($camp, $eticheta . ' nu este valida.');
        }
        return $this;
    }

    /**
     * Campul trebuie sa contina un numar de telefon romanesc
     * (07xxxxxxxx, 02xxxxxxxx, 03xxxxxxxx sau cu prefixul +40).
     *
     * @param string $camp
     * @param string $eticheta
     * @return Validator
     */
    public function telefon($camp, $eticheta)
    {
        if (!self::esteTelefonValid($this->valoare($camp))) {
            $this->adaugaEroare($camp, $eticheta . ' nu este valid (ex. 0722123456).');
        }
        return $this;
    }

    /**
     * Campul trebuie sa contina un cod postal romanesc de 6 cifre.
     *
     * @param string $camp
     * @param string $eticheta
     * @return Validator
     */
    public function codPostal($camp, $eticheta)
    {
        $v = $this->valoare($camp);
        if ($v !== '' && !preg_match('/^\d{6}$/', $v)) {
            $this->adaugaEroare($camp, $eticheta . ' trebuie sa contina 6 cifre.');
        }
        return $this;
    }

    /**
     * Campul trebuie sa fie un numar zecimal in intervalul dat.
     * Se accepta atat punctul cat si virgula ca separator zecimal.
     *
     * @param string $camp
     * @param string $eticheta
     * @param float  $min
     * @param float  $max
     * @return Validator
     */
    public function numar($camp, $eticheta, $min, $max)
    {
        $v = str_replace(',', '.', $this->valoare($camp));
        if (!is_numeric($v) || (float) $v < $min || (float) $v > $max) {
            $this->adaugaEroare($camp, sprintf('%s trebuie sa fie un numar intre %s si %s.', $eticheta, $min, $max));
        }
        return $this;
    }

    /**
     * Campul trebuie sa fie un numar intreg in intervalul dat.
     *
     * @param string $camp
     * @param string $eticheta
     * @param int    $min
     * @param int    $max
     * @return Validator
     */
    public function intreg($camp, $eticheta, $min, $max)
    {
        $v = $this->valoare($camp);
        if (!preg_match('/^-?\d+$/', $v) || (int) $v < $min || (int) $v > $max) {
            $this->adaugaEroare($camp, sprintf('%s trebuie sa fie un numar intreg intre %d si %d.', $eticheta, $min, $max));
        }
        return $this;
    }

    /**
     * Valoarea trebuie sa se regaseasca intr-o lista de optiuni permise.
     *
     * @param string $camp
     * @param string $eticheta
     * @param array  $optiuni
     * @return Validator
     */
    public function inLista($camp, $eticheta, array $optiuni)
    {
        if (!in_array($this->valoare($camp), $optiuni, true)) {
            $this->adaugaEroare($camp, $eticheta . ': optiunea selectata nu este valida.');
        }
        return $this;
    }

    /**
     * Doua campuri trebuie sa aiba aceeasi valoare (ex. confirmarea parolei).
     *
     * @param string $camp
     * @param string $campConfirmare
     * @param string $mesaj
     * @return Validator
     */
    public function identic($camp, $campConfirmare, $mesaj)
    {
        if ($this->valoare($camp) !== $this->valoare($campConfirmare)) {
            $this->adaugaEroare($campConfirmare, $mesaj);
        }
        return $this;
    }

    /**
     * Caseta de bifare trebuie sa fie bifata (ex. acceptarea termenilor).
     *
     * @param string $camp
     * @param string $mesaj
     * @return Validator
     */
    public function bifat($camp, $mesaj)
    {
        if (empty($this->date[$camp])) {
            $this->adaugaEroare($camp, $mesaj);
        }
        return $this;
    }

    /**
     * @return bool True daca nu a fost inregistrata nicio eroare.
     */
    public function esteValid()
    {
        return empty($this->erori);
    }

    /**
     * @return array camp => mesaj
     */
    public function erori()
    {
        return $this->erori;
    }

    /**
     * Verifica formatul unui numar de telefon romanesc.
     *
     * @param string $telefon
     * @return bool
     */
    public static function esteTelefonValid($telefon)
    {
        $curat = preg_replace('/[\s.\-()]/', '', (string) $telefon);
        return (bool) preg_match('/^(\+40|0040|0)[237]\d{8}$/', $curat);
    }
}
