<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation d'inscription - Banque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .credentials {
            background-color: #fff;
            padding: 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏦 Confirmation d'inscription</h1>
        <p>Bienvenue à la Banque - Gestionnaire de Comptes</p>
    </div>

    <div class="content">
        <h2>Bonjour {{ $user['name'] }},</h2>

        <p>Votre inscription a été effectuée avec succès ! Voici vos informations de connexion :</p>

        <div class="credentials">
            <h3>🔐 Vos identifiants de connexion</h3>
            <p><strong>Email :</strong> {{ $user['email'] }}</p>
            <p><strong>Mot de passe temporaire :</strong> {{ $temporaryPassword }}</p>
            <p><strong>Code d'authentification :</strong> {{ $codeAuthentification }}</p>
        </div>

        <div class="warning">
            ⚠️ <strong>Important :</strong> Conservez ces informations en lieu sûr. Le mot de passe temporaire devra être changé lors de votre première connexion.
        </div>

        <h3>📋 Prochaines étapes :</h3>
        <ol>
            <li>Connectez-vous avec votre email et mot de passe temporaire</li>
            <li>Utilisez votre code d'authentification pour valider la connexion</li>
            <li>Changez votre mot de passe dans les paramètres de votre compte</li>
        </ol>

        <p>Si vous avez des questions, n'hésitez pas à contacter notre support.</p>

        <p>Cordialement,<br>
        L'équipe de la Banque</p>
    </div>

    <div class="footer">
        <p>© 2024 Banque - Gestionnaire de Comptes. Tous droits réservés.</p>
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
    </div>
</body>
</html>