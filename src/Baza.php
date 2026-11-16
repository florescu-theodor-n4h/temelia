<?php
/**
 * Strat de acces la baza de date PostgreSQL.
 *
 * Clasa incapsuleaza o singura conexiune PDO pe durata cererii HTTP si
 * ofera metode ajutatoare care folosesc EXCLUSIV interogari pregatite
 * (prepared statements), eliminand astfel riscul de injectie SQL.
 *
 * @package Temelia
 * @author  Florescu Theodor
 */
class Baza
{
    /**
     * Conexiunea PDO partajata.
     *
     * @var PDO|null
     */
    private static $pdo = null;

    /**
     * Configuratia conexiunii (host, port, nume, user, parola).
     *
     * @var array
     */
    private static $config = array();

    /**
     * Retine parametrii de conexiune; conectarea efectiva este amanata
     * pana la prima interogare (conectare "lenesa").
     *
     * @param array $config Sectiunea 'db' din fisierul de configurare.
     * @return void
     */
    public static function configureaza(array $config)
    {
        self::$config = $config;
        self::$pdo = null;
    }

    /**
     * Returneaza conexiunea PDO, creand-o la nevoie.
     *
     * @return PDO
     * @throws PDOException Daca serverul de baze de date nu este disponibil.
     */
    public static function pdo()
    {
        if (self::$pdo === null) {
            $c = self::$config;
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $c['host'], $c['port'], $c['nume']);
            self::$pdo = new PDO($dsn, $c['user'], $c['parola'], array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ));
            self::$pdo->exec("SET client_encoding TO 'UTF8'");
        }
        return self::$pdo;
    }

    /**
     * Pregateste si executa o interogare cu parametri legati.
     *
     * @param string $sql       Interogarea SQL cu marcaje (? sau :nume).
     * @param array  $parametri Valorile legate de marcaje.
     * @return PDOStatement
     */
    public static function executa($sql, array $parametri = array())
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($parametri);
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
}
