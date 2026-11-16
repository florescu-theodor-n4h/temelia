-- =====================================================================
--  TEMELIA Materiale de Constructii S.R.L.
--  Schema bazei de date PostgreSQL pentru magazinul online.
--
--  Fisierul poate fi rulat de mai multe ori: tabelele existente sunt
--  sterse in ordinea inversa a dependentelor si recreate.
--
--  Conventii:
--    - denumirile tabelelor si coloanelor sunt in limba romana;
--    - toate preturile sunt exprimate in RON si INCLUD TVA (21%);
--    - sumele monetare folosesc tipul NUMERIC(12,2) pentru precizie.
-- =====================================================================

BEGIN;

DROP TABLE IF EXISTS tranzactii_plata      CASCADE;
DROP TABLE IF EXISTS istoric_status        CASCADE;
DROP TABLE IF EXISTS comenzi_produse       CASCADE;
DROP TABLE IF EXISTS comenzi               CASCADE;
DROP TABLE IF EXISTS cupoane               CASCADE;
DROP TABLE IF EXISTS recenzii              CASCADE;
DROP TABLE IF EXISTS favorite              CASCADE;
DROP TABLE IF EXISTS produse_asociate      CASCADE;
DROP TABLE IF EXISTS specificatii          CASCADE;
DROP TABLE IF EXISTS produse               CASCADE;
DROP TABLE IF EXISTS categorii             CASCADE;
DROP TABLE IF EXISTS utilizatori           CASCADE;

-- ---------------------------------------------------------------------
--  Utilizatori: clienti si administratori.
--  Parola este stocata exclusiv sub forma de hash (password_hash/bcrypt).
-- ---------------------------------------------------------------------
CREATE TABLE utilizatori (
    id            SERIAL PRIMARY KEY,
    nume          VARCHAR(120) NOT NULL,
    email         VARCHAR(160) NOT NULL UNIQUE,
    parola_hash   VARCHAR(255) NOT NULL,
    telefon       VARCHAR(20),
    adresa        VARCHAR(255),
    oras          VARCHAR(80),
    judet         VARCHAR(40),
    cod_postal    VARCHAR(10),
    rol           VARCHAR(10)  NOT NULL DEFAULT 'client'
                  CHECK (rol IN ('client', 'admin')),
    creat_la      TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
--  Categorii de produse (raioane).
-- ---------------------------------------------------------------------
CREATE TABLE categorii (
    id          SERIAL PRIMARY KEY,
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
    id            SERIAL PRIMARY KEY,
    categorie_id  INTEGER       NOT NULL REFERENCES categorii(id) ON DELETE RESTRICT,
    cod_sku       VARCHAR(40)   NOT NULL UNIQUE,
    nume          VARCHAR(200)  NOT NULL,
    slug          VARCHAR(200)  NOT NULL UNIQUE,
    descriere     TEXT          NOT NULL DEFAULT '',
    pret          NUMERIC(12,2) NOT NULL CHECK (pret >= 0),
    pret_vechi    NUMERIC(12,2)          CHECK (pret_vechi IS NULL OR pret_vechi >= 0),
    stoc          INTEGER       NOT NULL DEFAULT 0 CHECK (stoc >= 0),
    unitate       VARCHAR(30)   NOT NULL DEFAULT 'buc',
    greutate_kg   NUMERIC(8,2)  NOT NULL DEFAULT 1 CHECK (greutate_kg >= 0),
    imagine       VARCHAR(255),
    recomandat    BOOLEAN       NOT NULL DEFAULT FALSE,
    activ         BOOLEAN       NOT NULL DEFAULT TRUE,
    creat_la      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_produse_categorie ON produse(categorie_id);
CREATE INDEX idx_produse_nume      ON produse(LOWER(nume));

-- ---------------------------------------------------------------------
--  Specificatii tehnice (putere, tensiune, dimensiuni, material ...).
--  Folosite pe pagina produsului si in comparatorul de produse.
-- ---------------------------------------------------------------------
CREATE TABLE specificatii (
    id         SERIAL PRIMARY KEY,
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
    PRIMARY KEY (produs_id, asociat_id),
    CHECK (produs_id <> asociat_id)
);

-- ---------------------------------------------------------------------
--  Lista de favorite a clientilor autentificati (A9).
-- ---------------------------------------------------------------------
CREATE TABLE favorite (
    utilizator_id  INTEGER   NOT NULL REFERENCES utilizatori(id) ON DELETE CASCADE,
    produs_id      INTEGER   NOT NULL REFERENCES produse(id)     ON DELETE CASCADE,
    adaugat_la     TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (utilizator_id, produs_id)
);

-- ---------------------------------------------------------------------
--  Recenzii: un client poate evalua o singura data un produs cumparat.
-- ---------------------------------------------------------------------
CREATE TABLE recenzii (
    id             SERIAL PRIMARY KEY,
    produs_id      INTEGER   NOT NULL REFERENCES produse(id)     ON DELETE CASCADE,
    utilizator_id  INTEGER   NOT NULL REFERENCES utilizatori(id) ON DELETE CASCADE,
    nota           SMALLINT  NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentariu     TEXT      NOT NULL DEFAULT '',
    creat_la       TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (produs_id, utilizator_id)
);

-- ---------------------------------------------------------------------
--  Coduri de reducere (A8).
--    tip = 'procent' -> valoare reprezinta procentul (ex. 10 = 10%)
--    tip = 'valoare' -> valoare reprezinta o suma fixa in RON
-- ---------------------------------------------------------------------
CREATE TABLE cupoane (
    id               SERIAL PRIMARY KEY,
    cod              VARCHAR(30)   NOT NULL UNIQUE,
    descriere        VARCHAR(200)  NOT NULL DEFAULT '',
    tip              VARCHAR(10)   NOT NULL CHECK (tip IN ('procent', 'valoare')),
    valoare          NUMERIC(12,2) NOT NULL CHECK (valoare > 0),
    minim_comanda    NUMERIC(12,2) NOT NULL DEFAULT 0,
    valabil_pana     DATE,
    utilizari_max    INTEGER,
    utilizari        INTEGER       NOT NULL DEFAULT 0,
    activ            BOOLEAN       NOT NULL DEFAULT TRUE
);

-- ---------------------------------------------------------------------
--  Comenzi. Datele clientului sunt copiate in comanda pentru ca
--  documentul sa ramana valabil chiar daca contul este modificat.
-- ---------------------------------------------------------------------
CREATE TABLE comenzi (
    id              SERIAL PRIMARY KEY,
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
    observatii      TEXT          NOT NULL DEFAULT '',
    status          VARCHAR(20)   NOT NULL DEFAULT 'noua'
                    CHECK (status IN ('noua', 'confirmata', 'expediata', 'livrata', 'anulata')),
    creat_la        TIMESTAMP     NOT NULL DEFAULT NOW(),
    actualizat_la   TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_comenzi_utilizator ON comenzi(utilizator_id);
CREATE INDEX idx_comenzi_status     ON comenzi(status);

-- ---------------------------------------------------------------------
--  Liniile comenzii. Numele si pretul sunt "inghetate" la momentul
--  plasarii comenzii (modificarile ulterioare ale catalogului nu le
--  afecteaza).
-- ---------------------------------------------------------------------
CREATE TABLE comenzi_produse (
    id            SERIAL PRIMARY KEY,
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
    id          SERIAL PRIMARY KEY,
    comanda_id  INTEGER     NOT NULL REFERENCES comenzi(id) ON DELETE CASCADE,
    status      VARCHAR(20) NOT NULL,
    nota        VARCHAR(255) NOT NULL DEFAULT '',
    creat_la    TIMESTAMP   NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
--  Tranzactii efectuate prin procesatorul de plati de test (A1).
--  Nu se stocheaza NICIODATA numarul complet al cardului sau CVV-ul.
-- ---------------------------------------------------------------------
CREATE TABLE tranzactii_plata (
    id           SERIAL PRIMARY KEY,
    comanda_id   INTEGER       NOT NULL REFERENCES comenzi(id) ON DELETE CASCADE,
    referinta    VARCHAR(40)   NOT NULL UNIQUE,
    suma         NUMERIC(12,2) NOT NULL,
    stare        VARCHAR(20)   NOT NULL,
    mesaj        VARCHAR(255)  NOT NULL DEFAULT '',
    card_ultimele4 VARCHAR(4),
    creat_la     TIMESTAMP     NOT NULL DEFAULT NOW()
);

COMMIT;
