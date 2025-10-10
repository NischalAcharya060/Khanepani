<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
include 'config/db.php';

// Language handling
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','np'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Include language file
$langFile = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($langFile)) {
    include $langFile;
} else {
    include __DIR__ . '/lang/en.php';
}

$settings = [
        'email' => 'info@salakpurkhanepani.com',
        'phone' => '+977-1-4117356',
];

$sql = "SELECT email, phone, facebook_link FROM settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['user_contact_us'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e78c8; /* A vibrant, modern blue */
            --secondary-orange: #ff6f61; /* A warm, inviting orange */
            --background-light: #f7f9fb; /* Very light, soft background */
            --card-background: #ffffff;
            --text-dark: #333333;
            --text-medium: #666666;
            --border-color: #e0e6ed;
            --border-radius-large: 15px;
            --shadow-elevation: 0 10px 30px rgba(0, 0, 0, 0.08); /* Modern, soft shadow */
        }

        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .contact-section {
            padding: 90px 20px;
            max-width: 1280px;
            margin: auto;
        }

        /* Section Header */
        .contact-section h2 {
            text-align: center;
            color: var(--primary-blue);
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .contact-section h2::after {
            content: '';
            display: block;
            width: 100px;
            height: 5px;
            background: var(--secondary-orange);
            margin: 20px auto 50px;
            border-radius: 5px;
        }

        .contact-content {
            display: flex;
            gap: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Contact Info Box */
        .contact-info {
            flex: 1 1 40%;
            min-width: 350px;
            background: var(--card-background);
            padding: 40px;
            border-radius: var(--border-radius-large);
            box-shadow: var(--shadow-elevation);
            transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-top: 5px solid var(--secondary-orange);
        }

        .contact-info:hover {
            transform: translateY(-8px);
        }

        .contact-info h3 {
            color: var(--primary-blue);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 700;
        }

        .contact-info p {
            margin: 20px 0;
            font-size: 17px;
            color: var(--text-medium);
            display: flex;
            align-items: center;
        }

        .contact-info p i {
            color: var(--secondary-orange);
            margin-right: 15px;
            font-size: 20px;
            width: 25px;
        }

        .map iframe {
            margin-top: 30px;
            width: 100%;
            height: 320px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Contact Form */
        .contact-form {
            flex: 1 1 55%;
            min-width: 450px;
            background: var(--card-background);
            padding: 40px;
            border-radius: var(--border-radius-large);
            box-shadow: var(--shadow-elevation);
        }

        .contact-form h3 {
            color: var(--primary-blue);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 700;
        }

        .contact-form form select,
        .contact-form form input:not([type="checkbox"]),
        .contact-form form textarea {
            width: 100%;
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 16px;
            outline: none;
            background: #ffffff;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        .contact-form form select:focus,
        .contact-form form input:focus,
        .contact-form form textarea:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 120, 200, 0.2);
        }

        /* Submit Button */
        .contact-form button {
            background: linear-gradient(45deg, var(--primary-blue), #2c93e5);
            color: #fff;
            padding: 15px 35px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(30, 120, 200, 0.4);
        }

        .contact-form button:hover {
            background: linear-gradient(45deg, var(--secondary-orange), #ff968d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 97, 0.5);
        }

        .hidden{
            display: none;
        }

        /* Flash Message Styling */
        .flash-message {
            padding: 18px 25px;
            margin: 30px auto;
            max-width: 800px;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        .flash-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .flash-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Loading Button */
        .contact-form button.loading {
            background: #8899aa;
            position: relative;
            pointer-events: none;
            box-shadow: none;
        }

        .contact-form button.loading i {
            display: none;
        }

        .contact-form button.loading::after {
            content: "";
            position: absolute;
            right: 15px;
            top: 50%;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
            transform: translateY(-50%);
        }

        @keyframes spin {
            0% { transform: translateY(-50%) rotate(0deg); }
            100% { transform: translateY(-50%) rotate(360deg); }
        }

        /* reCAPTCHA style adjustment */
        .g-recaptcha {
            margin-bottom: 20px;
        }

        /* Responsive */
        @media screen and (max-width: 992px) {
            .contact-content {
                flex-direction: column;
                gap: 30px;
            }

            .contact-info, .contact-form {
                min-width: 100%;
                padding: 30px;
            }

            .contact-section h2 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="flash-message <?= $_SESSION['flash_message']['type'] ?>">
        <?= $_SESSION['flash_message']['text'] ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<section class="contact-section">
    <h2><?= $lang['user_contact_us'] ?></h2>
    <div class="contact-content">
        <div class="contact-info">
            <h3><?= $lang['contact_details'] ?></h3>

            <p><i class="fa fa-map-marker-alt"></i> Corporate Office: Salakpur Chowk, Kathmandu, Nepal</p>
            <p><i class="fa fa-phone"></i> <?= htmlspecialchars($settings['phone']) ?></p>
            <p><i class="fa fa-envelope"></i> <?= htmlspecialchars($settings['email']) ?></p>

            <p><i class="fa fa-clock"></i> Sun - Fri: 10:00 AM - 5:00 PM</p>

            <div class="map">
                <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3565.5095854783654!2d87.36577937488643!3d26.664180170728024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef6f34daba5585%3A0x243be79d3c22c683!2sSalakpur%20khanepani!5e0!3m2!1sen!2snp!4v1758365945264!5m2!1sen!2snp"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <div class="contact-form">
            <h3><?= $lang['send_message'] ?></h3>
            <form action="contact_process.php" method="post" id="contactForm">
                <select name="type" id="type" required>
                    <option value=""><?= $lang['select_message_type'] ?></option>
                    <option value="general"><?= $lang['general_message'] ?></option>
                    <option value="complaint"><?= $lang['complaint'] ?></option>
                    <option value="suggestion"><?= $lang['suggestion_feedback'] ?></option>
                </select>

                <input type="text" name="subject" id="subject" placeholder="<?= $lang['user_subject'] ?>" required>
                <input type="text" name="name" placeholder="<?= $lang['your_name'] ?>" required>
                <input type="email" name="email" placeholder="<?= $lang['your_email'] ?>">
                <input type="text" name="phone" placeholder="<?= $lang['your_phone'] ?>">

                <div id="complaintFields" class="hidden">
                    <input type="text" name="complaint_ref" placeholder="<?= $lang['complaint_ref'] ?>">
                    <textarea name="complaint_details" rows="4" placeholder="<?= $lang['complaint_details'] ?>"></textarea>
                </div>

                <textarea name="message" id="message" rows="6" placeholder="<?= $lang['your_message'] ?>" required></textarea>

                <div class="g-recaptcha" data-sitekey="6Lex7dcrAAAAAPeIL3aTKqVvlaWewWRnUcF03IX4"></div>
                <br>

                <button type="submit">
                    <i class="fa fa-paper-plane"></i> <?= $lang['send_message'] ?>
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
    const typeSelect = document.getElementById('type');
    const complaintFields = document.getElementById('complaintFields');
    const messageField = document.getElementById('message');
    const contactForm = document.getElementById('contactForm');
    const submitBtn = contactForm.querySelector("button[type='submit']");

    typeSelect.addEventListener('change', function() {
        if (this.value === 'complaint') {
            complaintFields.classList.remove('hidden');
            messageField.placeholder = "<?= $lang['additional_complaint_info'] ?>";
        } else if (this.value === 'suggestion') {
            complaintFields.classList.add('hidden');
            messageField.placeholder = "<?= $lang['suggestion_feedback_placeholder'] ?>";
        } else {
            complaintFields.classList.add('hidden');
            messageField.placeholder = "<?= $lang['your_message'] ?>";
        }
    });

    contactForm.addEventListener('submit', function() {
        submitBtn.classList.add("loading");
        submitBtn.disabled = true;
        submitBtn.innerHTML = "<?= $lang['sending'] ?? 'Sending...' ?>";
    });
</script>

</body>
</html>