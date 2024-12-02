<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Lead Inquiry</title>
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
            color: #5bc0de;
            text-align: center;
        }

        p {
            font-size: 16px;
            color: #333333;
            line-height: 1.6;
        }

        .lead-details {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }

        .lead-details th,
        .lead-details td {
            border: 1px solid #dddddd;
            padding: 10px;
            text-align: left;
        }

        .lead-details th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>New Lead Inquiry for Amazepay</h1>

        <p>You have received a new lead inquiry. Below are the details:</p>

        <table class="lead-details">
            <tr>
                <th>Name</th>
                <td>{{ $leadDetails['name'] }}</td>
            </tr>
            <tr>
                <th>Mobile Number</th>
                <td>{{ $leadDetails['contact_number']  }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $leadDetails['email'] }}</td>
            </tr>
            <tr>
                <th>Message</th>
                <td>{{ $leadDetails['message'] }}</td>
            </tr>
        </table>

        <p>Please follow up with the lead as soon as possible.</p>
    </div>
</body>

</html>
