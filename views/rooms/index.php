<?php

require_once __DIR__ . '/../../config/config.php';

requireLogin();

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Rooms | <?= htmlspecialchars(APP_NAME) ?>
</title>

<link
    rel="stylesheet"
    href="/public/css/style.css"
>
```

</head>

<body>

<header>

```
<div class="logo">
    🛏 LuxeStay
</div>

<nav>

    <a href="/index.php">
        Home
    </a>

    <a href="/views/rooms/index.php">
        Rooms
    </a>

    <a href="/logout.php">
        Logout
    </a>

</nav>
```

</header>

<main>

```
<section class="welcome">

    <p class="eyebrow">
        LUXESTAY ACCOMMODATION
    </p>

    <h1>
        Choose Your Room
    </h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user']['name']) ?>.
        Explore our available accommodations.
    </p>

    <div class="cards">

        <!-- STANDARD ROOM -->

        <article>

            <h2>
                Standard Room
            </h2>

            <p>
                Comfortable accommodation with
                everything you need for a relaxing stay.
            </p>

            <p>
                <strong>
                    $150 / night
                </strong>
            </p>

            <a href="/reservation.php?room_id=1">
                Reserve Room →
            </a>

        </article>


        <!-- DELUXE ROOM -->

        <article>

            <h2>
                Deluxe Room
            </h2>

            <p>
                Elevated comfort with premium amenities
                and beautiful views.
            </p>

            <p>
                <strong>
                    $285 / night
                </strong>
            </p>

            <a href="/reservation.php?room_id=2">
                Reserve Room →
            </a>

        </article>


        <!-- EXECUTIVE SUITE -->

        <article>

            <h2>
                Executive Suite
            </h2>

            <p>
                Spacious luxury with refined facilities
                and exceptional service.
            </p>

            <p>
                <strong>
                    $450 / night
                </strong>
            </p>

            <a href="/reservation.php?room_id=3">
                Reserve Room →
            </a>

        </article>

    </div>

</section>
```

</main>

<footer>

```
<strong>
    LuxeStay
</strong>

<p>
    Excellence in hospitality and refined comfort.
</p>


</footer>

</body>

</html>
