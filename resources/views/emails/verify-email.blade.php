<!DOCTYPE html>
<html>
<head>
    <title>Vérification de votre email</title>
</head>
<body>
    <h1>Bonjour {{ $user->name }}!</h1>
    <p>Votre code de vérification pour Fish App est :</p>

    <h2 style="font-size: 24px; letter-spacing: 3px; margin: 20px 0;">{{ $code }}</h2>

    <p>Ce code expirera le {{ $expires }}</p>
    <p>Entrez ce code dans l'application pour compléter votre inscription.</p>
</body>
</html>
