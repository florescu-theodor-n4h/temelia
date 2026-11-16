-- =====================================================================
--  TEMELIA Materiale de Constructii S.R.L.
--  Date initiale: categorii, produse, specificatii, accesorii, cupoane.
--
--  Conturile de utilizator (administrator si client de test) NU sunt
--  definite aici, deoarece parolele trebuie generate cu password_hash();
--  ele sunt create de scriptul sql/instalare.php.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
--  Categorii (raioane)
-- ---------------------------------------------------------------------
INSERT INTO categorii (id, nume, slug, descriere, ordine) VALUES
(1, 'Scule si echipamente',    'scule-echipamente',    'Scule electrice, scule de mana, acumulatori si accesorii pentru santier si atelier.', 1),
(2, 'Materiale de constructii','materiale-constructii','Ciment, adezivi, zidarie, termoizolatii si tot ce trebuie pentru structura si finisaj.', 2),
(3, 'Instalatii electrice',    'instalatii-electrice', 'Cabluri, prize, intrerupatoare si aparataj de protectie.', 3),
(4, 'Instalatii sanitare',     'instalatii-sanitare',  'Tevi, fitinguri, robineti si baterii pentru baie si bucatarie.', 4),
(5, 'Vopsele si decoratiuni',  'vopsele-decoratiuni',  'Vopsele lavabile, pensule, trafalete si accesorii pentru zugraveli.', 5),
(6, 'Gradina si exterior',     'gradina-exterior',     'Unelte de gradina, furtunuri si sisteme de udare.', 6);

-- ---------------------------------------------------------------------
--  Produse (preturi in RON, cu TVA inclus)
-- ---------------------------------------------------------------------
INSERT INTO produse (id, categorie_id, cod_sku, nume, slug, descriere, pret, pret_vechi, stoc, unitate, greutate_kg, imagine, recomandat, creat_la) VALUES
(1, 1, 'TM-SC-001', 'Masina de gaurit cu percutie 750W', 'masina-gaurit-percutie-750w',
 'Masina de gaurit cu percutie, ideala pentru gaurire in beton, caramida, lemn si metal. Mandrina cu cheie de 13 mm, reglaj electronic al turatiei si functie de inversare a sensului de rotatie. Livrata cu maner auxiliar si limitator de adancime.',
 289.90, 349.90, 25, 'buc', 2.10, 'masina-gaurit-percutie-750w.svg', TRUE, NOW() - INTERVAL '40 days'),
(2, 1, 'TM-SC-002', 'Autofiletanta cu acumulator 18V 2Ah', 'autofiletanta-acumulator-18v',
 'Autofiletanta compacta cu acumulator Li-Ion 18V, doua trepte de viteza si 21 de pozitii de cuplu. Iluminare LED a zonei de lucru. Pachetul include acumulator 2Ah, incarcator si valiza de transport.',
 459.00, NULL, 14, 'buc', 1.60, 'autofiletanta-acumulator-18v.svg', TRUE, NOW() - INTERVAL '10 days'),
(3, 1, 'TM-SC-003', 'Set burghie beton SDS-Plus 8 piese', 'set-burghie-beton-sds-8',
 'Set de 8 burghie pentru beton cu prindere SDS-Plus, diametre 5-12 mm, varf din carbura de wolfram. Compatibile cu masini de gaurit cu percutie si ciocane rotopercutoare.',
 64.90, NULL, 80, 'set', 0.45, 'set-burghie-beton-sds-8.svg', FALSE, NOW() - INTERVAL '60 days'),
(4, 1, 'TM-SC-004', 'Acumulator Li-Ion 18V 4Ah', 'acumulator-li-ion-18v-4ah',
 'Acumulator de schimb Li-Ion 18V cu capacitate de 4Ah si indicator de nivel al incarcarii. Compatibil cu intreaga gama de scule cu acumulator 18V comercializate de TEMELIA.',
 219.00, NULL, 30, 'buc', 0.65, 'acumulator-li-ion-18v-4ah.svg', FALSE, NOW() - INTERVAL '25 days'),
(5, 1, 'TM-SC-005', 'Ciocan de dulgher 600 g', 'ciocan-dulgher-600g',
 'Ciocan de dulgher din otel forjat cu coada din fibra de sticla si manson anti-vibratii. Capat cu gheara pentru extragerea cuielor.',
 39.90, NULL, 60, 'buc', 0.80, 'ciocan-dulgher-600g.svg', FALSE, NOW() - INTERVAL '90 days'),

(6, 2, 'TM-MC-001', 'Ciment Portland CEM II/A-S 42,5R - sac 40 kg', 'ciment-portland-cem-ii-40kg',
 'Ciment Portland compozit cu zgura, clasa de rezistenta 42,5R, recomandat pentru betoane, mortare si sape. Ambalat in saci de 40 kg. Se vinde la bucata (sac); pentru cantitati de peste 50 de saci va rugam sa solicitati oferta de paletizare.',
 34.50, NULL, 500, 'sac 40 kg', 40.00, 'ciment-portland-cem-ii-40kg.svg', TRUE, NOW() - INTERVAL '120 days'),
(7, 2, 'TM-MC-002', 'Adeziv flexibil gresie si faianta C2TE - 25 kg', 'adeziv-flexibil-c2te-25kg',
 'Adeziv flexibil pe baza de ciment, clasa C2TE, pentru placi ceramice si gresie portelanata, la interior si exterior. Timp deschis extins si alunecare redusa.',
 52.90, 59.90, 220, 'sac 25 kg', 25.00, 'adeziv-flexibil-c2te-25kg.svg', FALSE, NOW() - INTERVAL '35 days'),
(8, 2, 'TM-MC-003', 'Vata minerala bazaltica 10 cm - pachet 6 mp', 'vata-minerala-bazaltica-10cm',
 'Placi din vata minerala bazaltica pentru izolarea termica si fonica a peretilor, mansardelor si planseelor. Clasa de reactie la foc A1 (incombustibil).',
 129.00, NULL, 90, 'pachet 6 mp', 12.00, 'vata-minerala-bazaltica-10cm.svg', FALSE, NOW() - INTERVAL '15 days'),
(9, 2, 'TM-MC-004', 'Polistiren expandat EPS 80 - 5 cm, pachet 10 mp', 'polistiren-eps80-5cm',
 'Placi de polistiren expandat EPS 80 pentru termoizolarea fatadelor. Dimensiune placa 1000 x 500 mm, grosime 5 cm.',
 89.00, NULL, 120, 'pachet 10 mp', 4.00, 'polistiren-eps80-5cm.svg', FALSE, NOW() - INTERVAL '50 days'),
(10, 2, 'TM-MC-005', 'Bloc BCA 625 x 250 x 200 mm', 'bloc-bca-625x250x200',
 'Bloc din beton celular autoclavizat pentru zidarie portanta si de compartimentare. Usor, bun izolator termic si simplu de prelucrat.',
 14.20, NULL, 1500, 'buc', 18.00, 'bloc-bca-625x250x200.svg', FALSE, NOW() - INTERVAL '200 days'),

(11, 3, 'TM-IE-001', 'Cablu MYYM 3 x 2,5 mmp - colac 100 m', 'cablu-myym-3x2-5-100m',
 'Cablu flexibil cu conductori din cupru si izolatie PVC, 3 x 2,5 mmp, pentru circuite de prize. Comercializat in colac de 100 m.',
 349.00, 399.00, 40, 'colac 100 m', 12.00, 'cablu-myym-3x2-5-100m.svg', TRUE, NOW() - INTERVAL '20 days'),
(12, 3, 'TM-IE-002', 'Priza dubla cu impamantare, alba', 'priza-dubla-impamantare-alba',
 'Priza dubla Schuko cu impamantare si protectie pentru copii, montaj ingropat, 16A / 250V.',
 18.50, NULL, 300, 'buc', 0.15, 'priza-dubla-impamantare-alba.svg', FALSE, NOW() - INTERVAL '70 days'),
(13, 3, 'TM-IE-003', 'Intrerupator simplu, alb', 'intrerupator-simplu-alb',
 'Intrerupator simplu pentru montaj ingropat, 10A / 250V, rama inclusa.',
 12.90, NULL, 280, 'buc', 0.10, 'intrerupator-simplu-alb.svg', FALSE, NOW() - INTERVAL '70 days'),
(14, 3, 'TM-IE-004', 'Siguranta automata 1P 16A curba C', 'siguranta-automata-1p-16a',
 'Intreruptor automat monopolar 16A, curba de declansare C, capacitate de rupere 6 kA, montaj pe sina DIN.',
 24.90, NULL, 150, 'buc', 0.12, 'siguranta-automata-1p-16a.svg', FALSE, NOW() - INTERVAL '5 days'),

(15, 4, 'TM-IS-001', 'Teava PPR 20 mm PN20 - bara 4 m', 'teava-ppr-20mm-4m',
 'Teava din polipropilena PPR PN20 pentru instalatii de apa calda si rece. Imbinare prin termofuziune.',
 11.90, NULL, 600, 'bara 4 m', 0.70, 'teava-ppr-20mm-4m.svg', FALSE, NOW() - INTERVAL '100 days'),
(16, 4, 'TM-IS-002', 'Baterie lavoar monocomanda, crom', 'baterie-lavoar-monocomanda-crom',
 'Baterie pentru lavoar cu cartus ceramic de 35 mm, aerator economizor de apa si finisaj cromat. Furtunuri flexibile incluse.',
 189.00, 229.00, 35, 'buc', 1.40, 'baterie-lavoar-monocomanda-crom.svg', TRUE, NOW() - INTERVAL '8 days'),
(17, 4, 'TM-IS-003', 'Set fitinguri PPR 20 mm - 30 piese', 'set-fitinguri-ppr-20mm',
 'Set de fitinguri PPR 20 mm: coturi, teuri, mufe si adaptoare filetate. Ideal pentru reparatii si instalatii noi.',
 69.00, NULL, 70, 'set', 0.90, 'set-fitinguri-ppr-20mm.svg', FALSE, NOW() - INTERVAL '45 days'),
(18, 4, 'TM-IS-004', 'Robinet coltar 1/2" x 3/8"', 'robinet-coltar-1-2-3-8',
 'Robinet coltar cromat pentru racordarea bateriilor si rezervoarelor WC, cu rozeta inclusa.',
 22.50, NULL, 200, 'buc', 0.20, 'robinet-coltar-1-2-3-8.svg', FALSE, NOW() - INTERVAL '45 days'),

(19, 5, 'TM-VD-001', 'Vopsea lavabila interior alba - 15 L', 'vopsea-lavabila-interior-15l',
 'Vopsea lavabila mata pentru pereti si tavane interioare, putere mare de acoperire si rezistenta la spalare. Consum aproximativ 1 L la 10 mp per strat.',
 179.00, 215.00, 60, 'galeata 15 L', 22.00, 'vopsea-lavabila-interior-15l.svg', TRUE, NOW() - INTERVAL '12 days'),
(20, 5, 'TM-VD-002', 'Trafalet microfibra 25 cm cu maner', 'trafalet-microfibra-25cm',
 'Trafalet din microfibra pentru vopsele lavabile, latime 25 cm, cu maner ergonomic si filet pentru prelungitor.',
 29.90, NULL, 140, 'buc', 0.35, 'trafalet-microfibra-25cm.svg', FALSE, NOW() - INTERVAL '30 days'),
(21, 5, 'TM-VD-003', 'Set pensule pentru vopsit - 3 bucati', 'set-pensule-vopsit-3',
 'Set de 3 pensule plate (25, 50 si 75 mm) cu peri sintetici, potrivite pentru vopsele pe baza de apa si solvent.',
 24.50, NULL, 110, 'set', 0.25, 'set-pensule-vopsit-3.svg', FALSE, NOW() - INTERVAL '30 days'),
(22, 5, 'TM-VD-004', 'Banda de mascare 48 mm x 50 m', 'banda-mascare-48mm',
 'Banda adeziva de hartie pentru mascare, se indeparteaza fara urme in maximum 7 zile de la aplicare.',
 9.90, NULL, 0, 'rola', 0.20, 'banda-mascare-48mm.svg', FALSE, NOW() - INTERVAL '30 days'),

(23, 6, 'TM-GE-001', 'Furtun de gradina 1/2" - 25 m', 'furtun-gradina-25m',
 'Furtun de gradina din PVC armat cu 3 straturi, rezistent la UV si rasucire. Lungime 25 m, diametru 1/2".',
 79.90, NULL, 45, 'colac 25 m', 3.20, 'furtun-gradina-25m.svg', FALSE, NOW() - INTERVAL '3 days'),
(24, 6, 'TM-GE-002', 'Lopata cu coada din lemn', 'lopata-coada-lemn',
 'Lopata din otel calit cu coada din lemn de fag, lungime totala 145 cm, pentru sapat si manipulare materiale.',
 54.90, NULL, 50, 'buc', 2.00, 'lopata-coada-lemn.svg', FALSE, NOW() - INTERVAL '150 days'),
(25, 6, 'TM-GE-003', 'Set unelte de gradinarit - 5 piese', 'set-unelte-gradinarit-5',
 'Set de 5 unelte de mana pentru gradinarit (lopatica, grebla, sapalija, plantator, foarfeca) intr-o geanta de depozitare.',
 99.00, 129.00, 25, 'set', 1.50, 'set-unelte-gradinarit-5.svg', TRUE, NOW() - INTERVAL '6 days'),
(26, 6, 'TM-GE-004', 'Pistol de stropit multifunctional', 'pistol-stropit-multifunctional',
 'Pistol de stropit cu 7 moduri de pulverizare si sistem de prindere rapida, compatibil cu furtunurile de 1/2".',
 34.90, NULL, 70, 'buc', 0.30, 'pistol-stropit-multifunctional.svg', FALSE, NOW() - INTERVAL '3 days');

-- ---------------------------------------------------------------------
--  Specificatii tehnice
-- ---------------------------------------------------------------------
INSERT INTO specificatii (produs_id, nume, valoare, ordine) VALUES
(1, 'Putere', '750 W', 1), (1, 'Alimentare', '230 V retea', 2), (1, 'Turatie maxima', '3000 rot/min', 3), (1, 'Mandrina', '13 mm cu cheie', 4), (1, 'Greutate', '2,1 kg', 5),
(2, 'Putere', '-', 1), (2, 'Alimentare', '18 V acumulator Li-Ion', 2), (2, 'Turatie maxima', '1500 rot/min', 3), (2, 'Mandrina', '10 mm rapida', 4), (2, 'Greutate', '1,6 kg', 5),
(3, 'Prindere', 'SDS-Plus', 1), (3, 'Diametre', '5 / 6 / 6 / 8 / 8 / 10 / 10 / 12 mm', 2), (3, 'Material varf', 'Carbura de wolfram', 3),
(4, 'Tensiune', '18 V', 1), (4, 'Capacitate', '4 Ah', 2), (4, 'Tehnologie', 'Li-Ion', 3),
(5, 'Greutate cap', '600 g', 1), (5, 'Material coada', 'Fibra de sticla', 2),
(6, 'Clasa de rezistenta', '42,5R', 1), (6, 'Ambalare', 'Sac 40 kg', 2), (6, 'Standard', 'SR EN 197-1', 3),
(7, 'Clasa', 'C2TE', 1), (7, 'Ambalare', 'Sac 25 kg', 2), (7, 'Consum', '3-5 kg/mp', 3),
(8, 'Grosime', '10 cm', 1), (8, 'Suprafata pachet', '6 mp', 2), (8, 'Clasa foc', 'A1', 3),
(9, 'Grosime', '5 cm', 1), (9, 'Suprafata pachet', '10 mp', 2), (9, 'Tip', 'EPS 80', 3),
(10, 'Dimensiuni', '625 x 250 x 200 mm', 1), (10, 'Densitate', '500 kg/mc', 2),
(11, 'Sectiune', '3 x 2,5 mmp', 1), (11, 'Lungime', '100 m', 2), (11, 'Material conductor', 'Cupru', 3),
(12, 'Curent nominal', '16 A', 1), (12, 'Tensiune', '250 V', 2), (12, 'Culoare', 'Alb', 3),
(13, 'Curent nominal', '10 A', 1), (13, 'Tensiune', '250 V', 2), (13, 'Culoare', 'Alb', 3),
(14, 'Curent nominal', '16 A', 1), (14, 'Curba', 'C', 2), (14, 'Poli', '1', 3),
(15, 'Diametru', '20 mm', 1), (15, 'Presiune nominala', 'PN20', 2), (15, 'Lungime', '4 m', 3),
(16, 'Material', 'Alama cromata', 1), (16, 'Cartus', 'Ceramic 35 mm', 2),
(17, 'Diametru', '20 mm', 1), (17, 'Numar piese', '30', 2),
(18, 'Racord', '1/2" x 3/8"', 1), (18, 'Finisaj', 'Crom', 2),
(19, 'Volum', '15 L', 1), (19, 'Culoare', 'Alb', 2), (19, 'Consum', '1 L / 10 mp', 3), (19, 'Aspect', 'Mat', 4),
(20, 'Latime', '25 cm', 1), (20, 'Material', 'Microfibra', 2),
(21, 'Dimensiuni', '25 / 50 / 75 mm', 1), (21, 'Peri', 'Sintetici', 2),
(22, 'Latime', '48 mm', 1), (22, 'Lungime', '50 m', 2),
(23, 'Lungime', '25 m', 1), (23, 'Diametru', '1/2"', 2), (23, 'Straturi', '3', 3),
(24, 'Lungime totala', '145 cm', 1), (24, 'Material coada', 'Lemn de fag', 2),
(25, 'Numar piese', '5', 1), (25, 'Material', 'Otel inoxidabil', 2),
(26, 'Moduri pulverizare', '7', 1), (26, 'Racord', '1/2"', 2);

-- ---------------------------------------------------------------------
--  Accesorii compatibile / recomandari (A6)
-- ---------------------------------------------------------------------
INSERT INTO produse_asociate (produs_id, asociat_id) VALUES
(1, 3), (1, 5),
(2, 4), (2, 3),
(4, 2),
(6, 7), (6, 10), (6, 24),
(7, 6),
(8, 9),
(11, 12), (11, 13), (11, 14),
(12, 11), (13, 11),
(15, 17), (15, 18),
(16, 18), (16, 15),
(19, 20), (19, 21), (19, 22),
(20, 19),
(23, 26), (23, 25),
(25, 23);

-- ---------------------------------------------------------------------
--  Coduri de reducere (A8)
-- ---------------------------------------------------------------------
INSERT INTO cupoane (cod, descriere, tip, valoare, minim_comanda, valabil_pana, utilizari_max, activ) VALUES
('BINEVENIT10', 'Reducere de 10% la prima comanda',            'procent', 10,  0,   NULL,         NULL, TRUE),
('SANTIER50',   'Reducere de 50 RON la comenzi peste 500 RON', 'valoare', 50,  500, NULL,         100,  TRUE),
('VARA2024',    'Campanie de vara 2024 (expirata)',            'procent', 15,  0,   '2024-08-31', NULL, TRUE);

-- Sincronizarea secventelor dupa inserarea cu identificatori expliciti
SELECT setval('categorii_id_seq', (SELECT MAX(id) FROM categorii));
SELECT setval('produse_id_seq',   (SELECT MAX(id) FROM produse));

COMMIT;
