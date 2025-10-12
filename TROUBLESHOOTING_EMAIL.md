# Problèmes de Formulaire de Contact - Guide de Dépannage

## Problème: Le formulaire de contact ne fonctionne pas

### Étapes de dépannage

1. **Vérifier les fichiers de test**
   - Accédez à `test_contact_form.html` dans votre navigateur
   - Essayez d'envoyer un message de test
   - Vérifiez la console du navigateur (F12) pour les erreurs

2. **Vérifier les logs PHP**
   - Consultez les fichiers de log du serveur
   - Recherchez des erreurs liées à send_email.php

3. **Exécuter les scripts de diagnostic**
   - Exécutez `debug_info.php` pour obtenir des informations système
   - Exécutez `simple_mail_test.php` pour tester la fonction mail()
   - Exécutez `test_mail.php` pour un test plus complet

### Solutions possibles

#### Solution 1: Problème de configuration du serveur
Si la fonction mail() ne fonctionne pas:
- Contactez votre hébergeur pour configurer le serveur de messagerie
- Vérifiez que PHP mail() est activé

#### Solution 2: Utiliser un service SMTP externe
Remplacer send_email.php par une version utilisant PHPMailer:

1. Téléchargez PHPMailer depuis https://github.com/PHPMailer/PHPMailer
2. Extrayez dans un dossier `PHPMailer` dans votre projet
3. Remplacez le contenu de send_email.php par une version SMTP

#### Solution 3: Utiliser un service d'email tiers
Services comme:
- SendGrid (https://sendgrid.com)
- Mailgun (https://www.mailgun.com)
- Amazon SES (https://aws.amazon.com/ses/)

### Test de validation

1. **Testez le formulaire de contact de test**
   - Ouvrez `test_contact_form.html` dans votre navigateur
   - Remplissez tous les champs
   - Cliquez sur "Envoyer le Message"
   - Vérifiez si un message de succès s'affiche

2. **Vérifiez les fichiers de log**
   - Un fichier `contact_form_submissions.log` devrait être créé
   - Ce fichier contient les messages qui n'ont pas pu être envoyés par email

3. **Vérifiez la console du navigateur**
   - Appuyez sur F12 dans votre navigateur
   - Allez dans l'onglet "Console"
   - Soumettez le formulaire et vérifiez les erreurs

### Informations de contact de secours

Si le formulaire ne fonctionne toujours pas, les visiteurs peuvent envoyer un email directement à:
**nexoraprime051709@gmail.com**

### Support technique

Pour un support technique supplémentaire, veuillez fournir:
1. Les résultats des scripts de diagnostic
2. Les messages d'erreur dans la console du navigateur
3. Les informations du fichier `contact_form_submissions.log` (s'il existe)