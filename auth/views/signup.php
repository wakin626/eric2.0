<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Mfg. System</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #2c3e50 0%, #4a6741 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .signup-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
        }
        .signup-header { 
            background: #3498db; 
            color: #fff; 
            padding: 30px 20px; 
            text-align: center;
        }
        .signup-header i { font-size: 2.5rem; display: block; margin-bottom: 10px; }
        .signup-header h4 { margin: 0; font-weight: 600; }
        .signup-header small { opacity: 0.8; }
        .card-body { padding: 30px; }
        .form-control, .form-select { 
            border-radius: 8px; 
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #3498db; 
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .form-label { font-weight: 500; color: #555; margin-bottom: 5px; }
        .btn-signup {
            background: #3498db;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-signup:hover { background: #2980b9; }
        .login-link { 
            text-align: center; 
            margin-top: 20px;
            color: #666;
        }
        .login-link a { 
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }
        .alert { border-radius: 8px; }
    </style>
</head>
<body>
    <div class="signup-card">
        <div class="signup-header">
            <i class="bi bi-person-plus"></i>
            <h4>Create Account</h4>
            <small>Join Manufacturing System</small>
        </div>
        <div class="card-body">
            <?php if (!empty($errors ?? [])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <i class="bi bi-x-circle me-1"></i><?= $e ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($old['full_name'] ?? '') ?>" placeholder="Enter full name" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($old['username'] ?? '') ?>" placeholder="Choose username" required>
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
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="Enter email address" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-signup w-100">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>
            <p class="login-link mb-0">Already have an account? <a href="?controller=auth&action=login">Login</a></p>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>