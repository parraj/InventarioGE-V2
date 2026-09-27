<?php
/**
 * Editar Cuenta
 * --------------------------------------------------
 * Permite modificar los datos básicos de una cuenta.
 * Requiere nivel de acceso 1 (Administrador).
 */

$page_title = 'Editar Cuenta';
require_once('includes/load.php');
page_require_level(1);

/* =====================================================
 * OBTENER CUENTA
 * ===================================================== */
$account_id = (int)$_GET['id'];
$account = find_by_id('accounts', $account_id);

if (!$account) {
    $session->msg('d', 'Cuenta no encontrada.');
    redirect('accounts.php');
}

/* =====================================================
 * CARGAR HORARIOS
 * ===================================================== */
$opening_hours = [];

$sql = "SELECT day_week, opening_time
        FROM account_opening_hours
        WHERE id_account = '{$account_id}'";

$result = $db->query($sql);

while ($row = $db->fetch_assoc($result)) {
    $opening_hours[(int)$row['day_week']] = $row['opening_time'];
}

for ($i = 1; $i <= 7; $i++) {
    if (!isset($opening_hours[$i])) {
        $opening_hours[$i] = '08:00:00';
    }
}

/* =====================================================
 * PROCESAR ACTUALIZACIÓN
 * ===================================================== */
if (isset($_POST['account'])) {

    $req_fields = ['name'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name    = remove_junk($db->escape($_POST['name']));
        $address = remove_junk($db->escape($_POST['address']));
        $phone   = remove_junk($db->escape($_POST['phone']));
        $mail    = remove_junk($db->escape($_POST['mail']));

        /* ==========================
         * PROCESAR IMAGEN
         * ========================== */
        $image = upload_image($_FILES['image'], 'uploads/accounts/');

        if ($image === false) {
            $session->msg('d', 'Error en la imagen. Verifique formato o tamaño.');
            redirect("edit_acc.php?id={$account_id}");
        }

        if (!empty($image)) {

            if (!empty($account['image']) && file_exists($account['image'])) {
                unlink($account['image']);
            }

            $image_sql = ", image='{$image}'";

        } else {
            $image_sql = "";
        }

        /* ==========================
         * ACTUALIZAR CUENTA
         * ========================== */
        $sql = "UPDATE accounts SET
                    name='{$name}',
                    address='{$address}',
                    phone='{$phone}',
                    mail='{$mail}'
                    {$image_sql}
                WHERE id='{$account_id}'";

        $ok = $db->query($sql);

        /* ==========================
         * GUARDAR HORARIOS
         * ========================== */
        for ($day = 1; $day <= 7; $day++) {

            $day = (int)$day;
            $time = $db->escape($_POST['opening_time'][$day]);

            $check = $db->query("
                SELECT id
                FROM account_opening_hours
                WHERE id_account = {$account_id}
                AND day_week = {$day}
                LIMIT 1
            ");

            if ($db->num_rows($check) > 0) {

                $db->query("
                    UPDATE account_opening_hours
                    SET opening_time = '{$time}'
                    WHERE id_account = {$account_id}
                    AND day_week = {$day}
                ");

            } else {

                $db->query("
                    INSERT INTO account_opening_hours
                    (id_account, day_week, opening_time)
                    VALUES
                    ({$account_id}, {$day}, '{$time}')
                ");
            }
        }

        if ($ok) {
            $session->msg('s', 'Cuenta modificada correctamente.');
        } else {
            $session->msg('s', 'Horarios actualizados.');
        }

        redirect('accounts.php');

    } else {
        $session->msg('d', $errors);
        redirect("edit_acc.php?id={$account_id}");
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <?php echo display_msg($msg); ?>

        <h4 class="mb-4">
            <i class="bi bi-building me-2"></i>
            Editar Cuenta
        </h4>

        <form method="post"
              action="edit_acc.php?id=<?php echo $account_id; ?>"
              enctype="multipart/form-data"
              autocomplete="off">

            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">Nombre de la cuenta</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-bank"></i>
                    </span>
                    <input type="text"
                           name="name"
                           class="form-control"
                           value="<?php echo remove_junk($account['name']); ?>"
                           required>
                </div>
            </div>

            <!-- Dirección -->
            <div class="mb-3">
                <label class="form-label">Dirección</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-geo-alt"></i>
                    </span>
                    <input type="text"
                           name="address"
                           class="form-control"
                           value="<?php echo remove_junk($account['address']); ?>">
                </div>
            </div>

            <!-- Correo / Teléfono / Logo -->
            <div class="row mb-3">

                <div class="col-md-4">
                    <label class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email"
                               name="mail"
                               class="form-control"
                               value="<?php echo remove_junk($account['mail']); ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-telephone-fill"></i>
                        </span>
                        <input type="text"
                               name="phone"
                               class="form-control"
                               value="<?php echo remove_junk($account['phone']); ?>">
                    </div>
                </div>

                <div class="col-md-4">

                    <label class="form-label">Logo</label>

                    <input type="file"
                           name="image"
                           class="form-control"
                           accept="image/*">

                    <?php if (!empty($account['image']) && file_exists($account['image'])): ?>
                        <div class="mt-3 text-center">
                            <img src="<?php echo $account['image']; ?>"
                                 class="rounded-circle shadow"
                                 style="width:90px;height:90px;object-fit:cover;">
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <!-- Horarios desplegables -->
            <div class="mb-4">

                <div class="accordion" id="accordionHorario">

                    <div class="accordion-item">

                        <h2 class="accordion-header" id="headingHorario">
                            <button class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapseHorario"
                                    aria-expanded="false"
                                    aria-controls="collapseHorario">
                                <i class="bi bi-clock me-2"></i>
                                Horarios de apertura
                            </button>
                        </h2>

                        <div id="collapseHorario"
                             class="accordion-collapse collapse"
                             aria-labelledby="headingHorario"
                             data-bs-parent="#accordionHorario">

                            <div class="accordion-body">

                                <?php
                                $dias = [
                                    1 => 'Lunes',
                                    2 => 'Martes',
                                    3 => 'Miércoles',
                                    4 => 'Jueves',
                                    5 => 'Viernes',
                                    6 => 'Sábado',
                                    7 => 'Domingo'
                                ];
                                ?>

                                <?php foreach ($dias as $num => $nombre): ?>

                                    <div class="row align-items-center mb-3">

                                        <div class="col-md-4">
                                            <label class="form-label mb-0">
                                                <?php echo $nombre; ?>
                                            </label>
                                        </div>

                                        <div class="col-md-8">

                                            <div class="input-group">

                                                <span class="input-group-text">
                                                    <i class="bi bi-clock"></i>
                                                </span>

                                                <input type="time"
                                                       name="opening_time[<?php echo $num; ?>]"
                                                       class="form-control"
                                                       value="<?php echo $opening_hours[$num]; ?>"
                                                       step="1"
                                                       required>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-end">
                <button type="submit"
                        name="account"
                        class="btn btn-primary me-2">
                    Actualizar
                </button>

                <a href="accounts.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>

    </div>
</div>

<?php include_once('layouts/footer.php'); ?>