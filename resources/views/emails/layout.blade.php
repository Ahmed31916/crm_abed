<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-sizing: border-box;
        }

        .header {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .header img {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }

        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 10px !important;
            }
            .header {
                padding: 15px !important;
            }
        }

        @yield('styles')
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset(getCompanyLogo()) }}" alt="{{ config('app.name') }}">
        </div>

        @yield('content')

        <div class="footer">
            <p>@yield('footer', 'This is an automated email from ' . config('app.name'))</p>
        </div>
    </div>
</body>

</html>
