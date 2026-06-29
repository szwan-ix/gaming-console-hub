<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Check credentials against staff table (with password column)
        $stmt = $pdo->prepare("SELECT staff_id, name, role, password FROM staff WHERE name = ? AND role IN ('Admin', 'Technician') AND status = 'Active' LIMIT 1");
        $stmt->execute([$username]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['user_id'] = $staff['staff_id'];
            $_SESSION['username'] = $staff['name'];
            $_SESSION['role'] = $staff['role'];
            $_SESSION['login_time'] = time();

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Console Hub - Login</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #02040A;
            --surface: #18181B;
            --border: #27272A;
            --border-light: rgba(255,255,255,0.06);
            --text: #FFFFFF;
            --text-secondary: #A1A1AA;
            --text-muted: #71717A;
            --font: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --radius-sm: 23px;
        }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            line-height: 1.6;
        }

        /* Animated background mesh */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 50% at 20% 30%, rgba(168, 85, 247, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 20%, rgba(0, 240, 255, 0.06) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 50% 80%, rgba(255, 0, 110, 0.05) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(168, 85, 247, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(168, 85, 247, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        /* Floating neon orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: orbFloat 8s ease-in-out infinite;
        }
        .orb:nth-child(1) {
            width: 300px; height: 300px;
            background: rgba(0, 240, 255, 0.08);
            top: -80px; left: -80px;
            animation-delay: 0s;
        }
        .orb:nth-child(2) {
            width: 400px; height: 400px;
            background: rgba(255, 0, 110, 0.06);
            bottom: -120px; right: -120px;
            animation-delay: 3s;
        }
        .orb:nth-child(3) {
            width: 250px; height: 250px;
            background: rgba(168, 85, 247, 0.07);
            bottom: 15%; left: 10%;
            animation-delay: 5s;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-40px) scale(1.1); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: 0 24px 80px rgba(0,0,0,0.5);
            padding: 46px 38px;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Top accent line */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%;
            width: 60%; height: 1px;
            background: rgba(255,255,255,0.1);
            border-radius: 1px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 14px;
            display: block;
            opacity: 0.9;
        }

        .login-title {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.3px;
            color: var(--text);
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 10px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: var(--font-mono);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.1);
        }

        .form-control::placeholder { color: var(--text-muted); }

        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 12.5px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            animation: alertSlide 0.35s ease-out;
        }

        @keyframes alertSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(255, 0, 110, 0.06);
            color: #ff6b9d;
            border: 1px solid rgba(255, 0, 110, 0.2);
        }

        .alert-success {
            background: rgba(0, 255, 136, 0.06);
            color: var(--green, #3dd68c);
            border: 1px solid rgba(0, 255, 136, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 13px 28px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s ease;
            background: rgba(255,255,255,0.08);
            color: var(--text);
            font-family: var(--font);
        }

        .btn-login:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
        }

        .btn-login:active { transform: translateY(0); }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .login-footer p {
            font-size: 11.5px;
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .highlight {
            color: var(--text-muted);
            font-weight: 700;
        }

        .demo-badge {
            display: inline-block;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 10px;
            font-family: var(--font-mono);
            margin-top: 16px;
            line-height: 1.4;
        }

        #bg-canvas {
            position: fixed; top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 12px;
                padding: 32px 22px;
                border-radius: 16px;
            }
            .login-title { font-size: 24px; }
            .logo { font-size: 44px; }
            .form-control { padding: 11px 14px; font-size: 13px; }
            .btn-login { padding: 12px 22px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
    (function() {
        var canvas = document.getElementById('bg-canvas');
        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        // Create particles - Fjord atmospheric
        var count = 200;
        var positions = new Float32Array(count * 3);
        var sizes = new Float32Array(count);
        var speeds = [];

        for (var i = 0; i < count; i++) {
            positions[i*3] = (Math.random() - 0.5) * 20;
            positions[i*3+1] = (Math.random() - 0.5) * 15;
            positions[i*3+2] = (Math.random() - 0.5) * 10;
            sizes[i] = Math.random() * 2 + 0.5;
            speeds.push({
                x: (Math.random() - 0.5) * 0.002,
                y: (Math.random() - 0.5) * 0.002,
                z: (Math.random() - 0.5) * 0.001
            });
        }

        var geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        var material = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.04,
            transparent: true,
            opacity: 0.25,
            blending: THREE.AdditiveBlending,
            sizeAttenuation: true
        });

        var particles = new THREE.Points(geometry, material);
        scene.add(particles);

        camera.position.z = 8;

        function animate() {
            requestAnimationFrame(animate);
            var pos = particles.geometry.attributes.position.array;
            for (var i = 0; i < count; i++) {
                pos[i*3] += speeds[i].x;
                pos[i*3+1] += speeds[i].y;
                pos[i*3+2] += speeds[i].z;
                if (Math.abs(pos[i*3]) > 10) speeds[i].x *= -1;
                if (Math.abs(pos[i*3+1]) > 7.5) speeds[i].y *= -1;
                if (Math.abs(pos[i*3+2]) > 5) speeds[i].z *= -1;
            }
            particles.geometry.attributes.position.needsUpdate = true;
            particles.rotation.y += 0.0003;
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', function() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    })();
    </script>
    <div class="orb"></div>
    <div class="orb"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <span class="logo">🎮</span>
                <h1 class="login-title">Gaming Console Hub</h1>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
