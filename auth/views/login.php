<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #2c3e50 0%, #4a6741 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            height: 700px;
        }
        .login-header { 
            background: #fff; 
            padding: 30px 20px 10px; 
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .login-header img.logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }
        .login-header h4 { margin: 0; font-weight: 600; color: #2c3e50; }
        .login-header small { color: #6c757d; }
        .card-body { padding: 30px; }
        .form-control { 
            border-radius: 8px; 
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus { 
            border-color: #3498db; 
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .form-label { font-weight: 500; color: #555; margin-bottom: 5px; }
        .btn-login {
            background: #3498db;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-login:hover { background: #2980b9; }
        .signup-link { 
            text-align: center; 
            margin-top: 20px;
            color: #666;
        }
        .signup-link a { 
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }
        .signup-link a:hover { text-decoration: underline; }
        .alert { border-radius: 8px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="public/images/logo.png" alt="Cianan Corp Logo" class="logo">
            
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
            <p class="signup-link mb-0">Don't have an account? <a href="?controller=auth&action=signup">Sign Up</a></p>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>