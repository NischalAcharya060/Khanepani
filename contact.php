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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            --gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --gradient-accent: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .contact-hero {
            background: var(--gradient);
            color: white;
            padding: 100px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" opacity="0.1"><polygon fill="white" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .contact-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .contact-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .contact-section {
            padding: 80px 20px;
            max-width: 1400px;
            margin: auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 50px;
            align-items: start;
        }

        .contact-info {
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .contact-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient-accent);
        }

        .contact-form-container {
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .contact-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient);
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 40px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .contact-items {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 25px;
            background: var(--light);
            border-radius: 16px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .contact-item:hover {
            transform: translateX(10px);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .contact-item:hover .contact-icon {
            transform: scale(1.1) rotate(10deg);
            background: var(--gradient-accent);
        }

        .contact-details h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .contact-details p {
            color: var(--gray);
            font-size: 1rem;
        }

        .contact-details a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-details a:hover {
            color: var(--primary);
        }

        .map-container {
            margin-top: 40px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: none;
            display: block;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .social-link {
            width: 50px;
            height: 50px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .social-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-5px) rotate(10deg);
            border-color: var(--primary-dark);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--light);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .complaint-fields {
            background: var(--light);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid var(--accent);
            margin: 20px 0;
            transition: all 0.3s ease;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }

        .complaint-fields.active {
            max-height: 500px;
            opacity: 1;
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            width: 100%;
            justify-content: center;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn.loading {
            background: var(--gray);
            pointer-events: none;
        }

        .submit-btn.loading .btn-text {
            opacity: 0;
        }

        .submit-btn.loading .btn-icon {
            display: none;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .submit-btn.loading .loading-spinner {
            display: block;
        }

        .g-recaptcha {
            margin: 25px 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 { width: 80px; height: 80px; top: 10%; left: 5%; animation-delay: 0s; }
        .shape-2 { width: 120px; height: 120px; top: 60%; right: 10%; animation-delay: 2s; }
        .shape-3 { width: 60px; height: 60px; bottom: 20%; left: 15%; animation-delay: 4s; }

        .flash-message {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 20px 25px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.5s ease;
            box-shadow: var(--shadow-lg);
        }

        .flash-message.show {
            transform: translateX(0);
        }

        .flash-message.success { background: var(--success); }
        .flash-message.error { background: var(--danger); }

        .hidden {
            display: none !important;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideInUp 0.8s ease-out;
        }

        @media (max-width: 1024px) {
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .contact-hero h1 {
                font-size: 3rem;
            }
        }

        @media (max-width: 768px) {
            .contact-hero {
                padding: 80px 20px 60px;
            }

            .contact-hero h1 {
                font-size: 2.5rem;
            }

            .contact-section {
                padding: 60px 20px;
            }

            .contact-info,
            .contact-form-container {
                padding: 35px 25px;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .contact-item {
                padding: 20px;
            }

            .contact-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .form-control {
                padding: 14px 1px;
            }
        }

        @media (max-width: 480px) {
            .contact-hero h1 {
                font-size: 2rem;
            }

            .contact-hero p {
                font-size: 1rem;
            }

            .contact-info,
            .contact-form-container {
                padding: 25px 20px;
                border-radius: 16px;
            }

            .contact-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .form-control {
                padding: 12px 1px;
                font-size: 0.9rem;
            }

            .contact-details {
                align-self: center;
            }

            .contact-icon {
                align-self: center;
            }

            .social-links {
                flex-wrap: wrap;
            }

            .flash-message {
                right: 15px;
                left: 15px;
                top: 80px; /* Adjusted slightly for better small screen positioning */
                transform: translateY(-100px);
            }

            .flash-message.show {
                transform: translateY(0);
            }

            .complaint-fields {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="contact-hero">
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <h1 class="animate-slide-in"><?= htmlspecialchars($lang['user_contact_us']) ?></h1>
    <p class="animate-slide-in"><?= htmlspecialchars($lang['contact_description'] ?? 'Get in touch with us for any queries or support') ?></p>
</section>

<section class="contact-section">
    <div class="contact-grid">
        <div class="contact-info animate-slide-in">
            <h2 class="section-title"><?= htmlspecialchars($lang['contact_details']) ?></h2>

            <div class="contact-items">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h4><?= htmlspecialchars($lang['our_location'] ?? 'Our Location') ?></h4>
                        <p>Corporate Office: Salakpur Morang, Nepal</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h4><?= htmlspecialchars($lang['phone_number'] ?? 'Phone Number') ?></h4>
                        <p><a href="tel:<?= htmlspecialchars($settings['phone']) ?>"><?= htmlspecialchars($settings['phone']) ?></a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h4><?= htmlspecialchars($lang['email_address'] ?? 'Email Address') ?></h4>
                        <p><a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-details">
                        <h4><?= htmlspecialchars($lang['working_hours'] ?? 'Working Hours') ?></h4>
                        <p>Sun - Fri: 10:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="map-container">
                <iframe src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                        allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <div class="social-links">
                <a href="<?= htmlspecialchars($settings['facebook_link'] ?? '#') ?>" class="social-link" target="_blank" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-link" aria-label="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </div>

        <div class="contact-form-container animate-slide-in">
            <h2 class="section-title"><?= htmlspecialchars($lang['send_message']) ?></h2>

            <form action="contact_process.php" method="post" id="contactForm">
                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($lang['message_type'] ?? 'Message Type') ?> *</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value=""><?= htmlspecialchars($lang['select_message_type']) ?></option>
                        <option value="general"><?= htmlspecialchars($lang['general_message']) ?></option>
                        <option value="complaint"><?= htmlspecialchars($lang['complaint']) ?></option>
                        <option value="suggestion"><?= htmlspecialchars($lang['suggestion_feedback']) ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($lang['user_subject']) ?> *</label>
                    <input type="text" name="subject" id="subject" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($lang['your_name']) ?> *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($lang['your_email']) ?></label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= htmlspecialchars($lang['your_phone']) ?></label>
                    <input type="text" name="phone" class="form-control">
                </div>

                <div id="complaintFields" class="complaint-fields">
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($lang['complaint_ref']) ?></label>
                        <input type="text" name="complaint_ref" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($lang['complaint_details']) ?></label>
                        <textarea name="complaint_details" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" id="messageLabel"><?= htmlspecialchars($lang['your_message']) ?> *</label>
                    <textarea name="message" id="message" class="form-control" rows="6" required></textarea>
                </div>

                <div class="g-recaptcha" data-sitekey="6Lex7dcrAAAAAPeIL3aTKqVvlaWewWRnUcF03IX4"></div>

                <button type="submit" class="submit-btn" id="submitButton">
                    <i class="fas fa-paper-plane btn-icon"></i>
                    <span class="btn-text"><?= htmlspecialchars($lang['send_message']) ?></span>
                    <div class="loading-spinner"></div>
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
        const messageLabel = document.getElementById('messageLabel');
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

        // Initialize label text
        messageLabel.textContent = langStrings.yourMessage;
        messageField.placeholder = '';

        function toggleComplaintFields() {
            const type = typeSelect.value;
            const isComplaint = type === 'complaint';
            const isSuggestion = type === 'suggestion';

            // Toggle visibility of complaint fields
            if (isComplaint) {
                complaintFields.classList.add('active');
            } else {
                complaintFields.classList.remove('active');
                // Clear optional inputs when hiding
                const optionalInputs = complaintFields.querySelectorAll('input, textarea');
                optionalInputs.forEach(input => input.value = '');
            }

            // Update message label and placeholder based on message type
            if (isComplaint) {
                messageLabel.textContent = langStrings.additionalComplaintInfo;
                messageField.placeholder = ''; // Placeholder can be empty or a simple hint
                messageField.required = false; // Message is optional for complaint
            } else if (isSuggestion) {
                messageLabel.textContent = langStrings.yourMessage;
                messageField.placeholder = langStrings.suggestionPlaceholder;
                messageField.required = true;
            } else { // general
                messageLabel.textContent = langStrings.yourMessage;
                messageField.placeholder = '';
                messageField.required = true;
            }

            updateSubjectPrefix(type);
        }

        function updateSubjectPrefix(type) {
            let cleanSubject = subjectInput.value.trim();
            const prefixes = [langStrings.complaintPrefix, langStrings.suggestionPrefix];

            // 1. Remove any existing prefixes
            prefixes.forEach(prefix => {
                if (cleanSubject.startsWith(prefix)) {
                    cleanSubject = cleanSubject.substring(prefix.length).trim();
                }
            });

            // 2. Add the appropriate prefix for the selected type
            if (type === 'complaint') {
                // Only add prefix if the subject doesn't already contain the word "complaint" (case-insensitive)
                if (!cleanSubject.toLowerCase().includes('complaint')) {
                    subjectInput.value = langStrings.complaintPrefix + ' ' + cleanSubject;
                } else {
                    subjectInput.value = cleanSubject;
                }
            } else if (type === 'suggestion') {
                // Only add prefix if the subject doesn't already contain the word "suggestion" (case-insensitive)
                if (!cleanSubject.toLowerCase().includes('suggestion')) {
                    subjectInput.value = langStrings.suggestionPrefix + ' ' + cleanSubject;
                } else {
                    subjectInput.value = cleanSubject;
                }
            } else { // general
                subjectInput.value = cleanSubject;
            }
        }

        // Ensure subject updates when typing AND changing the type
        subjectInput.addEventListener('input', () => updateSubjectPrefix(typeSelect.value));
        typeSelect.addEventListener('change', toggleComplaintFields);

        contactForm.addEventListener('submit', function(e) {
            // Recaptcha validation
            const captchaResponse = grecaptcha.getResponse();
            if (captchaResponse.length === 0) {
                showFlashMessage(langStrings.recaptchaRequired, 'error');
                e.preventDefault();
                return;
            }

            // Disable button and show loading state
            submitBtn.classList.add("loading");
            submitBtn.querySelector('.btn-text').textContent = langStrings.sending;
            submitBtn.disabled = true;
        });

        function showFlashMessage(message, type) {
            const flashDiv = document.createElement('div');
            flashDiv.className = `flash-message ${type}`;
            flashDiv.textContent = message;
            document.body.appendChild(flashDiv);

            // Force reflow to ensure transition works
            void flashDiv.offsetWidth;
            flashDiv.classList.add('show');

            setTimeout(() => {
                flashDiv.classList.remove('show');
                setTimeout(() => flashDiv.remove(), 500); // Wait for transition before removing
            }, 4000);
        }

        <?php if (isset($_SESSION['flash_message'])): ?>
        showFlashMessage("<?= addslashes(htmlspecialchars($_SESSION['flash_message']['text'])) ?>", "<?= htmlspecialchars($_SESSION['flash_message']['type']) ?>");
        <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        // Intersection Observer for slide-in animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.8s ease-out forwards';
                    observer.unobserve(entry.target); // Stop observing once animated
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-slide-in').forEach(el => {
            // Initial state to prepare for animation
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            observer.observe(el);
        });

        // Initialize state on page load
        toggleComplaintFields();
    });
</script>

</body>
</html>