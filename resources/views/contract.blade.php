<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Renewal Reminder</title>
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Contract Renewal Reminder</h1>
        </div>
        <div class="content">
            <p>Dear customer</p>
            <p>We hope this email finds you well. We wanted to remind you that your contract is set to expire on
                 {{-- <strong>{{ $customer->contract_expiration->format('F j, Y') }}</strong>.</p> --}}
            <p>Please renew your contract before the expiration date to ensure uninterrupted service.</p>
            <p>Click the button below to renew your contract now:</p>
          {{-- <a href="{{ url('/renew/' . $customer->id) }}" class="button">Renew Contract</a> --}}
            <p>If you have any questions or need assistance, please feel free to contact our support team.</p>
            <p>Thank you for choosing our service!</p>
        </div>
        <div class="footer">
            <p>&copy; {{--  --}} Your Company Name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>