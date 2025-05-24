<?php
require_once '../includes/init.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.location.href='../pages/contact.php';</script>";
        exit();
    }

    // Validate name (only letters, spaces, and basic punctuation)
    if (!preg_match('/^[a-zA-Z\s\.\'-]+$/', $name)) {
        echo "<script>alert('Name can only contain letters, spaces, and basic punctuation!'); window.location.href='../pages/contact.php';</script>";
        exit();
    }

    // Prepare email content
    $to = "info@printingpress.com"; // Replace with your email
    $email_subject = "Contact Form: $subject";
    $email_body = "You have received a new message from your website contact form.\n\n" .
        "Here are the details:\n\n" .
        "Name: $name\n\n" .
        "Email: $email\n\n" .
        "Subject: $subject\n\n" .
        "Message:\n$message";

    $headers = "From: $email\n";
    $headers .= "Reply-To: $email\n";

    // Send email
    if (mail($to, $email_subject, $email_body, $headers)) {
        echo "<script>alert('Thank you for your message. We will get back to you soon!'); window.location.href='../pages/contact.php';</script>";
    } else {
        echo "<script>alert('Sorry, there was an error sending your message. Please try again later.'); window.location.href='../pages/contact.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href='../pages/contact.php';</script>";
}
?>