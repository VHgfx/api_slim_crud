<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class Email
{
    /* Si SMTP utilisé
    private static $sender_host; */
    private static $sender_email;
    private static $sender_username;
    private static $sender_password; 

    /** Initialisation des données de l'envoyeur
     * 
     * @return void
     */
    private static function initialize()
    {
        if (!isset(self::$sender_email)) {
            self::$sender_email = getenv('MAILING_ID');
            self::$sender_password = getenv('MAILING_PASSWORD');
            self::$sender_username = getenv('MAILING_USERNAME');
            /* Si SMTP utilisé
            self::$sender_host = getenv('MAILING_HOST');*/
        }
    }

    /** Extraction des infos du receiver
     *
     * @param array $receiver
     *              Champs obligatoires : 'email', 'firstname', 'lastname', 'role'
     * @return array
     */
    private static function extractReceiverInfos(array $receiver): array
    {
        return [
            'email' => $receiver['email'] ?? '',
            'firstname' => $receiver['firstname'] ?? '',
            'lastname' => $receiver['lastname'] ?? '',
            'role' => $receiver['role'] ?? ''
        ];
    }

    private static function sendingEmail($receiver_email, $subject, $message) {
        $mail = new PHPMailer(true);

        $mail->SMTPDebug = 0;
        $mail->isMail();
        /* Si SMTP utilisé
        $mail->isSMTP();
        $mail->Host = self::$sender_host;
        $mail->SMTPAuth = true;
        $mail->Username = self::$sender_email;
        $mail->Password = self::$sender_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;*/
        $mail->setFrom(self::$sender_email, self::$sender_username);
        $mail->addAddress($receiver_email);
        $mail->addReplyTo(self::$sender_email, self::$sender_username);
        $mail->addCC(self::$sender_email);
        $mail->addBCC(self::$sender_email);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        return $mail->send();
    }


    /** Envoi d'un mail de notification d'inscription
     *
     * @param array $receiver
     * @param array $mail_datas
     * @return bool|string
     */
    public static function sendSignUpNotification(array $receiver, array $mail_datas)
    {
        $processMailDatas = function(string $field, array $mail_datas) {
            if(!isset($mail_datas[$field]) || empty($mail_datas[$field])){
                throw new RuntimeException("processMailDatas : $field introuvable");
            }

            $output = trim($mail_datas[$field]);

            return $output;
        };
        
        self::initialize();
        ['email' => $receiver_email, 'firstname' => $receiver_firstname, 'lastname' => $receiver_lastname, 'role' => $receiver_role] = self::extractReceiverInfos($receiver);
        $password = $processMailDatas('password', $mail_datas);
        $login_link = getenv('ROOT_SITE') . "/login.php";
        
        setlocale(LC_TIME, 'fr_FR.UTF-8');

        try {
            $subject = "Création de votre compte";

            $message = "
            <html>
            <body>
            <p>Bonjour $receiver_firstname $receiver_lastname,</p>
            <p>Vous trouverez ci-dessous le détail de votre compte $receiver_role pour accéder à l'ERP :</p>
    
            Email: $receiver_email<br>
            Mot de passe : $password<br>

            Nous vous encourageons à modifier votre mot de passe lors de votre première connexion.
    
            <p>Se <a href='$login_link' target='_blank'>connecter</a> à votre compte.</p>
            
            <p>Cordialement,<br>L'équipe du site</p>
            </body>
            </html>";

            return self::sendingEmail($receiver_email, $subject, $message);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
