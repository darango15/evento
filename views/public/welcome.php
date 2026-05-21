<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a EventoSaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366F1;
            --secondary: #8B5CF6;
            --bg: #0F172A;
            --text: #F1F5F9;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(15,23,42,0) 70%);
            top: -200px; right: -200px;
            border-radius: 50%;
        }
        body::after {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(139,92,246,0.12) 0%, rgba(15,23,42,0) 70%);
            bottom: -150px; left: -150px;
            border-radius: 50%;
        }
        .hero {
            position: relative;
            z-index: 10;
            max-width: 800px;
            padding: 40px;
        }
        .logo-symbol {
            font-size: 80px;
            margin-bottom: 24px;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 20px rgba(99,102,241,0.3));
        }
        h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            background: linear-gradient(to right, #fff, #94A3B8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 20px;
            color: #94A3B8;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .cta-group {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        .btn {
            padding: 16px 32px;
            border-radius: 14px;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 30px -10px rgba(99,102,241,0.5);
        }
        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -10px rgba(99,102,241,0.6);
            background: var(--primary-dark);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-4px);
        }
        .features {
            margin-top: 80px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .feature-item {
            padding: 24px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(99,102,241,0.2);
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 16px;
            display: block;
        }
        .feature-title {
            font-weight: 700;
            margin-bottom: 8px;
            color: #fff;
        }
        .feature-text {
            font-size: 14px;
            color: #64748B;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="logo-symbol">⚡</div>
        <h1>EventoSaaS</h1>
        <p>La plataforma definitiva para gestionar tus eventos multi-tenant.<br>Crea, organiza y controla todo en un solo lugar.</p>
        
        <div class="cta-group">
            <a href="http://demo.evento.test/login" class="btn btn-primary">Prueba la Demo</a>
            <a href="/install.php" class="btn btn-secondary">Instalar Ahora</a>
        </div>

        <div class="features">
            <div class="feature-item">
                <span class="feature-icon">🌐</span>
                <div class="feature-title">Multi-Tenant</div>
                <div class="feature-text">Subdominios dedicados para cada organizador.</div>
            </div>
            <div class="feature-item">
                <span class="feature-icon">🎫</span>
                <div class="feature-title">Ticketing QR</div>
                <div class="feature-text">Generación automática de QRs y check-in en tiempo real.</div>
            </div>
            <div class="feature-item">
                <span class="feature-icon">📊</span>
                <div class="feature-title">Analytics</div>
                <div class="feature-text">Control total de asistencia y métricas del evento.</div>
            </div>
        </div>
    </div>
</body>
</html>
