<?php
require_once __DIR__ . '/../config/db.php';
$settings = [
        'email' => 'info@salakpurkhanepani.com',
        'phone' => '+977-1-4117356',
        'facebook_link' => '#',
        'map_embed' => 'Map Embed',
];

$sql = "SELECT email, phone, facebook_link, map_embed FROM settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}
?>
<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h4><?= $lang['resources'] ?? 'RESOURCES' ?></h4>
            <ul>
                <li><a href="../gallery.php"><?= $lang['user_gallery'] ?? 'Photo Gallery' ?></a></li>
                <li><a href="#"><?= $lang['privacy_policy'] ?? 'Privacy Policy' ?></a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4><?= $lang['quick_links'] ?? 'QUICK LINKS' ?></h4>
            <ul>
                <li><a href="../our_services.php"><?= $lang['our_services'] ?? 'Our Services' ?></a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4><?= $lang['contact_us'] ?? 'CONTACT US' ?></h4>
            <p><?= $lang['phone'] ?? 'Phone' ?>: <?= htmlspecialchars($settings['phone']) ?></p>
            <p><?= $lang['email'] ?? 'Email' ?>: <?= htmlspecialchars($settings['email']) ?></p>
        </div>

        <div class="footer-section social">
            <h4><?= $lang['social_media'] ?? 'SOCIAL MEDIA' ?></h4>
            <div class="social-icons">
                <a href="<?= htmlspecialchars($settings['facebook_link']) ?>" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>
        </div>

        <div class="footer-section">
            <h4><?= $lang['our_location'] ?? 'OUR LOCATION' ?></h4>
            <iframe
                    src="<?= htmlspecialchars($settings['map_embed'], ENT_QUOTES, 'UTF-8') ?>"
                    style="border:0; border-radius:8px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?= $lang['office_name'] ?? 'सलकपुर खानेपानी / Salakpur KhanePani' ?>. <?= $lang['all_rights'] ?? 'All rights reserved.' ?></p>
        <span style="display:none;">
            <a href="https://acharyanischal.com.np" target="_blank">Developed by Nischal Acharya</a>
        </span>
    </div>
</footer>

<style>
    footer {
        background: #004080;
        color: #fff;
        margin-top: 30px;
        font-family: 'Arial', sans-serif;
    }

    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-section h4 {
        font-size: 16px;
        margin-bottom: 12px;
        border-bottom: 2px solid #1e90ff;
        display: inline-block;
        padding-bottom: 5px;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section ul li {
        margin: 6px 0;
    }

    .footer-section ul li a {
        color: #ddd;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .footer-section ul li a:hover {
        color: #1e90ff;
    }

    .footer-section p {
        margin: 5px 0;
        font-size: 14px;
    }

    .footer-section.social .social-icons {
        margin-top: 10px;
    }

    .footer-section.social a {
        font-size: 20px;
        color: #ddd;
        margin-right: 12px;
        display: inline-block;
        transition: color 0.3s, transform 0.3s;
    }

    .footer-section.social a:hover {
        color: #1e90ff;
        transform: scale(1.2);
    }

    .footer-section iframe {
        width: 100%;
        max-width: 320px;
        height: 200px;
        border: 0;
        border-radius: 8px;
    }

    .footer-bottom {
        background: #003366;
        text-align: center;
        padding: 15px;
        font-size: 14px;
        border-top: 1px solid #00264d;
    }

    .footer-bottom p {
        margin: 0;
    }

    /* Responsive for mobile devices */
    @media (max-width: 768px) {
        .footer-container {
            grid-template-columns: 1fr;
            padding: 30px 15px;
            gap: 20px;
        }

        .footer-section h4 {
            font-size: 15px;
        }

        .footer-section p,
        .footer-section ul li a {
            font-size: 13px;
        }

        .footer-section.social a {
            font-size: 18px;
            margin-right: 10px;
        }

        .footer-section iframe {
            height: 180px;
        }
    }

    @media (max-width: 480px) {
        .footer-container {
            padding: 20px 10px;
            gap: 15px;
        }

        .footer-section h4 {
            font-size: 14px;
        }

        .footer-section p,
        .footer-section ul li a {
            font-size: 12px;
        }

        .footer-section iframe {
            height: 150px;
        }
    }
</style>
