<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Due Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #004085;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
        }
        .button {
            display: inline-block;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Payment Due Reminder</h1>
        </div>
        <div class="content">
            <p>Dear {{ $tenantName }},</p>

            <p>This is a friendly reminder that your payment is due.</p>
            <ul>
                <li><strong>Room Number:</strong> {{ $roomNumber }}</li>
                <li><strong>Due Date:</strong> {{ $dueDate }}</li>
              
            </ul>

            <p>Please make the payment at your earliest convenience.</p>
            <p>Thank you!</p>

            <p>If you have any questions, feel free to contact our support team.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ now()->year }} Your Company Name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>