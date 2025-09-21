<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'components/header.php'; ?>

<section class="contact-section container">
    <h2>Contact Us</h2>
    <div class="contact-content">
        <!-- Contact Info -->
        <div class="contact-info">
            <h3>Contact Details</h3>
            <p>📞 +977 1 4117356, 4117358</p>
            <p>✉ info@salakpurkhanepani.com</p>
            <div class="map">
                <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3565.5095854783654!2d87.36577937488643!3d26.664180170728024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39ef6f34daba5585%3A0x243be79d3c22c683!2sSalakpur%20khanepani!5e0!3m2!1sen!2snp!4v1758365945264!5m2!1sen!2snp"
                        width="100%"
                        height="300"
                        style="border:0; border-radius:10px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form">
            <h3>Send Us a Message</h3>
            <form action="contact_process.php" method="post">
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="email" name="email" placeholder="Your Email" required>
                <textarea name="message" rows="6" placeholder="Your Message" required></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>
