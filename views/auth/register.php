php
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Guest Registration | LuxeStay</title>

    <link
        rel="stylesheet"
        href="../../public/css/auth.css"
    >

    <style>
        .phone-input {
            display: flex;
            width: 100%;
            gap: 8px;
        }

        .phone-input select {
            width: 100px;
            flex-shrink: 0;
        }

        .phone-input input {
            flex: 1;
            min-width: 0;
        }
    </style>

</head>

<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Guest Registration</h1>

        <p>
            Welcome to LuxeStay.
            Create your guest account below.
        </p>

        <?php if (!empty($error)): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <!-- FULL NAME -->

            <label for="name">
                Full Name
            </label>

            <input
                id="name"
                type="text"
                name="name"
                placeholder="e.g. Jane Doe"
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                pattern="[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ\s'-]*"
                title="Name can contain letters, spaces, hyphens and apostrophes only."
                autocomplete="name"
                required
            >

            <!-- EMAIL -->

            <label for="email">
                Email Address
            </label>

            <input
                id="email"
                type="email"
                name="email"
                placeholder="jane@example.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                autocomplete="email"
                required
            >

            <!-- PHONE NUMBER -->

            <label for="phone">
                Phone Number
            </label>

            <div class="phone-input">

                <select
                    id="country_code"
                    name="country_code"
                    aria-label="Country code"
                    required
                >

                    <option value="+977"
                        <?= ($_POST['country_code'] ?? '+977') === '+977' ? 'selected' : '' ?>>
                        +977
                    </option>

                    <option value="+91"
                        <?= ($_POST['country_code'] ?? '') === '+91' ? 'selected' : '' ?>>
                        +91
                    </option>

                    <option value="+1"
                        <?= ($_POST['country_code'] ?? '') === '+1' ? 'selected' : '' ?>>
                        +1
                    </option>

                    <option value="+44"
                        <?= ($_POST['country_code'] ?? '') === '+44' ? 'selected' : '' ?>>
                        +44
                    </option>

                    <option value="+61"
                        <?= ($_POST['country_code'] ?? '') === '+61' ? 'selected' : '' ?>>
                        +61
                    </option>

                    <option value="+64"
                        <?= ($_POST['country_code'] ?? '') === '+64' ? 'selected' : '' ?>>
                        +64
                    </option>

                    <option value="+81"
                        <?= ($_POST['country_code'] ?? '') === '+81' ? 'selected' : '' ?>>
                        +81
                    </option>

                    <option value="+82"
                        <?= ($_POST['country_code'] ?? '') === '+82' ? 'selected' : '' ?>>
                        +82
                    </option>

                    <option value="+86"
                        <?= ($_POST['country_code'] ?? '') === '+86' ? 'selected' : '' ?>>
                        +86
                    </option>

                    <option value="+49"
                        <?= ($_POST['country_code'] ?? '') === '+49' ? 'selected' : '' ?>>
                        +49
                    </option>

                    <option value="+33"
                        <?= ($_POST['country_code'] ?? '') === '+33' ? 'selected' : '' ?>>
                        +33
                    </option>

                    <option value="+39"
                        <?= ($_POST['country_code'] ?? '') === '+39' ? 'selected' : '' ?>>
                        +39
                    </option>

                    <option value="+34"
                        <?= ($_POST['country_code'] ?? '') === '+34' ? 'selected' : '' ?>>
                        +34
                    </option>

                    <option value="+971"
                        <?= ($_POST['country_code'] ?? '') === '+971' ? 'selected' : '' ?>>
                        +971
                    </option>

                    <option value="+966"
                        <?= ($_POST['country_code'] ?? '') === '+966' ? 'selected' : '' ?>>
                        +966
                    </option>

                    <option value="+974"
                        <?= ($_POST['country_code'] ?? '') === '+974' ? 'selected' : '' ?>>
                        +974
                    </option>

                    <option value="+65"
                        <?= ($_POST['country_code'] ?? '') === '+65' ? 'selected' : '' ?>>
                        +65
                    </option>

                    <option value="+60"
                        <?= ($_POST['country_code'] ?? '') === '+60' ? 'selected' : '' ?>>
                        +60
                    </option>

                    <option value="+92"
                        <?= ($_POST['country_code'] ?? '') === '+92' ? 'selected' : '' ?>>
                        +92
                    </option>

                    <option value="+880"
                        <?= ($_POST['country_code'] ?? '') === '+880' ? 'selected' : '' ?>>
                        +880
                    </option>

                </select>

                <input
                    id="phone"
                    type="tel"
                    name="phone"
                    placeholder="9812345678"
                    inputmode="numeric"
                    pattern="[0-9]{7,15}"
                    maxlength="15"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                    title="Enter 7 to 15 digits. Do not include the country code."
                    autocomplete="tel"
                    required
                >

            </div>

            <!-- ADDRESS -->

            <label for="address">
                Residential Address
            </label>

            <textarea
                id="address"
                name="address"
                placeholder="123 Luxury Ave, City, Country"
                autocomplete="street-address"
                required
            ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

            <!-- PASSWORD -->

            <label for="password">
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                placeholder="Create a strong password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <div class="password-requirements">

                <small>
                    Password must contain:
                </small>

                <ul>
                    <li>At least 8 characters</li>
                    <li>One uppercase letter (A-Z)</li>
                    <li>One lowercase letter (a-z)</li>
                    <li>One number (0-9)</li>
                    <li>One special character (! @ # $ % etc.)</li>
                </ul>

            </div>

            <!-- CONFIRM PASSWORD -->

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                id="confirm_password"
                type="password"
                name="confirm_password"
                placeholder="Enter your password again"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <!-- TERMS -->

            <label class="checkbox">

                <input
                    type="checkbox"
                    name="terms"
                    required
                >

                <span>
                    I agree to the Terms of Service
                    and Privacy Policy.
                </span>

            </label>

            <!-- SUBMIT -->

            <button type="submit">
                Complete Registration →
            </button>

        </form>

        <p class="bottom-link">

            Already have an account?

            <a href="../../login.php">
                Log in here →
            </a>

        </p>

    </div>

</div>

</body>

</html>

