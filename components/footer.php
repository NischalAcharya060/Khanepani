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
    <div class="skp-footer-container">
        <div class="skp-footer-section">
            <h4 class="skp-footer-title"><?= $lang['resources'] ?? 'RESOURCES' ?></h4>
            <ul class="skp-footer-links">
                <li><a href="../gallery.php"><?= $lang['user_gallery'] ?? 'Photo Gallery' ?></a></li>
                <li><a href="#"><?= $lang['privacy_policy'] ?? 'Privacy Policy' ?></a></li>
            </ul>
        </div>

        <div class="skp-footer-section">
            <h4 class="skp-footer-title"><?= $lang['quick_links'] ?? 'QUICK LINKS' ?></h4>
            <ul class="skp-footer-links">
                <li><a href="../our_services.php"><?= $lang['our_services'] ?? 'Our Services' ?></a></li>
                <li><a href="../about_us.php"><?= $lang['about_us'] ?? 'About Us' ?></a></li>
                <li><a href="../faqs.php"><?= $lang['faqs'] ?? 'FAQs' ?></a></li>
            </ul>
        </div>

        <div class="skp-footer-section skp-footer-contact">
            <h4 class="skp-footer-title"><?= $lang['contact_us'] ?? 'CONTACT US' ?></h4>
            <p class="skp-contact-item">
                <i class="fas fa-phone-alt skp-footer-icon"></i>
                <span class="skp-contact-label"><?= $lang['phone'] ?? 'Phone' ?>:</span>
                <a href="tel:<?= htmlspecialchars($settings['phone']) ?>"><?= htmlspecialchars($settings['phone']) ?></a>
            </p>
            <p class="skp-contact-item">
                <i class="fas fa-envelope skp-footer-icon"></i>
                <span class="skp-contact-label"><?= $lang['email'] ?? 'Email' ?>:</span>
                <a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a>
            </p>

            <h4 class="skp-footer-title skp-social-title"><?= $lang['social_media'] ?? 'SOCIAL MEDIA' ?></h4>
            <div class="skp-social-icons">
                <a href="<?= htmlspecialchars($settings['facebook_link']) ?>" target="_blank" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>
        </div>


        <div class="skp-footer-section skp-footer-map">
            <h4 class="skp-footer-title"><?= $lang['our_location'] ?? 'OUR LOCATION' ?></h4>
            <div class="skp-map-embed-wrapper">
                <iframe
                        src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                        loading="lazy"
                        title="Our Location on Map"
                        referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>

    <div class="skp-footer-separator"></div>

    <div class="skp-footer-bottom">
        <p class="skp-copyright-text">© <?php echo date('Y'); ?> <?= $lang['office_name'] ?? 'सलकपुर खानेपानी / Salakpur KhanePani' ?>. <?= $lang['all_rights'] ?? 'All rights reserved.' ?></p>
        <span class="skp-developer-credit" style="display: none;">
            Developed by <a href="https://acharyanischal.com.np" target="_blank" rel="noopener noreferrer">Nischal Acharya</a>
        </span>
    </div>
</footer>

<style>
    :root {
        --color-primary: #004080;
        --color-secondary: #1e90ff;
        --color-text-light: #f0f8ff;
        --color-text-dim: #a9c6e3;
        --color-bottom-bg: #003366;
    }

    .skp-footer {
        background: var(--color-primary);
        color: var(--color-text-light);
        margin-top: 50px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        line-height: 1.6;
    }

    .skp-footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 30px;
        padding: 50px 25px;
        max-width: 1280px;
        margin: 0 auto;
    }

    .skp-footer-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 20px;
        letter-spacing: 0.05em;
        color: var(--color-secondary);
        text-transform: uppercase;
        position: relative;
    }

    .skp-footer-title::after {
        content: '';
        display: block;
        width: 40px;
        height: 3px;
        background: var(--color-secondary);
        position: absolute;
        left: 0;
        bottom: -8px;
    }

    .skp-footer-links {
        list-style: none;
        padding: 0;
    }

    .skp-footer-links li {
        margin: 10px 0;
    }

    .skp-footer-links li a {
        color: var(--color-text-dim);
        text-decoration: none;
        font-size: 0.95rem;
        transition: color 0.3s ease, padding-left 0.3s ease;
        display: inline-block;
    }

    .skp-footer-links li a:hover {
        color: var(--color-secondary);
        padding-left: 5px;
    }

    .skp-footer-contact .skp-contact-item {
        margin: 12px 0;
        font-size: 0.9rem;
        color: var(--color-text-dim);
        display: flex;
        align-items: center;
    }

    .skp-footer-contact .skp-contact-item a {
        color: var(--color-text-dim);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .skp-footer-contact .skp-contact-item a:hover {
        color: var(--color-secondary);
    }

    .skp-footer-contact .skp-footer-icon {
        color: var(--color-secondary);
        font-size: 1rem;
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .skp-footer-contact .skp-contact-label {
        font-weight: bold;
        margin-right: 5px;
        color: var(--color-text-light);
    }

    .skp-social-title {
        margin-top: 30px;
        margin-bottom: 20px;
    }

    .skp-social-icons a {
        font-size: 1.5rem;
        color: var(--color-text-light);
        margin-right: 18px;
        display: inline-block;
        transition: color 0.3s, transform 0.3s;
    }

    .skp-social-icons a:hover {
        color: var(--color-secondary);
        transform: translateY(-3px) scale(1.05);
    }

    .skp-footer-map .skp-map-embed-wrapper {
        position: relative;
        padding-bottom: 60%;
        height: 0;
        overflow: hidden;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .skp-footer-map iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    .skp-footer-separator {
        max-width: 1280px;
        margin: 0 auto;
        border-top: 1px solid var(--color-bottom-bg);
    }

    .skp-footer-bottom {
        background: var(--color-primary);
        text-align: center;
        padding: 20px 15px;
        font-size: 0.85rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--color-text-dim);
    }

    .skp-footer-bottom .skp-copyright-text {
        margin: 5px 0;
    }

    .skp-footer-bottom .skp-developer-credit {
        margin: 5px 0;
        font-size: 0.8rem;
    }

    .skp-footer-bottom .skp-developer-credit a {
        color: var(--color-text-dim);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .skp-footer-bottom .skp-developer-credit a:hover {
        color: var(--color-secondary);
        text-decoration: underline;
    }

    @media (max-width: 900px) {
        .skp-footer-container {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
    }

    @media (max-width: 600px) {
        .skp-footer-container {
            grid-template-columns: 1fr;
            padding: 30px 20px;
            gap: 40px;
        }

        .skp-footer-bottom {
            padding: 15px;
        }

        .skp-footer-map .skp-map-embed-wrapper {
            padding-bottom: 70%;
        }
    }
</style>