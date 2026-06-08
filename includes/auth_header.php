<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Auth' ?> - Ticketly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 420px;
            opacity: 0;
            transform: translateY(20px);
        }

        h2 {
            margin-bottom: 1.8rem;
            text-align: center;
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .form-row {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .input-field, select {
            width: 100%;
            height: 50px;
            padding: 0 1rem;
            border-radius: 0.6rem;
            border: 1px solid #ddd;
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .input-field:focus, select:focus {
            border-color: #2575fc;
            box-shadow: 0 0 8px rgba(37,117,252,0.4);
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper .input-field {
            padding-right: 48px;
        }

        .eye-icon {
            position: absolute;
            right: 14px;
            top: 0;
            height: 50px;
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            user-select: none;
            color: #666;
        }

        .btn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 0.6rem;
            background: #2575fc;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 0.5rem;
        }

        .btn:hover {
            background: #1b5dd6;
            transform: translateY(-2px);
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 0.5rem;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
            text-align: center;
            border-left: 4px solid #c62828;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0 1.5rem;
            font-size: 0.9rem;
        }

        .links {
            text-align: center;
            margin-top: 1.5rem;
            color: #555;
            font-size: 0.9rem;
        }

        .links a {
            color: #2575fc;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card {
                padding: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2><?= $page_title ?></h2>
        <?php if (isset($err) && $err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 0.8rem; border-radius: 0.5rem; margin-bottom: 1.2rem; font-size: 0.9rem; text-align: center; border-left: 4px solid #2e7d32;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
