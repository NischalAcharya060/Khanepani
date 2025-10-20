<?php
require_once __DIR__ . '/../config/database/db.php';
$settings = [
        'email' => 'info@salakpurkhanepani.com',
        'phone' => '+977-1-4117356',
        'facebook_link' => '#',
        'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14251.258950853518!2d87.568469!3d26.544837!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef6929a0072b25%3A0x86b0f16503c5d648!2sSalakpur%20Khane%20Pani%20Tatha%20Safai%20Ubhokta%20Sanstha!5e0!3m2!1sen!2snp!4v1700000000000!5m2!1sen!2snp',
];

$sql = "SELECT email, phone, facebook_link, map_embed FROM settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}
?>

<footer class="skp-footer">
    <div class="skp-footer-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
        </svg>
    </div>

    <div class="skp-footer-container">
        <!-- Brand Section -->
        <div class="skp-footer-section skp-footer-brand">
            <div class="skp-footer-logo">
                <img src="../assets/images/logo.jpg" alt="Khane Pani Logo">
                <span class="skp-logo-text"><?= $lang['logo'] ?? 'सलकपुर खानेपानी' ?></span>
            </div>
            <p class="skp-footer-description">
                <?= $lang['footer_description'] ?? 'Providing clean and reliable water services to the Salakpur community since 2005. Committed to sustainable water management and community welfare.' ?>
            </p>
            <div class="skp-social-icons">
                <a href="<?= htmlspecialchars($settings['facebook_link']) ?>" target="_blank" title="Facebook" class="skp-social-link">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" target="_blank" title="Twitter" class="skp-social-link">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" target="_blank" title="YouTube" class="skp-social-link">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="#" target="_blank" title="Instagram" class="skp-social-link">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="skp-footer-section">
            <h4 class="skp-footer-title"><?= $lang['quick_links'] ?? 'QUICK LINKS' ?></h4>
            <ul class="skp-footer-links">
                <li><a href="../index.php"><?= $lang['home'] ?? 'Home' ?></a></li>
                <li><a href="../about_us.php"><?= $lang['about_us'] ?? 'About Us' ?></a></li>
                <li><a href="../our_services.php"><?= $lang['our_services'] ?? 'Our Services' ?></a></li>
                <li><a href="../notices.php"><?= $lang['notices'] ?? 'Notices' ?></a></li>
                <li><a href="../contact.php"><?= $lang['contact'] ?? 'Contact' ?></a></li>
            </ul>
        </div>

        <!-- Resources -->
        <div class="skp-footer-section">
            <h4 class="skp-footer-title"><?= $lang['resources'] ?? 'RESOURCES' ?></h4>
            <ul class="skp-footer-links">
                <li><a href="../gallery.php"><?= $lang['user_gallery'] ?? 'Photo Gallery' ?></a></li>
                <li><a href="../faqs.php"><?= $lang['faqs'] ?? 'FAQs' ?></a></li>
                <li><a href="../nepali_unicode.php"><?= $lang['nepali_unicode'] ?? 'Nepali Unicode' ?></a></li>
                <li><a href="#"><?= $lang['privacy_policy'] ?? 'Privacy Policy' ?></a></li>
                <li><a href="#"><?= $lang['terms_service'] ?? 'Terms of Service' ?></a></li>
            </ul>
        </div>

        <!-- Contact & Location -->
        <div class="skp-footer-section skp-footer-contact-map">
            <div class="skp-contact-info">
                <h4 class="skp-footer-title"><?= $lang['contact_us'] ?? 'CONTACT US' ?></h4>
                <div class="skp-contact-item">
                    <i class="fas fa-phone-alt skp-footer-icon"></i>
                    <div class="skp-contact-details">
                        <span class="skp-contact-label"><?= $lang['phone'] ?? 'Phone' ?></span>
                        <a href="tel:<?= htmlspecialchars($settings['phone']) ?>"><?= htmlspecialchars($settings['phone']) ?></a>
                    </div>
                </div>
                <div class="skp-contact-item">
                    <i class="fas fa-envelope skp-footer-icon"></i>
                    <div class="skp-contact-details">
                        <span class="skp-contact-label"><?= $lang['email'] ?? 'Email' ?></span>
                        <a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a>
                    </div>
                </div>
                <div class="skp-contact-item">
                    <i class="fas fa-map-marker-alt skp-footer-icon"></i>
                    <div class="skp-contact-details">
                        <span class="skp-contact-label"><?= $lang['address'] ?? 'Address' ?></span>
                        <span><?= $lang['office_address'] ?? 'Salakpur, Jhapa, Nepal' ?></span>
                    </div>
                </div>
                <div class="skp-contact-item">
                    <i class="fas fa-clock skp-footer-icon"></i>
                    <div class="skp-contact-details">
                        <span class="skp-contact-label"><?= $lang['office_hours'] ?? 'Office Hours' ?></span>
                        <span><?= $lang['hours_value'] ?? 'Sun-Fri: 10AM - 5PM' ?></span>
                    </div>
                </div>
            </div>

            <div class="skp-map-section">
                <h4 class="skp-footer-title"><?= $lang['our_location'] ?? 'OUR LOCATION' ?></h4>
                <div class="skp-map-embed-wrapper">
                    <iframe
                            src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            title="Our Location on Map"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="skp-footer-bottom">
        <div class="skp-footer-bottom-container">
            <p class="skp-copyright-text">
                © <?php echo date('Y'); ?> <?= $lang['office_name'] ?? 'सलकपुर खानेपानी / Salakpur KhanePani' ?>.
                <?= $lang['all_rights'] ?? 'All rights reserved.' ?>
            </p>
            <div class="skp-footer-bottom-links">
                <a href="#"><?= $lang['privacy_policy'] ?? 'Privacy Policy' ?></a>
                <span class="skp-separator">|</span>
                <a href="#"><?= $lang['terms_service'] ?? 'Terms of Service' ?></a>
                <span class="skp-separator">|</span>
                <span class="skp-developer-credit">
                    <?= $lang['developed_by'] ?? 'Developed by' ?>
                    <a href="https://acharyanischal.com.np" target="_blank" rel="noopener noreferrer">Nischal Acharya</a>
                </span>
            </div>
        </div>
    </div>
</footer>

<style>
    :root {
        --color-primary: #004080;
        --color-primary-dark: #003366;
        --color-primary-light: #1a5ca1;
        --color-secondary: #1e90ff;
        --color-accent: #00b894;
        --color-text-light: #f8f9fa;
        --color-text-dim: #c9d6e8;
        --color-text-muted: #a9c6e3;
        --color-border: rgba(255, 255, 255, 0.1);
        --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.1);
        --shadow-medium: 0 8px 24px rgba(0, 0, 0, 0.15);
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    .skp-footer {
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: var(--color-text-light);
        margin-top: 80px;
        position: relative;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        line-height: 1.6;
    }

    .skp-footer-wave {
        position: absolute;
        top: -80px;
        left: 0;
        width: 100%;
        overflow: hidden;
        line-height: 0;
    }

    .skp-footer-wave svg {
        position: relative;
        display: block;
        width: calc(100% + 1.3px);
        height: 80px;
    }

    .skp-footer-wave .shape-fill {
        fill: var(--color-primary);
    }

    .skp-footer-container {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 40px;
        padding: 60px 40px 40px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .skp-footer-section {
        display: flex;
        flex-direction: column;
    }

    /* Brand Section */
    .skp-footer-brand {
        gap: 20px;
    }

    .skp-footer-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .skp-footer-logo img {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid var(--color-secondary);
    }

    .skp-logo-text {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--color-text-light);
        letter-spacing: 0.5px;
    }

    .skp-footer-description {
        color: var(--color-text-dim);
        font-size: 0.95rem;
        line-height: 1.7;
        margin-bottom: 20px;
    }

    /* Social Icons */
    .skp-social-icons {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }

    .skp-social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: var(--color-text-light);
        font-size: 1.1rem;
        transition: var(--transition);
        backdrop-filter: blur(10px);
        border: 1px solid var(--color-border);
    }

    .skp-social-link:hover {
        background: var(--color-secondary);
        color: white;
        transform: translateY(-3px);
        box-shadow: var(--shadow-light);
    }

    /* Footer Titles */
    .skp-footer-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 25px;
        letter-spacing: 0.05em;
        color: var(--color-text-light);
        text-transform: uppercase;
        position: relative;
        padding-bottom: 12px;
    }

    .skp-footer-title::after {
        content: '';
        display: block;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, var(--color-secondary), var(--color-accent));
        position: absolute;
        left: 0;
        bottom: 0;
        border-radius: 2px;
    }

    /* Footer Links */
    .skp-footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .skp-footer-links li a {
        color: var(--color-text-dim);
        text-decoration: none;
        font-size: 0.95rem;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .skp-footer-links li a::before {
        content: '▸';
        font-size: 0.8rem;
        color: var(--color-secondary);
        transition: var(--transition);
    }

    .skp-footer-links li a:hover {
        color: var(--color-text-light);
        transform: translateX(5px);
    }

    .skp-footer-links li a:hover::before {
        color: var(--color-accent);
        transform: translateX(-2px);
    }

    /* Contact Section */
    .skp-footer-contact-map {
        gap: 30px;
    }

    .skp-contact-info {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .skp-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        color: var(--color-text-dim);
    }

    .skp-footer-icon {
        color: var(--color-secondary);
        font-size: 1.1rem;
        margin-top: 2px;
        flex-shrink: 0;
        width: 20px;
        text-align: center;
    }

    .skp-contact-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .skp-contact-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .skp-contact-details a,
    .skp-contact-details span {
        color: var(--color-text-dim);
        text-decoration: none;
        font-size: 0.95rem;
        transition: var(--transition);
    }

    .skp-contact-details a:hover {
        color: var(--color-secondary);
    }

    /* Map Section */
    .skp-map-section {
        margin-top: 10px;
    }

    .skp-map-embed-wrapper {
        position: relative;
        padding-bottom: 70%;
        height: 0;
        overflow: hidden;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-medium);
        border: 1px solid var(--color-border);
    }

    .skp-map-embed-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        border-radius: var(--border-radius);
    }

    /* Footer Bottom */
    .skp-footer-bottom {
        background: rgba(0, 0, 0, 0.2);
        border-top: 1px solid var(--color-border);
        padding: 25px 40px;
    }

    .skp-footer-bottom-container {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .skp-copyright-text {
        color: var(--color-text-dim);
        font-size: 0.9rem;
        margin: 0;
    }

    .skp-footer-bottom-links {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .skp-footer-bottom-links a {
        color: var(--color-text-dim);
        text-decoration: none;
        font-size: 0.85rem;
        transition: var(--transition);
    }

    .skp-footer-bottom-links a:hover {
        color: var(--color-secondary);
    }

    .skp-separator {
        color: var(--color-text-muted);
        font-size: 0.8rem;
    }

    .skp-developer-credit {
        color: var(--color-text-muted);
        font-size: 0.85rem;
    }

    .skp-developer-credit a {
        color: var(--color-accent);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
    }

    .skp-developer-credit a:hover {
        color: var(--color-secondary);
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .skp-footer-container {
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 40px 30px;
        }

        .skp-footer-contact-map {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 900px) {
        .skp-footer-container {
            grid-template-columns: 1fr 1fr;
            padding: 50px 30px 30px;
            gap: 40px 30px;
        }

        .skp-footer-brand {
            grid-column: 1 / -1;
        }

        .skp-footer-contact-map {
            grid-column: 1 / -1;
            grid-template-columns: 1fr;
            gap: 30px;
        }
    }

    @media (max-width: 600px) {
        .skp-footer-container {
            grid-template-columns: 1fr;
            padding: 50px 20px 30px;
            gap: 40px;
        }

        .skp-footer-brand {
            grid-column: 1;
        }

        .skp-footer-bottom-container {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }

        .skp-footer-bottom-links {
            justify-content: center;
        }

        .skp-map-embed-wrapper {
            padding-bottom: 80%;
        }

        .skp-footer-wave {
            top: -60px;
        }

        .skp-footer-wave svg {
            height: 60px;
        }
    }

    @media (max-width: 480px) {
        .skp-footer-logo {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }

        .skp-social-icons {
            justify-content: center;
        }

        .skp-footer-bottom-links {
            flex-direction: column;
            gap: 10px;
        }

        .skp-separator {
            display: none;
        }
    }

    /* Animation for subtle entrance */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .skp-footer-section {
        animation: fadeInUp 0.6s ease forwards;
    }

    .skp-footer-section:nth-child(1) { animation-delay: 0.1s; }
    .skp-footer-section:nth-child(2) { animation-delay: 0.2s; }
    .skp-footer-section:nth-child(3) { animation-delay: 0.3s; }
    .skp-footer-section:nth-child(4) { animation-delay: 0.4s; }
</style>