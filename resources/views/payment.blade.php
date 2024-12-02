<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Due Reminder</title>
</head>
<body>
    <h2>Hello {{ $tenantName }},</h2>
    <p>This is a friendly reminder that your payment of <strong>{{ $amount }}</strong> is due on <strong>{{ $dueDate }}</strong>.</p>
    <p>Please make sure to complete the payment by the due date to avoid any penalties.</p>
    <p>If you have any questions, feel free to reach out to us.</p>
    <p>Thank you, <br> Your Property Management Team</p>
</body>
</html>
