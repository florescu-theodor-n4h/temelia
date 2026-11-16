# TEMELIA – Magazin online de materiale de construcții

Proiect individual de comerț electronic: **„aplicație de comerț electronic pentru materiale de construcții”**.
TEMELIA este o marcă fictivă (nume, siglă și design proprii) pentru un magazin online de tip *bricolaj / materiale de construcții*,
cu 6 raioane și 26 de produse.

| | |
|---|---|
| **Student** | Florescu Theodor |
| **Temă** | Magazin online de materiale de construcții, scule și instalații |
| **Site publicat** | _(de completat: adresa HTTPS a site-ului găzduit)_ |
| **Depozit** | _(de completat: adresa GitHub / GitLab)_ |

---

## 1. Tehnologii folosite

| Componentă | Alegere |
|---|---|
| Limbaj server | **PHP 7** (compatibil PHP 7.0 – 8.4), fără framework |
| Bază de date | **SQLite** (implicit) – schema este portabilă și pe **PostgreSQL** sau **MySQL/MariaDB** prin PDO |
| Interfață | **XHTML 1.0 Transitional** + CSS, aspect „enterprise” clasic, **fără JavaScript** |
| Dependențe | **niciuna** – fără Composer, fără biblioteci externe, fără Docker |
| Extensii PHP necesare | `pdo`, `pdo_sqlite` (sau `pdo_pgsql` / `pdo_mysql`); `mbstring` este opțională |
| Curierat | un singur API de curier: **shim RapidCurier** (`src/CurierShim.php`) |
| Plăți | procesator de plăți cu cardul **de test** (`src/PlataDummy.php`) |

Toate comentariile din cod (PHPDoc / „javadoc”) și mesajele de commit sunt în limba română.

## 2. Structura proiectului

```
public/                 rădăcina web (DocumentRoot)
  index.php             pagina principală
  catalog.php           catalog: căutare, filtre, sortare, paginare
  produs.php            detalii produs, specificații, recomandări, recenzii
  cos.php               coșul de cumpărături + cod de reducere
  finalizare.php        finalizarea comenzii (checkout)
  plata.php             plata cu cardul (procesator de test)
  confirmare.php        confirmarea comenzii
  urmarire.php          urmărire comandă / colet (AWB)
  favorite.php          lista de favorite
  comparare.php         comparator de produse
  cookie.php            consimțământ cookie (fără JavaScript)
  termeni.php, confidentialitate.php, cookies.php, retur.php, livrare.php, contact.php
  sitemap.php, robots.txt
  cont/                 autentificare, înregistrare, deconectare, istoric comenzi
  admin/                tablou de bord, produse, comenzi, categorii, cupoane
  api/                  API REST (produse, comenzi) + expunerea HTTP a curierului și a plăților
  css/, img/            stil și imagini (SVG)
src/                    clase și șabloane (în afara rădăcinii web)
  bootstrap.php         inițializare: autoload, sesiune securizată, antete HTTP
  Baza.php              acces PDO independent de motorul SQL
  Produse.php, Comenzi.php, Cos.php, Cupoane.php, Favorite.php, Autentificare.php
  Calculator.php        calcule financiare (în bani, fără erori de rotunjire)
  Validator.php         validare pe server
  CurierShim.php        shim pentru API-ul firmei de curierat
  PlataDummy.php        procesator de plăți de test
  Api.php               utilitare REST
  sabloane/             antet, subsol, fragmente reutilizabile
sql/
  schema.sql            schema portabilă (SQLite / PostgreSQL / MySQL)
  date_initiale.sql     categorii, produse, specificații, accesorii, cupoane
  instalare.php         script de instalare
teste/ruleaza.php       teste automate (fără PHPUnit)
date/                   fișierul bazei SQLite (generat, neversionat)
jurnal/                 jurnalul aplicației (neversionat)
```

## 3. Instalare și rulare locală

Cerințe: PHP 7.0+ cu extensia `pdo_sqlite`. Nu este necesar niciun server de baze de date.

```bash
# Debian / Ubuntu / Raspberry Pi OS (inclusiv ARM): doar PHP CLI + SQLite
sudo apt-get install --no-install-recommends php-cli php-sqlite3

git clone <adresa-depozitului> temelia && cd temelia
php sql/instalare.php                  # creeaza date/temelia.sqlite cu date de test
php -S localhost:8000 -t public        # server de dezvoltare integrat in PHP
```

Deschideți <http://localhost:8000>.

- Reinstalare (șterge datele): `php sql/instalare.php --forta`
- Fără comenzile demonstrative: `php sql/instalare.php --fara-demo`
- Teste automate: `php teste/ruleaza.php`

### Folosirea PostgreSQL sau MySQL (opțional)

Copiați `src/config.local.php.exemplu` în `src/config.local.php` și completați secțiunea `db`
(`driver` = `pgsql` sau `mysql`), sau folosiți variabile de mediu (`TEMELIA_DB_DRIVER`, `TEMELIA_DB_HOST`,
`TEMELIA_DB_NUME`, `TEMELIA_DB_USER`, `TEMELIA_DB_PAROLA`). Apoi rulați `php sql/instalare.php`.

### Publicare (găzduire)

Rădăcina web trebuie să fie directorul `public/`. Directoarele `date/` și `jurnal/` trebuie să permită scrierea de către
serverul web. Activați HTTPS (certificat Let's Encrypt, oferit gratuit de majoritatea furnizorilor) și setați
`url_baza` în `src/config.local.php` (ex. `https://temelia.example.ro`). Cheia API și cheia procesatorului de plăți trebuie
schimbate în producție.

## 4. Conturi de test

| Rol | E-mail | Parolă |
|---|---|---|
| Administrator | `admin@temelia.ro` | `Admin2026!` |
| Client | `client@temelia.ro` | `Client2026!` |

Date de test suplimentare:

| Element | Valoare |
|---|---|
| Card aprobat | `4111 1111 1111 1111` (orice dată viitoare, orice CVV de 3 cifre) |
| Card respins – fonduri insuficiente | `4000 0000 0000 0002` |
| Card respins – card blocat | `4000 0000 0000 0069` |
| Cupon 10% | `BINEVENIT10` |
| Cupon 50 lei (comenzi peste 500 lei) | `SANTIER50` |
| Cupon expirat (pentru demonstrație) | `VARA2024` |
| Produs fără stoc | „Bandă de mascare 48 mm x 50 m” |
| Cheie API (antet `X-Cheie-API`) | `temelia-cheie-test-2026` |

## 5. Cerințe implementate

### Nivelul 1
- [x] Pagina principală: nume, siglă, meniu cu categorii, produse recomandate, oferte, noutăți
- [x] Catalog: 26 de produse în 6 categorii, afișare pe categorii
- [x] Pagina produsului: nume, imagine, descriere, preț în lei cu TVA, stoc, buton „Adaugă în coș”
- [x] Coș: adăugare, modificarea cantităților, eliminare, subtotal și total; coșul se păstrează în sesiune
- [x] Finalizare comandă: nume, telefon, e-mail, adresă, metodă de livrare, metodă de plată (ramburs / card), validare
- [x] Confirmare: numărul comenzii și rezumatul ei
- [x] Administrare protejată prin parolă: adăugare / editare / ștergere produse, imagini, prețuri, stocuri
- [x] Gestionarea comenzilor: listă și detalii
- [x] Bază de date pentru produse și comenzi
- [x] Design adaptat pentru telefoane (media queries CSS)
- [x] Depozit Git cu README

### Nivelul 2
- [x] Înregistrare, autentificare, deconectare; parole stocate ca hash (`password_hash` / bcrypt)
- [x] Două roluri: client și administrator; clienții primesc 403 în `/admin`
- [x] Istoricul comenzilor clientului, cu statusul fiecăreia
- [x] Statusuri: Nouă, Confirmată, Expediată, Livrată, Anulată; administratorul le modifică
- [x] Stocul scade automat la plasarea comenzii (UPDATE atomic) și este restituit la anulare
- [x] Produsele fără stoc nu pot fi comandate
- [x] Căutare după nume, filtrare după categorie și interval de preț, sortare după preț și după noutate
- [x] Reduceri: preț vechi tăiat, preț nou, procent de reducere
- [x] Validare pe server (`src/Validator.php`)
- [x] Protecție SQL injection: exclusiv interogări pregătite PDO, escapare LIKE, sortare din listă albă
- [x] Protecție XSS: escapare `htmlspecialchars` la afișare + antet Content-Security-Policy (`script-src 'none'`)
- [x] Protecție suplimentară: jetoane CSRF pe toate formularele, sesiune `HttpOnly` / `SameSite`, regenerarea ID-ului de sesiune,
  limitarea încercărilor de autentificare, blocarea redirecționărilor deschise, validarea imaginilor încărcate
- [x] Pagini legale: Termeni și condiții, Politica de confidențialitate (GDPR), Politica de cookie-uri,
  Politica de retur (14 zile), Contact cu datele firmei
- [x] Banner cookie cu „Accept” și „Refuz” (funcționează fără JavaScript)
- [x] Legături către ANPC (SAL) și SOL în subsol
- [ ] Publicare online cu HTTPS – _de realizat la găzduire (vezi secțiunea 3)_

## 6. Funcționalități avansate (nivelul 3)

| Cod | Funcționalitate | Implementare |
|---|---|---|
| **A1** | Plăți online | Procesator de test cu verificare Luhn, dată de expirare, CVV și coduri de răspuns; se păstrează doar ultimele 4 cifre ale cardului (`src/PlataDummy.php`, `public/plata.php`, `public/api/plata.php`) |
| **A3** | API REST | `api/produse.php` (public), `api/comenzi.php` (cu cheie API, GET + POST pentru status), `api/curier.php` |
| **A4** | Recenzii și note | Doar clienții care au cumpărat produsul; notă medie afișată cu stele |
| **A6** | Recomandări de produse | Accesorii compatibile definite de administrator, completate cu produse „cumpărate împreună” și din aceeași categorie |
| **A8** | Coduri de reducere | Procentuale sau valorice, prag minim, dată de expirare, număr maxim de utilizări; administrare completă |
| **A9** | Favorite și comparare | Lista de favorite (în baza de date) și comparator de până la 4 produse, după specificațiile tehnice |
| **A12** | SEO | Adrese cu slug, `meta description` per pagină, `canonical`, `sitemap.php`, `robots.txt` |
| **A15** | Tablou de bord al vânzărilor | Cifră de afaceri, comenzi, coș mediu, top produse, vânzări pe raioane, grafic pe 14 zile (fără JavaScript), stocuri reduse |
| **A16** | Teste automate | `teste/ruleaza.php`: 82 de verificări (calcule, cupoane, validări, plăți, curier, coș, comenzi, stoc, injecție SQL) pe o bază SQLite temporară |

Elemente specifice magazinului de materiale de construcții: specificații tehnice (putere, tensiune, dimensiuni,
material), unități de vânzare (sac 40 kg, colac 100 m, pachet 6 mp), greutate per unitate folosită la tariful de curierat,
transport paletizat pentru comenzile de peste 100 kg, accesorii compatibile.

### Integrarea cu firma de curierat (shim)

`src/CurierShim.php` imită contractul API-ului unei firme de curierat fictive (RapidCurier):
`/v1/tarif` (cost după greutate, județ și ramburs; gratuit peste 1.500 lei), `/v1/awb` (generare AWB la expedierea comenzii)
și `/v1/urmarire` (istoricul coletului). Pentru o integrare reală trebuie rescrisă doar metoda `transport()`.

## 7. Declarație privind utilizarea instrumentelor AI

La realizarea proiectului a fost folosit asistentul **Claude Code (Anthropic)** pentru generarea structurii aplicației,
a codului sursă și a comentariilor, a datelor de test, a testelor automate și a acestui fișier README.
Codul a fost verificat prin testele automate și prin parcurgerea manuală a fluxurilor (coș, comandă, plată, administrare).
_(Completați aici, după caz, ce ați modificat sau verificat personal.)_

---
*Proiect didactic. Comenzile nu sunt onorate, iar plățile sunt procesate exclusiv în mediu de test.*
