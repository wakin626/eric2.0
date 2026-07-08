<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <img src="public/images/logo.png" alt="Cianan Corp Logo" class="logo">
            <h4>Order and Billing System</h4>
            <small class="text-muted">Sign in to your account</small>
        </div>
        <div class="login-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg">
                    <i class="bi bi-exclamation-circle me-1"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            <p class="signup-link mb-0">Don't have an account? <a href="?controller=auth&action=signup">Sign Up</a></p>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
