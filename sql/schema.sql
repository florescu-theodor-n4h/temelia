-- =====================================================================
--  TEMELIA Materiale de Constructii S.R.L.
--  Schema PORTABILA a bazei de date (SQLite / PostgreSQL / MySQL).
--
--  Schema foloseste doar elemente SQL standard, acceptate de toate cele
--  trei motoare. Singura diferenta - definirea cheii primare cu
--  autoincrementare - este exprimata prin marcajul {PK}, inlocuit de
--  scriptul sql/instalare.php in functie de driverul configurat:
--     sqlite -> INTEGER PRIMARY KEY AUTOINCREMENT
--     pgsql  -> SERIAL PRIMARY KEY
--     mysql  -> INT AUTO_INCREMENT PRIMARY KEY
--
--  Conventii:
--    - denumirile tabelelor si coloanelor sunt in limba romana;
--    - toate preturile sunt exprimate in RON si INCLUD TVA (21%);
--    - valorile logice sunt stocate ca SMALLINT (0 = nu, 1 = da);
--    - datele calendaristice sunt generate de aplicatie (Y-m-d H:i:s).
--
--  Fiecare instructiune se termina cu ';' la final de linie; scriptul de
--  instalare imparte fisierul dupa aceasta regula.
-- =====================================================================

DROP TABLE IF EXISTS tranzactii_plata;
DROP TABLE IF EXISTS istoric_status;
DROP TABLE IF EXISTS comenzi_produse;
DROP TABLE IF EXISTS comenzi;
DROP TABLE IF EXISTS cupoane;
DROP TABLE IF EXISTS recenzii;
DROP TABLE IF EXISTS favorite;
DROP TABLE IF EXISTS produse_asociate;
DROP TABLE IF EXISTS specificatii;
DROP TABLE IF EXISTS produse;
DROP TABLE IF EXISTS categorii;
DROP TABLE IF EXISTS utilizatori;

-- ---------------------------------------------------------------------
--  Utilizatori: clienti si administratori.
--  Parola este stocata exclusiv sub forma de hash (password_hash/bcrypt).
-- ---------------------------------------------------------------------
CREATE TABLE utilizatori (
    id            {PK},
    nume          VARCHAR(120) NOT NULL,
    email         VARCHAR(160) NOT NULL UNIQUE,
    parola_hash   VARCHAR(255) NOT NULL,
    telefon       VARCHAR(20),
    rol           VARCHAR(10)  NOT NULL DEFAULT 'client' CHECK (rol IN ('client', 'admin')),
    creat_la      VARCHAR(19)  NOT NULL
);

-- ---------------------------------------------------------------------
--  Categorii de produse (raioane).
-- ---------------------------------------------------------------------
CREATE TABLE categorii (
    id          {PK},
    nume        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    descriere   TEXT,
    ordine      INTEGER      NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------------------
--  Produse. Campul pret_vechi, daca este completat si mai mare decat
--  pretul curent, indica o reducere afisata cu pretul vechi taiat.
-- ---------------------------------------------------------------------
CREATE TABLE produse (
    id            {PK},
    categorie_id  INTEGER       NOT NULL REFERENCES categorii(id),
    cod_sku       VARCHAR(40)   NOT NULL UNIQUE,
    nume          VARCHAR(200)  NOT NULL,
    slug          VARCHAR(200)  NOT NULL UNIQUE,
    descriere     TEXT          NOT NULL,
    pret          NUMERIC(12,2) NOT NULL CHECK (pret >= 0),
    pret_vechi    NUMERIC(12,2),
    stoc          INTEGER       NOT NULL DEFAULT 0 CHECK (stoc >= 0),
    unitate       VARCHAR(30)   NOT NULL DEFAULT 'buc',
    greutate_kg   NUMERIC(8,2)  NOT NULL DEFAULT 1,
    imagine       VARCHAR(255),
    recomandat    SMALLINT      NOT NULL DEFAULT 0,
    activ         SMALLINT      NOT NULL DEFAULT 1,
    creat_la      VARCHAR(19)   NOT NULL
);

CREATE INDEX idx_produse_categorie ON produse(categorie_id);

-- ---------------------------------------------------------------------
--  Specificatii tehnice (putere, tensiune, dimensiuni, material ...).
--  Folosite pe pagina produsului si in comparatorul de produse.
-- ---------------------------------------------------------------------
CREATE TABLE specificatii (
    id         {PK},
    produs_id  INTEGER      NOT NULL REFERENCES produse(id) ON DELETE CASCADE,
    nume       VARCHAR(80)  NOT NULL,
    valoare    VARCHAR(160) NOT NULL,
    ordine     INTEGER      NOT NULL DEFAULT 0
);

CREATE INDEX idx_specificatii_produs ON specificatii(produs_id);

-- ---------------------------------------------------------------------
--  Accesorii compatibile / produse recomandate (A6).
-- ---------------------------------------------------------------------
CREATE TABLE produse_asociate (
    produs_id   INTEGER NOT NULL REFERENCES produse(id) ON DELETE CASCADE,
    asociat_id  INTEGER NOT NULL REFERENCES produse(id) ON DELETE CASCADE,
    PRIMARY KEY (produs_id, asociat_id)
);

-- ---------------------------------------------------------------------
--  Lista de favorite a clientilor autentificati (A9).
-- ---------------------------------------------------------------------
CREATE TABLE favorite (
    utilizator_id  INTEGER     NOT NULL REFERENCES utilizatori(id) ON DELETE CASCADE,
    produs_id      INTEGER     NOT NULL REFERENCES produse(id)     ON DELETE CASCADE,
    adaugat_la     VARCHAR(19) NOT NULL,
    PRIMARY KEY (utilizator_id, produs_id)
);

-- ---------------------------------------------------------------------
--  Recenzii: un client poate evalua o singura data un produs cumparat.
-- ---------------------------------------------------------------------
CREATE TABLE recenzii (
    id             {PK},
    produs_id      INTEGER     NOT NULL REFERENCES produse(id)     ON DELETE CASCADE,
    utilizator_id  INTEGER     NOT NULL REFERENCES utilizatori(id) ON DELETE CASCADE,
    nota           SMALLINT    NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentariu     TEXT        NOT NULL,
    creat_la       VARCHAR(19) NOT NULL,
    UNIQUE (produs_id, utilizator_id)
);

-- ---------------------------------------------------------------------
--  Coduri de reducere (A8).
--    tip = 'procent' -> valoare reprezinta procentul (ex. 10 = 10%)
--    tip = 'valoare' -> valoare reprezinta o suma fixa in RON
-- ---------------------------------------------------------------------
CREATE TABLE cupoane (
    id               {PK},
    cod              VARCHAR(30)   NOT NULL UNIQUE,
    descriere        VARCHAR(200)  NOT NULL DEFAULT '',
    tip              VARCHAR(10)   NOT NULL CHECK (tip IN ('procent', 'valoare')),
    valoare          NUMERIC(12,2) NOT NULL CHECK (valoare > 0),
    minim_comanda    NUMERIC(12,2) NOT NULL DEFAULT 0,
    valabil_pana     VARCHAR(10),
    utilizari_max    INTEGER,
    utilizari        INTEGER       NOT NULL DEFAULT 0,
    activ            SMALLINT      NOT NULL DEFAULT 1
);

-- ---------------------------------------------------------------------
--  Comenzi. Datele clientului sunt copiate in comanda pentru ca
--  documentul sa ramana valabil chiar daca contul este modificat.
-- ---------------------------------------------------------------------
CREATE TABLE comenzi (
    id              {PK},
    numar           VARCHAR(20)   NOT NULL UNIQUE,
    utilizator_id   INTEGER       REFERENCES utilizatori(id) ON DELETE SET NULL,
    nume_client     VARCHAR(120)  NOT NULL,
    telefon         VARCHAR(20)   NOT NULL,
    email           VARCHAR(160)  NOT NULL,
    adresa          VARCHAR(255)  NOT NULL,
    oras            VARCHAR(80)   NOT NULL,
    judet           VARCHAR(40)   NOT NULL,
    cod_postal      VARCHAR(10)   NOT NULL DEFAULT '',
    metoda_livrare  VARCHAR(20)   NOT NULL CHECK (metoda_livrare IN ('curier', 'ridicare')),
    metoda_plata    VARCHAR(20)   NOT NULL CHECK (metoda_plata IN ('ramburs', 'card')),
    stare_plata     VARCHAR(20)   NOT NULL DEFAULT 'neplatita'
                    CHECK (stare_plata IN ('neplatita', 'platita', 'respinsa', 'rambursata')),
    subtotal        NUMERIC(12,2) NOT NULL,
    discount        NUMERIC(12,2) NOT NULL DEFAULT 0,
    cost_livrare    NUMERIC(12,2) NOT NULL DEFAULT 0,
    total           NUMERIC(12,2) NOT NULL,
    cod_cupon       VARCHAR(30),
    awb             VARCHAR(30),
    observatii      TEXT          NOT NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'noua'
                    CHECK (status IN ('noua', 'confirmata', 'expediata', 'livrata', 'anulata')),
    creat_la        VARCHAR(19)   NOT NULL,
    actualizat_la   VARCHAR(19)   NOT NULL
);

CREATE INDEX idx_comenzi_utilizator ON comenzi(utilizator_id);
CREATE INDEX idx_comenzi_status     ON comenzi(status);

-- ---------------------------------------------------------------------
--  Liniile comenzii. Numele si pretul sunt "inghetate" la momentul
--  plasarii comenzii (modificarile ulterioare ale catalogului nu le
--  afecteaza).
-- ---------------------------------------------------------------------
CREATE TABLE comenzi_produse (
    id            {PK},
    comanda_id    INTEGER       NOT NULL REFERENCES comenzi(id) ON DELETE CASCADE,
    produs_id     INTEGER       REFERENCES produse(id) ON DELETE SET NULL,
    nume_produs   VARCHAR(200)  NOT NULL,
    cod_sku       VARCHAR(40)   NOT NULL,
    pret_unitar   NUMERIC(12,2) NOT NULL,
    cantitate     INTEGER       NOT NULL CHECK (cantitate > 0),
    total_linie   NUMERIC(12,2) NOT NULL
);

CREATE INDEX idx_comenzi_produse_comanda ON comenzi_produse(comanda_id);

-- ---------------------------------------------------------------------
--  Istoricul modificarilor de status (trasabilitate).
-- ---------------------------------------------------------------------
CREATE TABLE istoric_status (
    id          {PK},
    comanda_id  INTEGER      NOT NULL REFERENCES comenzi(id) ON DELETE CASCADE,
    status      VARCHAR(20)  NOT NULL,
    nota        VARCHAR(255) NOT NULL DEFAULT '',
    creat_la    VARCHAR(19)  NOT NULL
);

-- ---------------------------------------------------------------------
--  Tranzactii efectuate prin procesatorul de plati de test (A1).
--  Nu se stocheaza NICIODATA numarul complet al cardului sau CVV-ul.
-- ---------------------------------------------------------------------
CREATE TABLE tranzactii_plata (
    id              {PK},
    comanda_id      INTEGER       NOT NULL REFERENCES comenzi(id) ON DELETE CASCADE,
    referinta       VARCHAR(40)   NOT NULL UNIQUE,
    suma            NUMERIC(12,2) NOT NULL,
    stare           VARCHAR(20)   NOT NULL,
    mesaj           VARCHAR(255)  NOT NULL DEFAULT '',
    card_ultimele4  VARCHAR(4),
    creat_la        VARCHAR(19)   NOT NULL
);
