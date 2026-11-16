<?php
/**
 * Gestionarea conturilor de utilizator: inregistrare, autentificare,
 * deconectare si verificarea drepturilor de acces (rolurile 'client'
 * si 'admin').
 *
 * Parolele sunt salvate exclusiv ca hash bcrypt prin password_hash(),
 * iar identificatorul de sesiune este regenerat la autentificare pentru
 * a preveni atacurile de tip "session fixation".
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Autentificare
{
    /**
     * Creeaza un cont nou de client.
     *
     * @param string $nume
     * @param string $email
     * @param string $parola  Parola in clar (nu se salveaza ca atare).
     * @param string $telefon
     * @return int Identificatorul noului utilizator.
     * @throws RuntimeException Daca adresa de e-mail este deja folosita.
     */
    public static function inregistreaza($nume, $email, $parola, $telefon)
    {
        $email = strtolower(trim($email));
        if (self::dupaEmail($email)) {
            throw new RuntimeException('Exista deja un cont asociat acestei adrese de e-mail.');
        }
        return Baza::insereaza('utilizatori', array(
            'nume'        => $nume,
            'email'       => $email,
            'parola_hash' => password_hash($parola, PASSWORD_DEFAULT),
            'telefon'     => $telefon,
            'rol'         => 'client',
            'creat_la'    => Baza::acum(),
        ));
    }

    /**
     * Verifica datele de autentificare si deschide sesiunea utilizatorului.
     *
     * @param string $email
     * @param string $parola
     * @return bool True daca autentificarea a reusit.
     */
    public static function autentifica($email, $parola)
    {
        $utilizator = self::dupaEmail(strtolower(trim($email)));
        if (!$utilizator || !password_verify($parola, $utilizator['parola_hash'])) {
            return false;
        }
        // Actualizarea transparenta a hash-ului daca algoritmul implicit s-a schimbat
        if (password_needs_rehash($utilizator['parola_hash'], PASSWORD_DEFAULT)) {
            Baza::executa('UPDATE utilizatori SET parola_hash = ? WHERE id = ?',
                array(password_hash($parola, PASSWORD_DEFAULT), $utilizator['id']));
        }
        session_regenerate_id(true);
        $_SESSION['utilizator_id'] = (int) $utilizator['id'];
        $_SESSION['rol'] = $utilizator['rol'];
        return true;
    }

    /**
     * Inchide sesiunea utilizatorului, pastrand cosul de cumparaturi.
     *
     * @return void
     */
    public static function deconecteaza()
    {
        unset($_SESSION['utilizator_id'], $_SESSION['rol']);
        session_regenerate_id(true);
    }

    /**
     * Returneaza datele utilizatorului autentificat sau null.
     *
     * @return array|null
     */
    public static function utilizator()
    {
        static $cache = null;
        if (empty($_SESSION['utilizator_id'])) {
            return null;
        }
        if ($cache === null || (int) $cache['id'] !== (int) $_SESSION['utilizator_id']) {
            $cache = Baza::unul('SELECT * FROM utilizatori WHERE id = ?', array($_SESSION['utilizator_id']));
        }
        return $cache;
    }

    /**
     * @return bool True daca exista un utilizator autentificat.
     */
    public static function esteAutentificat()
    {
        return self::utilizator() !== null;
    }

    /**
     * @return bool True daca utilizatorul curent are rolul de administrator.
     */
    public static function esteAdmin()
    {
        $u = self::utilizator();
        return $u !== null && $u['rol'] === 'admin';
    }

    /**
     * Opreste accesul vizitatorilor neautentificati, redirectionandu-i
     * catre pagina de autentificare.
     *
     * @return void
     */
    public static function cereAutentificare()
    {
        if (!self::esteAutentificat()) {
            flash('info', 'Pentru a continua va rugam sa va autentificati.');
            redirectioneaza('cont/autentificare.php', array('inapoi' => $_SERVER['REQUEST_URI']));
        }
    }

    /**
     * Permite accesul exclusiv administratorilor. Clientii autentificati
     * primesc codul HTTP 403 (acces interzis).
     *
     * @return void
     */
    public static function cereAdmin()
    {
        self::cereAutentificare();
        if (!self::esteAdmin()) {
            http_response_code(403);
            exit('Acces interzis. Aceasta sectiune este rezervata administratorilor.');
        }
    }

    /**
     * Cauta un utilizator dupa adresa de e-mail.
     *
     * @param string $email
     * @return array|null
     */
    public static function dupaEmail($email)
    {
        return Baza::unul('SELECT * FROM utilizatori WHERE email = ?', array($email));
    }
}
