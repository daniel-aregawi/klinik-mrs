<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            text-align: center;
            /* Center the text within the container */
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        p {
            color: #555;
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin-bottom: 10px;
        }

        .success-image {
            display: block;
            margin: 0 auto;
            /* Center the image */
            width: 100px;
            /* Adjust the width */
            height: auto;
            /* Maintain aspect ratio */
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Payment Success</h2>
        <img class="success-image" src="{{ asset('/email-image/payment_success_icon.png') }}" alt="Success Image">

        <p>Dear {{ $appointment->user->name }},</p>
        <p>Your payment for the Appointment with ID {{ $appointment->order_id }} has been successfully processed.</p>

        <h3>Order Details:</h3>
        <ul>
            <li>Order ID: {{ $appointment->order_id }}</li>
            <li>Total Amount: ${{ $appointment->doctor->appointment_price }}</li>
            <li>Date: {{ $appointment->date }}</li>
            <li>Day: {{ \Carbon\Carbon::parse($appointment->date)->format('l') }}</li>
            <li>Start Time: {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}</li>
            <li>End Time: {{ \Carbon\Carbon::parse($appointment->end_time)->format('H:i') }}</li>
        </ul>

        <p>Thank you for your purchase!</p>
        <p>Best Regards,<br>Klinik</p>
    </div>

</body>

</html>