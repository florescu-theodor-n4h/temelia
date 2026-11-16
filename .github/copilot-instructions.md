# Instructiuni GitHub Copilot - proiectul TEMELIA

Regulile complete se afla in [`AGENTS.md`](../AGENTS.md). Rezumatul de mai jos este
suficient pentru completarile de cod; in caz de conflict, `AGENTS.md` are prioritate.

## Context
Magazin online de materiale de constructii: PHP fara framework, PDO cu SQLite implicit
(schema portabila PostgreSQL/MySQL), interfata XHTML 1.0 Transitional fara JavaScript.

## Reguli pentru codul generat
- **PHP 7.0+**: fara `match`, `?->`, `fn () =>`, `?int`, `void`, tipuri union, atribute,
  proprietati tipizate, argumente numite, `str_contains`. Folositi `array()`, nu `[]`.
- **Fara dependinte**: fara Composer, npm, CDN, Docker.
- **Limba romana** pentru comentarii PHPDoc (`@param`, `@return`) pe fiecare functie si metoda,
  pentru identificatori si pentru mesajele catre utilizator; fara diacritice in cod.
- **XHTML valid**: `<br />`, `<img ... />`, `checked="checked"`; fara elemente HTML5.
- **Fara `<script>`, `style="..."` sau `onclick`**: politica CSP le blocheaza. Stilurile merg in `public/css/stil.css`.
- **SQL portabil**: fara `RETURNING`, `NOW()`, `TRUE`/`FALSE`, `ILIKE`, `FOR UPDATE`.
  Folositi `Baza::insereaza()` pentru ID-ul inserat si `Baza::acum()` pentru date.

## Tipare existente care trebuie refolosite
- Afisare: `<?php echo e($valoare); ?>` - niciodata date neescapate.
- Interogari: `Baza::toate('SELECT ... WHERE id = ?', array($id))` - niciodata concatenare.
- Formulare: `csrf_camp()` in formular, `csrf_verifica()` + `Validator` in handler,
  apoi `flash()` si `redirectioneaza()` (Post/Redirect/Get).
- Administrare: `Autentificare::cereAdmin()` inainte de orice modificare.
- Bani: `Calculator` (calcule in bani intregi), afisare cu `pret()`.
- Fragmente XHTML: `html_pret()`, `html_stoc()`, `html_card_produs()`, `html_erori()` din `src/vizual.php`.

## Verificare
`php -l fisier.php` si `php teste/ruleaza.php`. Nu porniti PostgreSQL: blocheaza masina de dezvoltare.
