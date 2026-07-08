<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card signup-card">
        <div class="login-header">
            <img src="public/images/logo.png" alt="Cianan Corp Logo" class="logo">
            <h4>Create Account</h4>
            <small class="text-muted">Join Order and Billing System</small>
        </div>
        <div class="login-body">
            <?php if (!empty($errors ?? [])): ?>
                <div class="error-msg">
                    <?php foreach ($errors as $e): ?>
                        <i class="bi bi-x-circle me-1"></i><?= $e ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" placeholder="Enter full name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($old['username'] ?? '') ?>" placeholder="Choose username" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select" required>
                            <option value="">Select</option>
                            <option value="warehouse" <?= ($old['department'] ?? '') == 'warehouse' ? 'selected' : '' ?>>Warehouse</option>
                            <option value="production" <?= ($old['department'] ?? '') == 'production' ? 'selected' : '' ?>>Production</option>
                            <option value="finance" <?= ($old['department'] ?? '') == 'finance' ? 'selected' : '' ?>>Finance</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="Enter email address" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100 mt-2">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>
            <p class="login-link mb-0">Already have an account? <a href="?controller=auth&action=login">Sign In</a></p>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
