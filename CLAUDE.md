# CLAUDE.md

Regulile complete ale proiectului se afla in `AGENTS.md` si sunt importate mai jos.
Acest fisier contine doar precizarile specifice Claude Code.

@AGENTS.md

## Precizari pentru Claude Code

- **Mediul este fragil** (Debian ARM in proot): PostgreSQL a blocat de doua ori sistemul.
  Nu porniti servicii de sistem si nu instalati pachete fara acordul utilizatorului.
  Comenzile de lunga durata se ruleaza cu `timeout`; serverul `php -S` se opreste la final.
- Fisierele temporare (scripturi de verificare, jurnale de server) se scriu in directorul
  scratchpad al sesiunii, nu in depozit.
- Lucrati in micro-commit-uri, cu mesaje in limba romana; incheiati fiecare mesaj de commit
  cu linia de atribuire `Co-Authored-By` ceruta de harness.
- Dupa modificari in `src/`, rulati `php teste/ruleaza.php` si raportati rezultatul exact
  (numarul de teste trecute / esuate).
- Pentru verificarea paginilor: instalati baza (`php sql/instalare.php --forta`), porniti
  `timeout 60 php -S 127.0.0.1:8765 -t public` in fundal, verificati cu `curl`, apoi opriti serverul.
- Raspundeti utilizatorului in limba in care v-a scris; codul si commit-urile raman in romana.
