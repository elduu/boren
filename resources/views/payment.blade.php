<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Due Reminder</title>
</head>
<body>
    <h1>Payment Due Reminder</h1>
    <p>Dear {{ $tenantName }},</p>

    <p>This is a friendly reminder that your payment is due.</p>
    <ul>
        <li><strong>Room Number:</strong> {{ $roomNumber }}</li>
        <li><strong>Due Date:</strong> {{ $dueDate }}</li>
        <li><strong>Amount Due:</strong> ${{ number_format($amountDue, 2) }}</li>
    </ul>

    <p>Please make the payment at your earliest convenience.</p>
    <p>Thank you!</p>
</body>
</html>
