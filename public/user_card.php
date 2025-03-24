<?php
include '../common/php/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_GET['u'];
$user_at = $_GET['at'];

// Requête pour récupérer les informations de l'utilisateur du profile
$query = "SELECT * FROM users WHERE id = ? OR at = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $user_at);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Holographique Métallique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #011627;;
        }

        .pixel {
            position: absolute;
            width: 20px;   /* Taille du carré */
            height: 20px;
            background-color: #f2cce2;  /* Couleur des carrés */
            opacity: 0;  /* Initialement transparent */
            animation: fadeInOut 4s ease-in-out infinite;  /* Animation pour faire apparaître et disparaître les carrés en fondu */
            z-index: -1;  /* Placer les pixels sous le contenu */
        }

        @keyframes fadeInOut {
            0% {
                opacity: 0;
            }
            50% {
                opacity: 1; /* Pixel visible */
            }
            100% {
                opacity: 0;  /* Pixel disparaît */
            }
        }

        .card-container {
            width: 300px;
            height: 400px;
            perspective: 1000px;
        }

        .card {
            width: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
            background: rgba(255, 255, 255, 0.17); /* Fond semi-transparent */
            backdrop-filter: blur(10px); /* Flou derrière l'élément */
            -webkit-backdrop-filter: blur(10px); /* Compatibilité Safari */
            border-radius: 12px; /* Coins arrondis */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Bordure fine et semi-transparente */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Ombre légère */
            padding: 15px;
            transition: all 0.3s ease-in-out; /* Animation fluide */
            color: white;
        }

        .card .side {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .front {
            background: transparent;
        }

        .back {
            background-color: #383287;
            transform: rotateY(180deg);
        }

        .profile-pic-big {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="card-container" onmousemove="tiltEffect(event)" onmouseleave="resetIdleTimer()">
        <div class="card" id="card">
            <div class="card-body text-center">
                <img src="<?= htmlspecialchars($user['pp']) ?>" alt="Photo de profil" class="profile-pic-big mb-3">
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p>@<?= htmlspecialchars($user['at']) ?></p>
                <p><strong><?= htmlspecialchars($user['nb_follows']) ?> followers</strong></p>
                <hr class="my-4">
                <?php
                $date = new DateTime($user['date']);
                $day = $date->format('d');
                $year = $date->format('Y');
                $months = [
                    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 'April' => 'Avril',
                    'May' => 'Mai', 'June' => 'Juin', 'July' => 'Juillet', 'August' => 'Août',
                    'September' => 'Septembre', 'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
                ];
                $month = $months[$date->format('F')];
                ?>
                <p>Utilisateur depuis le<br>
                <?= htmlspecialchars($day) ?> <?= htmlspecialchars($month) ?> <?= htmlspecialchars($year) ?></p>
            </div>
        </div>
    </div>

    <script src="../common/js/pixel.js"></script>
    <script>
        function tiltEffect(event) {
            const card = document.getElementById('card');
            const glow = document.getElementById('glow');
            const { clientX: x, clientY: y } = event;
            const { left, top, width, height } = card.getBoundingClientRect();
            const centerX = left + width / 2;
            const centerY = top + height / 2;
            const deltaX = (x - centerX) / width * 2;
            const deltaY = (y - centerY) / height * -2;

            card.style.transition = "transform 0.1s ease-out";
            card.style.transform = `rotateY(${deltaX * 20}deg) rotateX(${deltaY * 20}deg)`;

            glow.style.transform = `translate(${deltaX * 20}px, ${deltaY * 20}px)`;

            resetIdleTimer();
        }

        function resetIdleTimer() {
            clearTimeout(idleTimeout);
            isIdle = false;
            idleTimeout = setTimeout(startIdleRotation, 10000);
        }
    </script>
</body>
</html>