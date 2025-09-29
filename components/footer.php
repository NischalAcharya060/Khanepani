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
                <li><a href="#"><?= $lang['our_services'] ?? 'Our Services' ?></a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h4><?= $lang['contact_us'] ?? 'CONTACT US' ?></h4>
            <p><strong><?= $lang['office_name'] ?? 'सलकपुर खानेपानी' ?></strong></p>
            <p><?= $lang['office_address'] ?? 'Salakpur, Nepal' ?></p>
            <p><?= $lang['phone'] ?? 'Phone' ?>: 977-1-4117356, 4117358</p>
            <p><?= $lang['fax'] ?? 'Fax' ?>: 977-1-4259824, 4262229</p>
            <p><?= $lang['user_email'] ?? 'Email' ?>: info@salakpurwater.org</p>
            <p><?= $lang['zip_code'] ?? 'Zip code' ?>: 57200</p>
        </div>

        <div class="footer-section social">
            <h4><?= $lang['social_media'] ?? 'SOCIAL MEDIA' ?></h4>
            <div class="social-icons">
                <a href="https://www.facebook.com/profile.php?id=61578812410424" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>
        </div>

        <div class="footer-section">
            <h4><?= $lang['our_location'] ?? 'OUR LOCATION' ?></h4>
            <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3565.5095854783654!2d87.36577937488643!3d26.664180170728024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef6f34daba5585%3A0x243be79d3c22c683!2sSalakpur%20khanepani!5e0!3m2!1sen!2snp!4v1758365945264!5m2!1sen!2snp"
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
    /* Footer */
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
