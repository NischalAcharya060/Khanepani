<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact - Khane Pani Office</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        .hidden { display: none; }
        .contact-form input, .contact-form select, .contact-form textarea, .contact-form button {
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        .contact-form button {
            background-color: #0056d6;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .contact-form button:hover {
            background-color: #003d99;
        }
    </style>
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
            <form action="contact_process.php" method="post" id="contactForm">
                <select name="type" id="type" required>
                    <option value="">-- Select Message Type --</option>
                    <option value="general">General Message</option>
                    <option value="complaint">Complaint</option>
                    <option value="suggestion">Suggestion / Feedback</option>
                </select>

                <input type="text" name="subject" id="subject" placeholder="Subject" required>

                <input type="text" name="name" placeholder="Your Name" required>
                <input type="email" name="email" placeholder="Your Email" required>

                <!-- Complaint-specific fields -->
                <div id="complaintFields" class="hidden">
                    <input type="text" name="complaint_ref" placeholder="Complaint Reference Number (if any)">
                    <textarea name="complaint_details" rows="4" placeholder="Complaint Details"></textarea>
                </div>

                <textarea name="message" id="message" rows="6" placeholder="Your Message" required></textarea>

                <button type="submit">Send Message</button>
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
            messageField.placeholder = "Provide any additional information about your complaint";
        } else if (this.value === 'suggestion') {
            complaintFields.classList.add('hidden');
            messageField.placeholder = "Write your suggestion or feedback here";
        } else {
            complaintFields.classList.add('hidden');
            messageField.placeholder = "Write your message here";
        }
    });
</script>

</body>
</html>
