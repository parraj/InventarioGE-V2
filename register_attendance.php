<?php
require_once('includes/load.php');
page_require_level(2);

/* ==============================================================
   FUNCIÓN GENERAL PARA MOSTRAR SWEETALERT
============================================================== */
function show_attendance_alert($title, $html, $icon = 'success')
{
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Control de Asistencia</title>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>

    <body>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: <?php echo json_encode($title); ?>,
                html: <?php echo json_encode($html); ?>,
                icon: <?php echo json_encode($icon); ?>,
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: true
            }).then(function () {
                window.location.href = 'home.php';
            });
        });
    </script>

    </body>
    </html>
    <?php

    exit;
}


/* ==============================================================
   FUNCIÓN PARA OBTENER TOLERANCIAS UTILIZADAS EN EL MES

   Una sola función para toda la lógica de tolerancias.
============================================================== */
function get_used_tolerances($db, $employee_id, $month_start, $month_end, $opening_time, $tolerance_time)
{
    $opening = date('H:i:s', $opening_time);
    $tolerance = date('H:i:s', $tolerance_time);

    $query  = "SELECT COUNT(*) AS total ";
    $query .= "FROM employees_attendance ";
    $query .= "WHERE employee_id = '{$employee_id}' ";
    $query .= "AND status = 'A tiempo' ";
    $query .= "AND check_in BETWEEN '{$month_start}' AND '{$month_end}' ";
    $query .= "AND TIME(check_in) > '{$opening}' ";
    $query .= "AND TIME(check_in) <= '{$tolerance}'";

    $result = $db->query($query);
    $data = $db->fetch_assoc($result);

    return (int)$data['total'];
}


/* ==============================================================
   FUNCIÓN PARA CONSTRUIR EL ESTADO DE LLEGADA Y PRESENTISMO

   La lógica de presentismo queda en un solo lugar.
============================================================== */
function get_arrival_status_html($status)
{
    if ($status === 'Tarde') {

        return array(
            'status_html' => '
                <span style="color:#dc3545;font-weight:bold;">
                    Tarde
                </span>
            ',
            'presentism_html' => '
                <span style="
                    display:inline-block;
                    padding:5px 10px;
                    border-radius:5px;
                    background:#dc3545;
                    color:#fff;
                    font-weight:bold;
                ">
                    Presentismo Perdido
                </span>
            ',
            'icon' => 'warning'
        );

    }

    return array(
        'status_html' => '
            <span style="color:#198754;font-weight:bold;">
                A tiempo
            </span>
        ',
        'presentism_html' => '
            <span style="
                display:inline-block;
                padding:5px 10px;
                border-radius:5px;
                background:#198754;
                color:#fff;
                font-weight:bold;
            ">
                Conserva Presentismo
            </span>
        ',
        'icon' => 'success'
    );
}


/* ==============================================================
   VALIDAR DNI
============================================================== */
if (!isset($_POST['dni']) || empty($_POST['dni'])) {
    redirect('home.php', false);
}

$dni = remove_junk($db->escape($_POST['dni']));


/* ==============================================================
   BUSCAR EMPLEADO ACTIVO
============================================================== */
$employee_data = find_by_sql("
    SELECT *
    FROM employees
    WHERE dni = '{$dni}'
      AND active = 1
    LIMIT 1
");

if (!is_array($employee_data) || empty($employee_data)) {

    show_attendance_alert(
        'Empleado no encontrado',
        '<div style="font-size:16px;">
            El empleado no existe o se encuentra inactivo.
        </div>',
        'error'
    );
}

$employee = $employee_data[0];

$employee_id = (int)$employee['id'];
$account_id  = (int)$employee['account'];

$employee_name = htmlspecialchars(
    ucwords($employee['name']),
    ENT_QUOTES,
    'UTF-8'
);

$now = date('Y-m-d H:i:s');
$current_time = date('H:i:s');


/* ==============================================================
   OBTENER HORARIO SEGÚN EL DÍA
   1 = Lunes ... 7 = Domingo
============================================================== */
$day_week = date('N');

$schedule = find_by_sql("
    SELECT opening_time
    FROM account_opening_hours
    WHERE id_account = '{$account_id}'
      AND day_week = '{$day_week}'
    LIMIT 1
");

if (!is_array($schedule) || empty($schedule)) {

    show_attendance_alert(
        'Horario no configurado',
        '<div style="font-size:16px;">
            No se encontró un horario configurado para hoy.
        </div>',
        'error'
    );
}

$opening_time_value = $schedule[0]['opening_time'];

$opening_time   = strtotime($opening_time_value);
$tolerance_time = strtotime('+5 minutes', $opening_time);
$check_time     = strtotime($current_time);


/* ==============================================================
   RANGO DEL MES
============================================================== */
$month_start = date('Y-m-01 00:00:00');
$month_end   = date('Y-m-t 23:59:59');


/* ==============================================================
   RANGO DEL DÍA
============================================================== */
$today_start = date('Y-m-d 00:00:00');
$today_end   = date('Y-m-d 23:59:59');


/* ==============================================================
   BUSCAR REGISTRO DEL DÍA
============================================================== */
$attendance_today = find_by_sql("
    SELECT *
    FROM employees_attendance
    WHERE employee_id = '{$employee_id}'
      AND check_in BETWEEN '{$today_start}' AND '{$today_end}'
    LIMIT 1
");


/* ==============================================================
   CALCULAR TOLERANCIAS ACTUALES

   Se calcula una sola vez al comienzo.
   Para la primera marcación se utiliza el valor anterior.
   Después de insertar la entrada, se incrementa solamente
   si esa entrada utilizó una tolerancia.
============================================================== */
$used_tolerances = get_used_tolerances(
    $db,
    $employee_id,
    $month_start,
    $month_end,
    $opening_time,
    $tolerance_time
);


/* ==============================================================
   PRIMER REGISTRO DEL DÍA
   ENTRADA
============================================================== */
if (empty($attendance_today)) {

    /* ==========================================================
       DETERMINAR ESTADO DE LLEGADA

       Misma lógica original:
       - Antes o exactamente a la apertura: A tiempo
       - Hasta 5 minutos después:
         primeras 3 tolerancias = A tiempo
         desde la cuarta = Tarde
       - Después de 5 minutos = Tarde
    ========================================================== */

    $used_tolerance_today = false;

    if ($check_time <= $opening_time) {

        $status = 'A tiempo';

    } elseif ($check_time <= $tolerance_time) {

        if ($used_tolerances < 3) {

            $status = 'A tiempo';
            $used_tolerance_today = true;

        } else {

            $status = 'Tarde';
        }

    } else {

        $status = 'Tarde';
    }


    /* ==========================================================
       INSERTAR ASISTENCIA
    ========================================================== */

    $insert_query  = "INSERT INTO employees_attendance ";
    $insert_query .= "(employee_id, check_in, status, account, opening_time) ";
    $insert_query .= "VALUES (";
    $insert_query .= "'{$employee_id}', ";
    $insert_query .= "'{$now}', ";
    $insert_query .= "'{$status}', ";
    $insert_query .= "'{$account_id}', ";
    $insert_query .= "'{$opening_time_value}'";
    $insert_query .= ")";

    if (!$db->query($insert_query)) {

        show_attendance_alert(
            'Error',
            '<div style="font-size:16px;">
                No fue posible registrar la entrada.
            </div>',
            'error'
        );
    }


    /* ==========================================================
       ACTUALIZAR TOLERANCIAS DESPUÉS DE REGISTRAR

       Solo aumenta si esta entrada realmente consumió
       una tolerancia.
    ========================================================== */

    if ($used_tolerance_today) {
        $used_tolerances++;
    }


    /* ==========================================================
       ESTADO Y PRESENTISMO
    ========================================================== */

    $arrival_status = get_arrival_status_html($status);


    /* ==========================================================
       SWEETALERT - ENTRADA
    ========================================================== */

    $html = '
        <div style="
            text-align:left;
            font-size:16px;
            line-height:1.8;
        ">

            <div>
                <strong>Empleado:</strong><br>
                ' . $employee_name . '
            </div>

            <div>
                <strong>Hora de llegada:</strong><br>
                ' . htmlspecialchars($current_time, ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Hora de apertura:</strong><br>
                ' . htmlspecialchars($opening_time_value, ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Estado:</strong><br>
                ' . $arrival_status['status_html'] . '
            </div>

            <div>
                <strong>Tolerancias utilizadas:</strong><br>
                ' . $used_tolerances . ' / 3
            </div>

            <div style="margin-top:10px;">
                <strong>Presentismo:</strong><br>
                ' . $arrival_status['presentism_html'] . '
            </div>

        </div>
    ';

    show_attendance_alert(
        'Entrada registrada',
        $html,
        $arrival_status['icon']
    );
}


/* ==============================================================
   REGISTRO EXISTENTE DEL DÍA
============================================================== */

$attendance = $attendance_today[0];

$attendance_id = (int)$attendance['id'];


/* ==============================================================
   OBTENER ESTADO Y PRESENTISMO DE LA ENTRADA

   Se calcula UNA SOLA VEZ y se reutiliza tanto para la salida
   de almuerzo como para el regreso.
============================================================== */

$arrival_status = get_arrival_status_html(
    $attendance['status']
);


/* ==============================================================
   SEGUNDO REGISTRO
   SALIDA A ALMUERZO
============================================================== */
if (empty($attendance['lunch_time_departure'])) {

    $update = "
        UPDATE employees_attendance
        SET lunch_time_departure = '{$now}'
        WHERE id = '{$attendance_id}'
    ";

    if (!$db->query($update)) {

        show_attendance_alert(
            'Error',
            '<div style="font-size:16px;">
                Error al registrar la salida a almuerzo.
            </div>',
            'error'
        );
    }


    /* ==========================================================
       SWEETALERT - SALIDA ALMUERZO
    ========================================================== */

    $html = '
        <div style="
            text-align:left;
            font-size:16px;
            line-height:1.8;
        ">

            <div>
                <strong>Empleado:</strong><br>
                ' . $employee_name . '
            </div>

            <div>
                <strong>Hora de llegada:</strong><br>
                ' . htmlspecialchars($attendance['check_in'], ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Estado de llegada:</strong><br>
                ' . $arrival_status['status_html'] . '
            </div>

            <div>
                <strong>Tolerancias utilizadas:</strong><br>
                ' . $used_tolerances . ' / 3
            </div>

            <div style="margin-top:10px;">
                <strong>Presentismo:</strong><br>
                ' . $arrival_status['presentism_html'] . '
            </div>

            <hr>

            <div>
                <strong>Salida a almorzar:</strong><br>
                ' . htmlspecialchars($current_time, ENT_QUOTES, 'UTF-8') . '
            </div>

        </div>
    ';

    show_attendance_alert(
        'Salida a almorzar registrada',
        $html,
        'success'
    );
}


/* ==============================================================
   TERCER REGISTRO
   REGRESO DE ALMUERZO
============================================================== */
if (empty($attendance['lunch_time_entrance'])) {

    $update = "
        UPDATE employees_attendance
        SET lunch_time_entrance = '{$now}'
        WHERE id = '{$attendance_id}'
    ";

    if (!$db->query($update)) {

        show_attendance_alert(
            'Error',
            '<div style="font-size:16px;">
                Error al registrar el regreso del almuerzo.
            </div>',
            'error'
        );
    }


    /* ==========================================================
       CALCULAR DURACIÓN DEL ALMUERZO
    ========================================================== */

    $departure = $attendance['lunch_time_departure'];

    $start = strtotime($departure);
    $end   = strtotime($now);

    $diff_seconds = 0;

    if ($end > $start) {
        $diff_seconds = $end - $start;
    }

    $time_diff = gmdate('H:i:s', $diff_seconds);

    $minutes = $diff_seconds / 60;


    /* ==========================================================
       ESTADO DEL ALMUERZO
    ========================================================== */

    if ($minutes > 60) {

        $status_lunch = 'Tarde';

        $status_lunch_html = '
            <span style="color:#dc3545;font-weight:bold;">
                Tarde
            </span>
        ';

    } else {

        $status_lunch = 'A tiempo';

        $status_lunch_html = '
            <span style="color:#198754;font-weight:bold;">
                A tiempo
            </span>
        ';
    }


    /* ==========================================================
       SWEETALERT - REGRESO ALMUERZO
    ========================================================== */

    $html = '
        <div style="
            text-align:left;
            font-size:16px;
            line-height:1.8;
        ">

            <div>
                <strong>Empleado:</strong><br>
                ' . $employee_name . '
            </div>

            <div>
                <strong>Hora de llegada:</strong><br>
                ' . htmlspecialchars($attendance['check_in'], ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Estado de llegada:</strong><br>
                ' . $arrival_status['status_html'] . '
            </div>

            <div>
                <strong>Tolerancias utilizadas:</strong><br>
                ' . $used_tolerances . ' / 3
            </div>

            <div style="margin-top:10px;">
                <strong>Presentismo:</strong><br>
                ' . $arrival_status['presentism_html'] . '
            </div>

            <hr>

            <div>
                <strong>Salida a almorzar:</strong><br>
                ' . htmlspecialchars($departure, ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Regreso:</strong><br>
                ' . htmlspecialchars($current_time, ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Tiempo de almuerzo:</strong><br>
                ' . htmlspecialchars($time_diff, ENT_QUOTES, 'UTF-8') . '
            </div>

            <div>
                <strong>Estado del almuerzo:</strong><br>
                ' . $status_lunch_html . '
            </div>

        </div>
    ';

    show_attendance_alert(
        'Regreso de almuerzo registrado',
        $html,
        ($status_lunch === 'Tarde' ? 'warning' : 'success')
    );
}


/* ==============================================================
   TRES REGISTROS COMPLETOS
============================================================== */

show_attendance_alert(
    'Registros completos',
    '<div style="font-size:16px;">
        Este empleado ya completó sus tres registros del día.
    </div>',
    'info'
);

?>