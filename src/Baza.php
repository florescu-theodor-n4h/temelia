<?php
/**
 * Strat de acces la baza de date, independent de motorul SQL.
 *
 * Aplicatia foloseste PDO si doar SQL standard, astfel incat poate rula
 * fara modificari pe:
 *   - SQLite     (implicit; un singur fisier, fara server - recomandat
 *                 pentru dezvoltare locala si gazduire gratuita);
 *   - PostgreSQL (driverul 'pgsql');
 *   - MySQL / MariaDB (driverul 'mysql').
 *
 * Toate interogarile folosesc EXCLUSIV instructiuni pregatite (prepared
 * statements), eliminand riscul de injectie SQL. Diferentele dintre
 * motoare (obtinerea ID-ului inserat, activarea cheilor straine) sunt
 * tratate centralizat in aceasta clasa.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Baza
{
    /**
     * Conexiunea PDO partajata pe durata cererii.
     *
     * @var PDO|null
     */
    private static $pdo = null;

    /**
     * Configuratia conexiunii (sectiunea 'db' din config.php).
     *
     * @var array
     */
    private static $config = array();

    /**
     * Retine parametrii de conexiune; conectarea efectiva este amanata
     * pana la prima interogare (conectare "lenesa").
     *
     * @param array $config Chei: driver, fisier (sqlite) sau host, port,
     *                      nume, user, parola (pgsql / mysql).
     * @return void
     */
    public static function configureaza(array $config)
    {
        self::$config = $config;
        self::$pdo = null;
    }

    /**
     * Driverul PDO configurat: 'sqlite', 'pgsql' sau 'mysql'.
     *
     * @return string
     */
    public static function driver()
    {
        return isset(self::$config['driver']) ? self::$config['driver'] : 'sqlite';
    }

    /**
     * Returneaza conexiunea PDO, creand-o la nevoie.
     *
     * @return PDO
     * @throws PDOException Daca baza de date nu este disponibila.
     * @throws RuntimeException Daca driverul configurat nu este suportat.
     */
    public static function pdo()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $c = self::$config;
        $optiuni = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );

        switch (self::driver()) {
            case 'sqlite':
                $director = dirname($c['fisier']);
                if (!is_dir($director)) {
                    mkdir($director, 0775, true);
                }
                self::$pdo = new PDO('sqlite:' . $c['fisier'], null, null, $optiuni);
                // SQLite nu verifica cheile straine decat la cerere explicita
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                // Asteptare de pana la 5 secunde daca fisierul este blocat de alta cerere
                self::$pdo->exec('PRAGMA busy_timeout = 5000');
                break;

            case 'pgsql':
                $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['nume']);
                self::$pdo = new PDO($dsn, $c['user'], $c['parola'], $optiuni);
                break;

            case 'mysql':
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['nume']);
                self::$pdo = new PDO($dsn, $c['user'], $c['parola'], $optiuni);
                break;

            default:
                throw new RuntimeException('Driver de baza de date nesuportat: ' . self::driver());
        }
        return self::$pdo;
    }

    /**
     * Pregateste si executa o interogare cu parametri legati.
     *
     * @param string $sql       Interogarea SQL cu marcaje (?).
     * @param array  $parametri Valorile legate de marcaje.
     * @return PDOStatement
     */
    public static function executa($sql, array $parametri = array())
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($parametri));
        return $stmt;
    }

    /**
     * Returneaza toate randurile rezultate.
     *
     * @param string $sql
     * @param array  $parametri
     * @return array Lista de tablouri asociative.
     */
    public static function toate($sql, array $parametri = array())
    {
        return self::executa($sql, $parametri)->fetchAll();
    }

    /**
     * Returneaza primul rand rezultat sau null daca nu exista.
     *
     * @param string $sql
     * @param array  $parametri
     * @return array|null
     */
    public static function unul($sql, array $parametri = array())
    {
        $rand = self::executa($sql, $parametri)->fetch();
        return $rand === false ? null : $rand;
    }

    /**
     * Returneaza valoarea primei coloane din primul rand.
     *
     * @param string $sql
     * @param array  $parametri
     * @return mixed|null
     */
    public static function valoare($sql, array $parametri = array())
    {
        $v = self::executa($sql, $parametri)->fetchColumn();
        return $v === false ? null : $v;
    }

    /**
     * Insereaza un rand si returneaza identificatorul generat.
     *
     * Metoda inlocuieste clauza RETURNING (specifica PostgreSQL) cu un
     * mecanism portabil bazat pe PDO::lastInsertId().
     *
     * @param string $tabel Numele tabelului (constant din cod, nu din exterior).
     * @param array  $date  coloana => valoare
     * @return int
     */
    public static function insereaza($tabel, array $date)
    {
        $coloane = array_keys($date);
        foreach ($coloane as $col) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $col)) {
                throw new InvalidArgumentException('Nume de coloana invalid: ' . $col);
            }
        }
        $marcaje = implode(', ', array_fill(0, count($date), '?'));
        self::executa(
            'INSERT INTO ' . $tabel . ' (' . implode(', ', $coloane) . ') VALUES (' . $marcaje . ')',
            array_values($date)
        );
        // PostgreSQL necesita numele secventei asociate coloanei SERIAL
        $secventa = self::driver() === 'pgsql' ? $tabel . '_id_seq' : null;
        return (int) self::pdo()->lastInsertId($secventa);
    }

    /**
     * Executa o functie in cadrul unei tranzactii. In caz de exceptie
     * tranzactia este anulata (ROLLBACK), iar exceptia este propagata.
     *
     * @param callable $operatie Functia care contine operatiile atomice.
     * @return mixed Valoarea returnata de functie.
     * @throws Exception
     */
    public static function tranzactie($operatie)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $rezultat = call_user_func($operatie);
            $pdo->commit();
            return $rezultat;
        } catch (Exception $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }

    /**
     * Momentul curent in formatul stocat in baza de date (Y-m-d H:i:s).
     * Data este generata de aplicatie pentru a evita functiile SQL
     * specifice fiecarui motor (NOW(), datetime('now') etc.).
     *
     * @return string
     */
    public static function acum()
    {
        return date('Y-m-d H:i:s');
    }
}
