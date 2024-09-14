<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amazepay | 503 Error</title>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS for the error page -->
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .error-container {
            text-align: center;
        }
        .error-container img {
            max-width: 100%;
            height: auto;
        }
        .error-container h1 {
            font-size: 3rem;
            margin-top: 20px;
        }
        .error-container p {
            font-size: 1.2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <div class="container error-container">
        <!-- Error Image -->
        <img src="{{ asset('images/error-503.jpg') }}" alt="503 Error" class="img-fluid mb-4">

        <!-- Error Heading -->
        <h1 class="display-4">503 - Service Unavailable</h1>

        <!-- Maintenance Message -->
        <p class="lead">Our site is currently under maintenance. We’ll be back shortly.</p>
        <p class="lead">Please check back later, or contact support if you need immediate assistance.</p>
    </div>

    <!-- Bootstrap JS (Optional if you need JS components) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.8/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
