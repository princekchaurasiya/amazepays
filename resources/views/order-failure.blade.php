<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Order Failure Notification</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .email-container {
      max-width: 600px;
      margin: 20px auto;
      background-color: #ffffff;
      padding: 20px;
      border: 1px solid #dddddd;
      border-radius: 8px;
    }
    h1 {
      color: #d9534f;
      text-align: center;
    }
    p {
      font-size: 16px;
      color: #333333;
      line-height: 1.6;
    }
    .order-details {
      margin-top: 20px;
      border-collapse: collapse;
      width: 100%;
    }
    .order-details th, .order-details td {
      border: 1px solid #dddddd;
      padding: 10px;
      text-align: left;
    }
    .order-details th {
      background-color: #f2f2f2;
      font-weight: bold;
    }
    .resend-button {
      display: block;
      width: 100%;
      text-align: center;
      margin-top: 20px;
    }
    a.button {
      background-color: #5bc0de;
      color: #ffffff;
      padding: 12px 20px;
      text-decoration: none;
      border-radius: 5px;
      display: inline-block;
    }
    a.button:hover {
      background-color: #31b0d5;
    }
    .footer {
      margin-top: 20px;
      font-size: 12px;
      text-align: center;
      color: #777777;
    }
  </style>
</head>
<body>
  <div class='email-container'>
    <h1>Order Failed</h1>
    <p>Hello Admin,</p>
    <p>We encountered an issue processing the following order. Please review the details and take the necessary action from the admin panel.</p>

    <table class='order-details'>
      <tr>
        <th>Order ID</th>
        <td>#123456</td>
      </tr>
      <tr>
        <th>Customer Name</th>
        <td>John Doe</td>
      </tr>
      <tr>
        <th>Email</th>
        <td>johndoe@example.com</td>
      </tr>
      <tr>
        <th>Order Date</th>
        <td>25-Oct-2024</td>
      </tr>
      <tr>
        <th>Total Amount</th>
        <td>\$99.99</td>
      </tr>
      <tr>
        <th>Status</th>
        <td><strong>Failed</strong></td>
      </tr>
    </table>

    <div class='resend-button'>
      <a href='https://yourwebsite.com/admin/resend-order/123456' class='button'>Resend Order</a>
    </div>

    <div class='footer'>
      <p>This is an automated notification. Please do not reply to this email.</p>
    </div>
  </div>
</body>
</html>
