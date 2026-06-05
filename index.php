<?php
// PODEŠAVANJE VREMENA (Važno za tačno vreme u Srbiji/Balkanu)
date_default_timezone_set('Europe/Belgrade');

// === TVOJA ŠIFRA ZA PRISTUP ===
$sifra_za_pristup = "demo"; 

// Ime fajla gde će se čuvati podaci
$fajl_baza = 'dnevnik_tracker.json';

session_start();

// LOGIKA ZA ODJAVU
if (isset($_GET['akcija']) && $_GET['akcija'] == 'odjava') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// LOGIKA ZA PRIJAVU
if (isset($_POST['prijava'])) {
    if ($_POST['sifra'] === $sifra_za_pristup) {
        $_SESSION['prijavljen'] = true;
        header("Location: index.php");
        exit;
    } else {
        $greska_prijava = "Pogrešna šifra!";
    }
}

$prijavljen = isset($_SESSION['prijavljen']) && $_SESSION['prijavljen'] === true;

// --- GLAVNA LOGIKA APLIKACIJE ---
if ($prijavljen) {
    $danas = date('Y-m-d'); // Trenutni datum
    $trenutno_vreme = date('H:i'); // Trenutno vreme (sati i minuti)

    // Učitaj podatke iz fajla
    $podaci = [];
    if (file_exists($fajl_baza)) {
        $podaci = json_decode(file_get_contents($fajl_baza), true);
    }

    // Ako današnji dan ne postoji u bazi, napravi mu prazan "šablon"
    if (!isset($podaci[$danas])) {
        $podaci[$danas] = [
            'dolazak' => null,
            'odlazak' => null,
            'pauza' => null,
            'zadaci' => [] // Lista poslova u toku dana
        ];
    }

    // OBRADA KLIKOVA (FORMI)
    $sacuvaj_potrebno = false;

    if (isset($_POST['akcija'])) {
        if ($_POST['akcija'] == 'zabelezi_dolazak') {
            $podaci[$danas]['dolazak'] = $trenutno_vreme;
            $sacuvaj_potrebno = true;
        } 
        elseif ($_POST['akcija'] == 'zabelezi_odlazak') {
            $podaci[$danas]['odlazak'] = $trenutno_vreme;
            $sacuvaj_potrebno = true;
        }
        elseif ($_POST['akcija'] == 'sacuvaj_pauzu') {
            $podaci[$danas]['pauza'] = $_POST['pauza_minuti'];
            $sacuvaj_potrebno = true;
        }
        elseif ($_POST['akcija'] == 'dodaj_posao' && !empty(trim($_POST['opis']))) {
            // Dodajemo novi posao sa tačnim vremenom unosa
            $podaci[$danas]['zadaci'][] = [
                'vreme' => $trenutno_vreme,
                'opis' => trim($_POST['opis'])
            ];
            $sacuvaj_potrebno = true;
        }
    }

    // Ako je nešto promenjeno, sačuvaj u fajl i osveži
    if ($sacuvaj_potrebno) {
        file_put_contents($fajl_baza, json_encode($podaci, JSON_PRETTY_PRINT));
        header("Location: index.php");
        exit;
    }

    // Sortiraj podatke tako da najnoviji dani budu na vrhu za Istoriju
    krsort($podaci);
    $danasnji_podaci = $podaci[$danas];
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Radni Dnevnik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #eef2f5; padding-top: 30px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .kontejner { max-width: 700px; margin: auto; }
        .kartica { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .vreme-badge { background-color: #e9ecef; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 0.9em; }
        .posao-item { border-left: 4px solid #0d6efd; padding-left: 15px; margin-bottom: 15px; background: #f8f9fa; padding: 10px 10px 10px 15px; border-radius: 0 8px 8px 0; }
    </style>
</head>
<body>

<div class="container kontejner pb-5">
    
    <?php if (!$prijavljen): ?>
        <!-- FORMA ZA LOGIN -->
        <div class="kartica mt-5 text-center">
            <h3 class="mb-4">Prijava u sistem</h3>
            <form method="POST" class="w-75 mx-auto">
                <?php if(isset($greska_prijava)) echo "<div class='alert alert-danger'>$greska_prijava</div>"; ?>
                <div class="mb-3">
                    <input type="password" name="sifra" class="form-control form-control-lg text-center" placeholder="Unesi lozinku" required>
                </div>
                <button type="submit" name="prijava" class="btn btn-primary btn-lg w-100">Uđi</button>
            </form>
        </div>

    <?php else: ?>
        <!-- GLAVNI EKRAN (ULOGOVAN) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">Danas, <?php echo date('d.m.Y.'); ?></h2>
            <a href="?akcija=odjava" class="btn btn-outline-danger btn-sm">Odjavi se</a>
        </div>

        <!-- 1. KARTICA: VREME DOLASKA I ODLASKA -->
        <div class="kartica">
            <h5 class="text-secondary mb-3">Radno vreme i pauza</h5>
            <div class="row text-center">
                
                <!-- DOLAZAK -->
                <div class="col-4">
                    <p class="mb-1 text-muted small">Dolazak</p>
                    <?php if ($danasnji_podaci['dolazak']): ?>
                        <div class="h4 text-success"><?php echo $danasnji_podaci['dolazak']; ?></div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="akcija" value="zabelezi_dolazak">
                            <button type="submit" class="btn btn-sm btn-success">Zabeleži</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- PAUZA -->
                <div class="col-4 border-start border-end">
                    <p class="mb-1 text-muted small">Pauza</p>
                    <?php if ($danasnji_podaci['pauza']): ?>
                        <div class="h4 text-warning"><?php echo $danasnji_podaci['pauza']; ?> min</div>
                    <?php else: ?>
                        <form method="POST" class="d-flex justify-content-center">
                            <input type="hidden" name="akcija" value="sacuvaj_pauzu">
                            <input type="number" name="pauza_minuti" class="form-control form-control-sm me-1" style="width: 60px;" placeholder="Min" required>
                            <button type="submit" class="btn btn-sm btn-warning text-white">OK</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- ODLAZAK -->
                <div class="col-4">
                    <p class="mb-1 text-muted small">Odlazak</p>
                    <?php if ($danasnji_podaci['odlazak']): ?>
                        <div class="h4 text-danger"><?php echo $danasnji_podaci['odlazak']; ?></div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="akcija" value="zabelezi_odlazak">
                            <button type="submit" class="btn btn-sm btn-danger">Zabeleži</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. KARTICA: UNOS NOVOG POSLA -->
        <div class="kartica">
            <h5 class="text-secondary mb-3">Šta si upravo završio/radio?</h5>
            <form method="POST">
                <input type="hidden" name="akcija" value="dodaj_posao">
                <div class="mb-2">
                    <textarea name="opis" rows="2" class="form-control" placeholder="Npr. Završio izveštaj, imao sastanak klijentom..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Upiši u dnevnik (<?php echo date('H:i'); ?>)</button>
            </form>
        </div>

        <!-- 3. KARTICA: LISTA DANAŠNJIH POSLOVA -->
        <div class="kartica">
            <h5 class="text-secondary mb-3">Tvoj tok dana</h5>
            <?php if (empty($danasnji_podaci['zadaci'])): ?>
                <p class="text-muted text-center m-0">Još uvek nisi uneo nijedan posao za danas.</p>
            <?php else: ?>
                <?php 
                // Obrnemo listu da najnoviji posao bude na vrhu
                $obrnuti_zadaci = array_reverse($danasnji_podaci['zadaci']);
                foreach ($obrnuti_zadaci as $zadatak): 
                ?>
                    <div class="posao-item">
                        <span class="vreme-badge me-2"><?php echo $zadatak['vreme']; ?></span>
                        <span><?php echo nl2br(htmlspecialchars($zadatak['opis'])); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <hr class="my-5">

        <!-- 4. KARTICA: ISTORIJA (Prethodni dani) -->
        <h4 class="mb-3">Istorija po danima</h4>
        
        <?php foreach ($podaci as $datum => $podaci_dana): 
            if ($datum == $danas) continue; // Preskačemo danas jer to već vidimo gore
        ?>
            <div class="kartica bg-light">
                <h6 class="text-primary border-bottom pb-2 mb-3">Datum: <?php echo date('d.m.Y.', strtotime($datum)); ?></h6>
                
                <div class="d-flex gap-3 mb-3 text-muted small font-weight-bold">
                    <span>🟢 Dolazak: <?php echo $podaci_dana['dolazak'] ?: '-'; ?></span>
                    <span>🔴 Odlazak: <?php echo $podaci_dana['odlazak'] ?: '-'; ?></span>
                    <span>☕ Pauza: <?php echo $podaci_dana['pauza'] ? $podaci_dana['pauza'].' min' : '-'; ?></span>
                </div>

                <?php if (!empty($podaci_dana['zadaci'])): ?>
                    <ul class="list-unstyled m-0">
                        <?php foreach ($podaci_dana['zadaci'] as $z): ?>
                            <li class="mb-1">
                                <span class="text-muted small">[<?php echo $z['vreme']; ?>]</span> 
                                <?php echo htmlspecialchars($z['opis']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <i class="text-muted small">Nema unetih zadataka za ovaj dan.</i>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<!-- Bootstrap JS za lepše funkcionalnosti ako zatreba -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
