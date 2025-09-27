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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $lang['user_contact_us'] ?> - <?= $lang['logo'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f9ff;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .contact-section {
            padding: 70px 20px;
            max-width: 1200px;
            margin: auto;
        }

        .contact-section h2 {
            text-align: center;
            color: #004080;
            font-size: 36px;
            margin-bottom: 50px;
            position: relative;
        }

        .contact-section h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: #ff6600;
            margin: 15px auto 0;
            border-radius: 2px;
        }

        .contact-content {
            display: flex;
            gap: 40px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        /* Info Box */
        .contact-info {
            flex: 1 1 40%;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .contact-info:hover {
            transform: translateY(-5px);
        }

        .contact-info h3 {
            color: #004080;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
        }

        .contact-info p {
            margin: 12px 0;
            font-size: 16px;
            color: #555;
        }

        .contact-info p i {
            color: #ff6600;
            margin-right: 10px;
        }

        .map iframe {
            margin-top: 20px;
            width: 100%;
            height: 280px;
            border-radius: 10px;
            border: none;
        }

        /* Contact Form */
        .contact-form {
            flex: 1 1 55%;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .contact-form h3 {
            color: #004080;
            margin-bottom: 25px;
            font-size: 22px;
            font-weight: 600;
        }

        .contact-form form select,
        .contact-form form input,
        .contact-form form textarea {
            width: 100%;
            padding: 14px 15px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 15px;
            outline: none;
            background: #fafafa;
            transition: 0.3s;
        }

        .contact-form form select:focus,
        .contact-form form input:focus,
        .contact-form form textarea:focus {
            border-color: #004080;
            box-shadow: 0 0 6px rgba(0,64,128,0.3);
            background: #fff;
        }

        .contact-form button {
            background: linear-gradient(135deg, #004080, #0056d6);
            color: #fff;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .contact-form button:hover {
            background: linear-gradient(135deg, #ff6600, #e65c00);
            transform: translateY(-2px);
        }

        .hidden{
            display: none;
        }

        .flash-message {
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 900px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        .flash-message.success {
            background: #e6f9f0;
            color: #0a8a4d;
            border: 1px solid #0a8a4d;
        }

        .flash-message.error {
            background: #ffecec;
            color: #d93025;
            border: 1px solid #d93025;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }


        /* Responsive */
        @media screen and (max-width: 992px) {
            .contact-content {
                flex-direction: column;
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
        <!-- Contact Info -->
        <div class="contact-info">
            <h3><?= $lang['contact_details'] ?></h3>
            <p><i class="fa fa-phone"></i> +977 1 4117356, 4117358</p>
            <p><i class="fa fa-envelope"></i> info@salakpurkhanepani.com</p>
            <div class="map">
                <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3565.5095854783654!2d87.36577937488643!3d26.664180170728024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef6f34daba5585%3A0x243be79d3c22c683!2sSalakpur%20khanepani!5e0!3m2!1sen!2snp!4v1758365945264!5m2!1sen!2snp"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <!-- Contact Form -->
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
                <input type="email" name="email" placeholder="<?= $lang['your_email'] ?>" required>

                <!-- Complaint-specific fields -->
                <div id="complaintFields" class="hidden">
                    <input type="text" name="complaint_ref" placeholder="<?= $lang['complaint_ref'] ?>">
                    <textarea name="complaint_details" rows="4" placeholder="<?= $lang['complaint_details'] ?>"></textarea>
                </div>

                <textarea name="message" id="message" rows="6" placeholder="<?= $lang['your_message'] ?>" required></textarea>

                <button type="submit"><?= $lang['send_message'] ?></button>
            </form>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
    const typeSelect = document.getElementById('type');
    const complaintFields = document.getElementById('complaintFields');
    const messageField = document.getElementById('message');

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
</script>

</body>
</html>
