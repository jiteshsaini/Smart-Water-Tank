<?php
/*
Sets the shape and size of the tank.

The dashboard turns a sensor reading into litres with these, so they are worth
measuring properly. A PIN keeps passers by out; it sits in config.php beside
the database password.
*/

session_start();

require_once __DIR__ . '/util.php';

$MEASUREMENTS = array('diameter', 'length', 'breadth', 'depth');


function pin_required() {
    global $settings_pin;
    return isset($settings_pin) && $settings_pin !== '';
}


function unlocked() {
    return !empty($_SESSION['tank_settings_unlocked']);
}


/* A wrong PIN costs a second, which is enough to make guessing tedious. */
function try_pin($typed) {
    global $settings_pin;

    if (hash_equals((string) $settings_pin, (string) $typed)) {
        $_SESSION['tank_settings_unlocked'] = true;
        return true;
    }

    sleep(1);
    return false;
}


/* Whatever came back from the form, cleaned up. Anything odd becomes 0. */
function clean_posted() {
    global $MEASUREMENTS;

    $clean = array();
    $clean['shape'] = ($_POST['shape'] ?? 'cylinder') === 'box' ? 'box' : 'cylinder';

    foreach ($MEASUREMENTS as $name) {
        $value = $_POST[$name] ?? 0;
        $clean[$name] = is_numeric($value) ? round((float) $value, 1) : 0;
    }

    return $clean;
}


/* The measurements that matter for the chosen shape. */
function missing_measurements($wanted) {
    $missing = array();

    if ($wanted['depth'] <= 0) {
        $missing[] = 'depth';
    }
    if ($wanted['shape'] === 'box') {
        if ($wanted['length'] <= 0)  { $missing[] = 'length'; }
        if ($wanted['breadth'] <= 0) { $missing[] = 'breadth'; }
    } else {
        if ($wanted['diameter'] <= 0) { $missing[] = 'diameter'; }
    }

    return $missing;
}


function save_settings($wanted) {
    $connection = create_db_connection();

    $statement = mysqli_prepare($connection,
        "INSERT INTO `settings` (`name`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

    foreach ($wanted as $name => $value) {
        $text = (string) $value;
        mysqli_stmt_bind_param($statement, "ss", $name, $text);
        mysqli_stmt_execute($statement);
    }

    mysqli_stmt_close($statement);
    close_db_connection($connection);
}


function field($name, $label, $value, $note = '') {
    $note = $note === '' ? '' : "<span class='note'>$note</span>";
    echo "<label>$label
            <input type='number' name='$name' value='" . htmlspecialchars($value) . "'
                   min='0' max='2000' step='0.1'>
            $note
          </label>";
}


/* ------------------------------------------------------------- the page */

$message = '';
$trouble = '';

if (!pin_required()) {
    $trouble = "There is no PIN set. Put one in config.php, as \$settings_pin, before using this page.";
} elseif (isset($_POST['pin'])) {
    if (!try_pin($_POST['pin'])) {
        $trouble = "That PIN is not right.";
    }
} elseif (isset($_POST['shape']) && unlocked()) {
    $wanted = clean_posted();
    $missing = missing_measurements($wanted);

    if ($missing) {
        $trouble = "Still needed: " . implode(", ", $missing) . ".";
    } else {
        save_settings($wanted);
        $message = "Saved. The dashboard picks this up on its next load. If it still "
                 . "shows the old tank, your browser is holding the old page: reload it "
                 . "with Ctrl and Shift held down, or Cmd and Shift on a Mac.";
    }
}

$tank = tank_settings();
$capacity = tank_capacity($tank);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Tank size</title>
<style>
    body { font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0; padding: 24px; color: #222; }
    .card { max-width: 430px; margin: 0 auto; border: 1px solid #d8dbdf; border-radius: 10px; padding: 20px; }
    h1 { font-size: 19px; margin: 0 0 4px; }
    p.lead { color: #666; font-size: 13.5px; margin: 0 0 18px; }
    label { display: block; margin-bottom: 12px; }
    input[type=number], select { display: block; width: 100%; padding: 8px; margin-top: 4px;
                                 font: inherit; border: 1px solid #ccd2d6; border-radius: 6px; }
    .note { color: #777; font-size: 12.5px; }
    .row { display: none; }
    .row.on { display: block; }
    button { font: inherit; font-weight: 600; padding: 10px 18px; border: 0; border-radius: 7px;
             background: #12806a; color: #fff; cursor: pointer; }
    .msg { color: #12806a; margin-bottom: 14px; }
    .bad { color: #b3382d; margin-bottom: 14px; }
    .full { margin-top: 16px; padding-top: 14px; border-top: 1px solid #eceff1; color: #444; }
    a { color: #12806a; }
</style>
</head>
<body>
<div class="card">

<h1>Tank size</h1>
<p class="lead">The dashboard works out litres from these. Measure the inside of the tank.</p>

<?php if ($message): ?><p class="msg"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<?php if ($trouble): ?><p class="bad"><?= htmlspecialchars($trouble) ?></p><?php endif; ?>

<?php if (pin_required() && !unlocked()): ?>

    <form method="post">
        <label>PIN
            <input type="password" name="pin" autofocus>
            <span class="note">Set when the project was installed. See config.php.</span>
        </label>
        <button type="submit">Unlock</button>
    </form>

<?php elseif (pin_required()): ?>

    <form method="post">
        <label>Shape
            <select name="shape" id="shape" onchange="showFields()">
                <option value="cylinder" <?= $tank['shape'] === 'box' ? '' : 'selected' ?>>Round</option>
                <option value="box"      <?= $tank['shape'] === 'box' ? 'selected' : '' ?>>Rectangular</option>
            </select>
        </label>

        <div class="row" id="round">
            <?php field('diameter', 'Diameter, inside (cm)', $tank['diameter']); ?>
        </div>

        <div class="row" id="rect">
            <?php field('length', 'Length, inside (cm)', $tank['length']); ?>
            <?php field('breadth', 'Breadth, inside (cm)', $tank['breadth']); ?>
        </div>

        <?php field('depth', 'Depth (cm)', $tank['depth'], 'From the sensor down to the bottom.'); ?>

        <button type="submit">Save</button>
    </form>

    <p class="full">A full tank holds <b><?= $capacity ?> litres</b>.
       <br><a href="index.html">Back to the dashboard</a></p>

    <script>
    function showFields() {
        var box = document.getElementById("shape").value === "box";
        document.getElementById("rect").className = box ? "row on" : "row";
        document.getElementById("round").className = box ? "row" : "row on";
    }
    showFields();
    </script>

<?php endif; ?>

</div>
</body>
</html>
