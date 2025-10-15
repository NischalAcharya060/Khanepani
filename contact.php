<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/database/db.php';
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$langFile = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/lang/en.php';
}
$settings = [
        'email' => 'info@example.com',
        'phone' => '+977-1-0000000',
        'facebook_link' => '#',
        'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.7562473456385!2d85.32147311453896!3d27.693433982798625!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb19a4e61c3241%3A0x644265576a4f933e!2sKathmandu!5e0!3m2!1sen!2snp!4v1678873724213!5m2!1sen!2snp',
];
$sql = "SELECT email, phone, facebook_link, map_embed FROM settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $db_settings = $result->fetch_assoc();
    $settings = array_merge($settings, $db_settings);
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['user_contact_us']) ?> - <?= htmlspecialchars($lang['logo']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #007bff;
            --secondary-teal: #17a2b8;
            --background-light: #f0f2f5;
            --card-background: #ffffff;
            --text-dark: #212529;
            --border-color: #dee2e6;
            --radius: 18px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        .contact-section {
            padding: 80px 20px;
            max-width: 1200px;
            margin: auto;
        }
        .contact-section h2 {
            text-align: center;
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 20px;
            animation: slideInUp 0.8s ease-out;
        }
        .contact-section h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-teal));
            margin: 15px auto 50px;
            border-radius: 2px;
        }
        .contact-info h3, .contact-form h3 {
            color: var(--primary-blue);
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .contact-content {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .contact-info, .contact-form {
            flex: 1 1 48%;
            min-width: 350px;
            background: var(--card-background);
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
        }
        .contact-info {
            border-left: 5px solid var(--secondary-teal);
            animation: fadeInRight 1s ease-out;
        }
        .contact-form {
            border-right: 5px solid var(--primary-blue);
            animation: fadeInLeft 1s ease-out;
        }
        .contact-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.5;
        }
        .contact-item i {
            color: var(--secondary-teal);
            margin-right: 15px;
            font-size: 20px;
            width: 25px;
            animation: pulse 2s infinite ease-in-out;
        }
        .contact-item:nth-child(2) i { animation-delay: 0.2s; }
        .contact-item:nth-child(3) i { animation-delay: 0.4s; }
        .contact-form form input,
        .contact-form form select,
        .contact-form form textarea {
            width: 100%;
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 16px;
            background: var(--background-light);
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .contact-form form input:focus, .contact-form form textarea:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .map iframe {
            width: 100%;
            height: 300px;
            border-radius: 10px;
            border: 0;
            margin-top: 30px;
        }
        .social-links { margin-top: 25px; text-align: center; }
        .social-links a {
            width: 40px; height: 40px; line-height: 40px;
            margin: 0 6px;
            background: var(--border-color);
            color: var(--text-dark);
            border-radius: 50%;
            transition: transform 0.3s ease;
        }
        .social-links a:hover {
            background: var(--primary-blue);
            color: white;
            transform: scale(1.15) rotate(5deg);
        }
        .contact-form button {
            background: linear-gradient(45deg, var(--primary-blue), var(--secondary-teal));
            color: #fff;
            padding: 14px 30px;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s;
            overflow: hidden;
        }
        .contact-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(23, 162, 184, 0.4);
        }
        .contact-form button.loading {
            background: #adb5bd;
            pointer-events: none;
            position: relative;
        }
        .contact-form button.loading i { display: none; }
        .contact-form button.loading::after {
            content: "";
            position: absolute;
            right: 15px;
            top: 50%;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            transform: translateY(-50%);
        }
        .contact-form button.loading span { opacity: 0; }
        .contact-form button.loading::before {
            content: "<?= $lang['sending'] ?? 'SENDING...' ?>";
            color: white;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        .hidden { display: none !important; }
        @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes spin { 0% { transform: translateY(-50%) rotate(0deg); } 100% { transform: translateY(-50%) rotate(360deg); } }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); color: var(--primary-blue); } 100% { transform: scale(1); } }
        @media screen and (max-width: 900px) {
            .contact-content { flex-direction: column; gap: 25px; }
            .contact-info, .contact-form { min-width: 100%; padding: 30px; }
            .contact-section h2 { font-size: 34px; }
        }
        @media screen and (max-width: 500px) {
            .contact-section { padding: 40px 15px; }
            .contact-info, .contact-form { padding: 25px; }
            .contact-section h2 { font-size: 28px; }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="flash-message <?= htmlspecialchars($_SESSION['flash_message']['type']) ?>" style="max-width: 900px; margin: 30px auto;">
        <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<section class="contact-section">
    <h2><?= htmlspecialchars($lang['user_contact_us']) ?></h2>
    <div class="contact-content">
        <div class="contact-info">
            <h3><?= htmlspecialchars($lang['contact_details']) ?></h3>

            <div class="contact-item">
                <i class="fa fa-map-marker-alt"></i>
                <p>Corporate Office: Salakpur Morang, Nepal</p>
            </div>
            <div class="contact-item">
                <i class="fa fa-phone"></i>
                <p><a href="tel:<?= htmlspecialchars($settings['phone']) ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($settings['phone']) ?></a></p>
            </div>
            <div class="contact-item">
                <i class="fa fa-envelope"></i>
                <p><a href="mailto:<?= htmlspecialchars($settings['email']) ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($settings['email']) ?></a></p>
            </div>
            <div class="contact-item">
                <i class="fa fa-clock"></i>
                <p>Sun - Fri: 10:00 AM - 5:00 PM</p>
            </div>

            <div class="map">
                <iframe
                        src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <div class="social-links">
                <a href="<?= htmlspecialchars($settings['facebook_link'] ?? '#') ?>" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>

        <div class="contact-form">
            <h3><?= htmlspecialchars($lang['send_message']) ?></h3>
            <form action="contact_process.php" method="post" id="contactForm">
                <select name="type" id="type" required aria-label="<?= htmlspecialchars($lang['select_message_type']) ?>">
                    <option value=""><?= htmlspecialchars($lang['select_message_type']) ?></option>
                    <option value="general"><?= htmlspecialchars($lang['general_message']) ?></option>
                    <option value="complaint"><?= htmlspecialchars($lang['complaint']) ?></option>
                    <option value="suggestion"><?= htmlspecialchars($lang['suggestion_feedback']) ?></option>
                </select>

                <input type="text" name="subject" id="subject" placeholder="<?= htmlspecialchars($lang['user_subject']) ?> *" required>
                <input type="text" name="name" placeholder="<?= htmlspecialchars($lang['your_name']) ?> *" required>
                <input type="email" name="email" placeholder="<?= htmlspecialchars($lang['your_email']) ?>">
                <input type="text" name="phone" placeholder="<?= htmlspecialchars($lang['your_phone']) ?>">

                <div id="complaintFields" class="hidden">
                    <input type="text" name="complaint_ref" placeholder="<?= htmlspecialchars($lang['complaint_ref']) ?>">
                    <textarea name="complaint_details" rows="4" placeholder="<?= htmlspecialchars($lang['complaint_details']) ?>"></textarea>
                </div>

                <textarea name="message" id="message" rows="6" placeholder="<?= htmlspecialchars($lang['your_message']) ?> *" required></textarea>

                <div class="g-recaptcha" data-sitekey="6Lex7dcrAAAAAPeIL3aTKqVvlaWewWRnUcF03IX4"></div>
                <br>

                <button type="submit" id="submitButton">
                    <i class="fa fa-paper-plane"></i> <span><?= htmlspecialchars($lang['send_message']) ?></span>
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const complaintFields = document.getElementById('complaintFields');
        const messageField = document.getElementById('message');
        const contactForm = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitButton');
        const subjectInput = document.getElementById('subject');

        const langStrings = {
            additionalComplaintInfo: "<?= addslashes(htmlspecialchars($lang['additional_complaint_info'] ?? 'Additional message/notes (optional)')) ?>",
            suggestionPlaceholder: "<?= addslashes(htmlspecialchars($lang['suggestion_feedback_placeholder'] ?? 'Please share your suggestion or feedback here.')) ?>",
            yourMessage: "<?= addslashes(htmlspecialchars($lang['your_message'] ?? 'Your Message')) ?> *",
            sending: "<?= addslashes(htmlspecialchars($lang['sending'] ?? 'SENDING...')) ?>",
            complaintPrefix: "<?= addslashes(htmlspecialchars($lang['complaint_prefix'] ?? '[Complaint]')) ?>",
            suggestionPrefix: "<?= addslashes(htmlspecialchars($lang['suggestion_prefix'] ?? '[Suggestion]')) ?>",
            recaptchaRequired: "<?= addslashes(htmlspecialchars($lang['recaptcha_required'] ?? 'Please confirm you are not a robot.')) ?>"
        };

        typeSelect.addEventListener('change', function() {
            const type = this.value;
            if (type === 'complaint') {
                complaintFields.classList.remove('hidden');
                messageField.placeholder = langStrings.additionalComplaintInfo;
            } else {
                complaintFields.classList.add('hidden');
                const optionalInputs = complaintFields.querySelectorAll('input, textarea');
                optionalInputs.forEach(input => input.value = '');
                messageField.placeholder = (type === 'suggestion') ? langStrings.suggestionPlaceholder : langStrings.yourMessage;
            }
            let cleanSubject = subjectInput.value.trim();
            const prefixes = [langStrings.complaintPrefix, langStrings.suggestionPrefix];
            prefixes.forEach(prefix => {
                if (cleanSubject.startsWith(prefix)) {
                    cleanSubject = cleanSubject.substring(prefix.length).trim();
                }
            });
            if (type === 'complaint' && !cleanSubject.toLowerCase().includes('complaint')) {
                subjectInput.value = langStrings.complaintPrefix + ' ' + cleanSubject;
            } else if (type === 'suggestion' && !cleanSubject.toLowerCase().includes('suggestion')) {
                subjectInput.value = langStrings.suggestionPrefix + ' ' + cleanSubject;
            } else if (type === 'general') {
                subjectInput.value = cleanSubject;
            }
        });

        contactForm.addEventListener('submit', function(e) {
            const captchaResponse = grecaptcha.getResponse();
            if (captchaResponse.length === 0) {
                alert(langStrings.recaptchaRequired);
                e.preventDefault();
                return;
            }
            submitBtn.classList.add("loading");
            submitBtn.disabled = true;
        });
    });
</script>

</body>
</html>