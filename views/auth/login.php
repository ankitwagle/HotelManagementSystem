
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | LuxeStay</title>

    <link
        rel="stylesheet"
        href="../../public/css/auth.css"
    >

</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Welcome Back</h1>

        <p>
            Sign in to manage your LuxeStay account.
        </p>


        <?php if (!empty($error)): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <!-- EMAIL -->

            <label for="email">
                Email Address
            </label>

            <input
                id="email"
                type="email"
                name="email"
                placeholder="you@example.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                autocomplete="email"
                required
            >


            <!-- PASSWORD -->

            <label for="password">
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                placeholder="Your password"
                autocomplete="current-password"
                required
            >


            <!-- REMEMBER ME -->

            <label class="checkbox">

                <input
                    type="checkbox"
                    name="remember"
                >

                <span>
                    Remember me
                </span>

            </label>


            <!-- LOGIN BUTTON -->

            <button type="submit">
                Sign In →
            </button>

        </form>


        <!-- REGISTER -->

        <p class="bottom-link">

            Don't have an account?

            <a href="../../register.php">
                Create one →
            </a>

        </p>

    </div>

</div>

</body>

</html>

