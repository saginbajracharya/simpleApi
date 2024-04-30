<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        /* Add your custom styles here */
        .highlight {
            font-size: 24px; /* Adjust the font size as needed */
            padding: 5px 10px; /* Add padding inside the rectangular container */
            border: 2px solid #ff0000; /* Set the border color and width */
            background-color: #ffc0cb; /* Set the background color */
            border-radius: 5px; /* Optional: Add border-radius for rounded corners */
            color: #000; /* Set font color to black */
        }
        .bold {
            font-weight: bold; /* Make the text bold */
            color: #000; /* Set font color to black */
        }
        .larger-font {
            font-size: 18px; /* Set a larger font size for the company name */
            color: #000; /* Set font color to black */
        }
    </style>
</head>
<body>
    <p>Hello <span class="bold">{{ $user->name }}</span>,</p>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <p><span class="highlight">{{ $token }}</span> - Use this token to reset your password.</p>
    <p>If you did not request a password reset, no further action is required.</p>
    <p>Regards,<br><span class="larger-font bold">DLofiStudio</span></p>
</body>
</html>
