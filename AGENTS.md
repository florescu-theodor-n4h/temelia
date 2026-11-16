# AGENTS.md - instructiuni pentru agentii de programare

Acest fisier este sursa unica de reguli pentru orice asistent AI care lucreaza in depozit
(Claude Code, GitHub Copilot, Cursor, Codex, Aider etc.). `CLAUDE.md`,
`.github/copilot-instructions.md` si `llms.txt` trimit aici; la orice schimbare de regula,
actualizati mai intai acest fisier.

## Proiectul

**TEMELIA** - magazin online de materiale de constructii (proiect didactic individual).
PHP fara framework, baza de date prin PDO (SQLite implicit), interfata XHTML 1.0 fara JavaScript.
Cerintele academice (nivelurile 1-3, functionalitatile A1-A16) sunt bifate in `README.md`;
nu stergeti functionalitati bifate acolo fara a actualiza README-ul.

## Reguli obligatorii (nu se negociaza)

1. **NU porniti PostgreSQL** si niciun alt server/daemon de baze de date. Pe masina de
   dezvoltare (Debian ARM / proot) PostgreSQL a blocat de doua ori intregul sistem.
   Folositi exclusiv SQLite pentru rulare si teste. Nu rulati `pg_ctlcluster`,
   `pg_createcluster`, `initdb`, `service postgresql` si nu instalati pachete de server SQL.
2. **Nu adaugati dependinte**: fara Composer, npm, biblioteci externe, CDN-uri sau Docker.
   Docker este interzis explicit.
3. **Compatibilitate PHP 7.0+** (testat pe 8.4). Interzise: `match`, `?->`, `fn () =>`,
   tipuri nullable (`?int`), `void`, tipuri union, atribute `#[...]`, `readonly`, `enum`,
   proprietati tipizate, argumente numite, virgule finale in *apeluri* de functii,
   `str_contains` / `str_starts_with` / `str_ends_with`. Folositi `array()` in loc de `[]`
   pentru consecventa cu codul existent.
4. **Limba romana** pentru tot ce scrie agentul in cod: comentarii PHPDoc (stil "javadoc")
   pe fiecare clasa, metoda si functie (`@param`, `@return`, `@throws`), comentarii de linie,
   mesaje pentru utilizator, nume de variabile/functii/tabele. Fara diacritice in cod si SQL
   (ex. `Adaugare`, nu `Adăugare`); README-ul poate avea diacritice.
5. **Micro-commit-uri in limba romana**: un commit per schimbare logica, mesaj la imperativ
   nominal, ex. `Adaugare filtru dupa stoc in catalog`, `Corectare calcul TVA la livrare`.
   Nu faceti push fara cererea explicita a utilizatorului.
6. **XHTML 1.0 Transitional valid**: toate etichetele inchise (`<br />`, `<img ... />`),
   atribute cu ghilimele si valori explicite (`checked="checked"`, `selected="selected"`),
   fara elemente HTML5 (`<nav>`, `<section>`, `formnovalidate` ...), fara `<form>` in `<p>`.
7. **Fara JavaScript si fara stiluri inline**: antetul CSP este `script-src 'none'; style-src 'self'`.
   Orice atribut `style="..."` sau `onclick` este blocat de browser. Folositi clase in
   `public/css/stil.css`; dimensiunile dinamice se dau prin atributele `width`/`height` ale imaginilor.
8. **SQL portabil** (SQLite / PostgreSQL / MySQL): fara `RETURNING`, `NOW()`, `INTERVAL`,
   `ILIKE`, `TRUE`/`FALSE`, `SERIAL`, `FOR UPDATE`, `CASCADE` in schema. Valori logice = `SMALLINT` 0/1.
   Datele calendaristice se genereaza in PHP cu `Baza::acum()` (format `Y-m-d H:i:s`).
   Cheia primara in `sql/schema.sql` se scrie `{PK}` (inlocuita de `sql/instalare.php`).

## Comenzi

```bash
php sql/instalare.php --forta      # recreeaza date/temelia.sqlite cu date demonstrative
php teste/ruleaza.php              # teste automate (SQLite temporar); cod iesire 0 = succes
php -l <fisier>.php                # verificare sintaxa
timeout 60 php -S 127.0.0.1:8000 -t public   # server de dezvoltare - DOAR cu timeout
```

Porniti serverul integrat numai cu `timeout` si opriti-l la final (`ps -eo pid,cmd | grep "[p]hp -S"`).
Inainte de fiecare commit: `php -l` pe fisierele modificate si `php teste/ruleaza.php` daca ati atins `src/`.

Conturi de test: `admin@temelia.ro` / `Admin2026!`, `client@temelia.ro` / `Client2026!`.

## Arhitectura

```
public/      radacina web; cate un fisier PHP per pagina (fara router)
  admin/     panou de administrare (rol 'admin')
  cont/      autentificare, inregistrare, istoric comenzi
  api/       endpoint-uri JSON (produse, comenzi, curier, plata)
src/         clase (autoload NumeClasa.php) + sabloane/ ; in afara radacinii web
sql/         schema portabila, date initiale, script de instalare
teste/       ruleaza.php - mini-cadru de testare propriu
date/        fisierul SQLite (neversionat)   jurnal/  aplicatie.log (neversionat)
```

Fiecare pagina incepe cu `require __DIR__ . '/../src/bootstrap.php';` (adancimea caii conteaza)
si se termina cu sablonul de subsol. Schema unei pagini cu formular:

```php
if (este_post()) {
    csrf_verifica();
    $v = new Validator($_POST);
    $v->obligatoriu('nume', 'Numele')->lungime('nume', 'Numele', 3, 120);
    if ($v->esteValid()) {
        // ... modificare date ...
        flash('succes', 'Mesaj pentru utilizator.');
        redirectioneaza('pagina.php');        // Post/Redirect/Get
    }
    $erori = $v->erori();
}
$titlu = 'Titlul paginii';
require DIR_SRC . '/sabloane/antet.php';     // admin: admin_antet.php (verifica rolul)
// ... XHTML ...
require DIR_SRC . '/sabloane/subsol.php';
```

| Clasa / fisier | Responsabilitate |
|---|---|
| `Baza` | PDO; `toate`, `unul`, `valoare`, `executa`, `insereaza`, `tranzactie`, `acum`, `driver` |
| `Produse` | catalog, cautare/filtrare/sortare, recomandari (A6), administrare produse |
| `Comenzi` | plasare atomica, statusuri (anulare = restituire stoc, expediere = AWB), statistici (A15) |
| `Cos` | cos in `$_SESSION['cos']` (produs_id => cantitate); `rezumat()` calculeaza totalurile |
| `Calculator` | calcule pure in bani (intregi); cupoane, TVA, reduceri - fara acces la BD |
| `Validator` | validare pe server cu metode inlantuite |
| `Autentificare` | conturi, `password_hash`, roluri, `cereAutentificare()` / `cereAdmin()` |
| `CurierShim` | singurul API de curier (RapidCurier simulat): `/v1/tarif`, `/v1/awb`, `/v1/urmarire` |
| `PlataDummy` | procesator de plati de test (Luhn, expirare, CVV, coduri de raspuns) |
| `Favorite` | favorite (BD), comparator (sesiune), recenzii |
| `Api` | raspunsuri JSON, verificarea antetului `X-Cheie-API` |
| `functii.php` / `vizual.php` | utilitare (`e`, `url`, `pret`, CSRF, flash) si fragmente XHTML (`html_*`) |

## Securitate - lista de verificare

- Orice valoare afisata trece prin `e()`; nu scrieti niciodata `echo $_GET[...]`.
- Orice interogare foloseste marcaje `?` si `Baza::*`; nu concatenati date de intrare in SQL.
  Coloanele de sortare provin doar din liste albe (`Produse::sortari()`).
  Pentru `LIKE` folositi escaparea cu `!` si clauza `ESCAPE '!'` (vezi `Produse::escapeLike`).
- Orice formular POST contine `csrf_camp()`, iar handler-ul apeleaza `csrf_verifica()`.
- Paginile din `admin/` includ `admin_antet.php` sau apeleaza `Autentificare::cereAdmin()`
  **inainte** de orice modificare de date.
- Redirectionarile catre adrese primite din exterior trec prin `adresa_locala()`.
- Preturile se citesc mereu din BD, niciodata din formular; sumele se calculeaza cu `Calculator`.
- Stocul se scade doar prin `UPDATE ... SET stoc = stoc - ? WHERE id = ? AND stoc >= ?` in tranzactie.
- Nu se salveaza niciodata numarul complet al cardului sau CVV-ul.
- Imaginile incarcate se valideaza cu `getimagesize()` si primesc nume aleator.

## Stil de cod

- Indentare 4 spatii, acolade pe linie noua pentru clase si metode, pe aceeasi linie pentru structuri de control.
- Clase cu metode statice (stilul existent); fara namespace-uri.
- Fisierele care contin doar PHP nu au `?>` final.
- Mesajele pentru utilizator: limba romana, ton profesional, formula de politete "dumneavoastra".
- Sume afisate cu `pret()` (`1.234,50 lei`), date cu `data_ro()`.

## Ce sa NU faceti

- Nu porniti PostgreSQL / MySQL si nu lasati servere pornite in fundal.
- Nu introduceti JavaScript, framework-uri CSS, fonturi web sau resurse externe.
- Nu modificati `date/`, `jurnal/` sau `src/config.local.php` in commit-uri.
- Nu rescrieti istoricul Git si nu faceti commit-uri mari care amesteca mai multe schimbari.
- Nu copiati numele, sigla sau designul unor magazine reale (marca TEMELIA este fictiva).
