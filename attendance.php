<?php
$page_title = 'Lista de Asistencias';
require_once('includes/load.php');
page_require_level(1);

/* ================================================================
   MES Y AÑO
================================================================ */
$current_month = date('m');
$current_year = date('Y');

$month = $current_month;
$year = $current_year;

if (isset($_POST['submit']) && !empty($_POST['date'])) {
    list($year, $month) = explode('-', $_POST['date']);
}

/* ================================================================
   DATOS
================================================================ */
$employees = find_all_employees('employees');
$attendances = get_month_attendances($month, $year);

/* ================================================================
   RESUMEN
================================================================ */
$employees_summary = [];

foreach ($employees as $emp) {

    $info = get_attendance_summary(
        $emp['id'],
        $month,
        $year,
        $emp['account']
    );

    if ($info['used_tolerance'] == 0 && $info['has_bonus']) {
        $color = 'panel-success';
    } elseif ($info['used_tolerance'] <= 3 && $info['has_bonus']) {
        $color = 'panel-warning';
    } else {
        $color = 'panel-danger';
    }

    $employees_summary[] = [
        'name' => $emp['name'],
        'used_tolerance' => $info['used_tolerance'],
        'color' => $color
    ];
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<!-- SELECTOR -->
<div class="col-md-3 mb-3">
    <form method="post" action="attendance.php" class="row g-2 align-items-center">
        <div class="col-auto flex-grow-1">
            <div class="input-group w-100">
                <input type="month" class="form-control" name="date" required>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" name="submit" class="btn btn-primary">Consultar</button>
        </div>
    </form>
</div>

<!-- RESUMEN -->
<div class="row mt-4">
    <?php foreach ($employees_summary as $emp): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-header <?php
                echo $emp['color'] == 'panel-success' ? 'bg-success text-white' :
                    ($emp['color'] == 'panel-warning' ? 'bg-warning text-dark' :
                        'bg-danger text-white');
                ?>">
                    <strong><?php echo remove_junk(ucwords($emp['name'])); ?></strong>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <b>Tolerancias Usadas:</b> <?php echo $emp['used_tolerance']; ?> / 3
                    </p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ==============================================================
     TABS (SIN CAMBIAR TUS TABLAS)
============================================================== -->
<div class="col-md-12">

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">
                Control de Asistencia
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">
                Control de Almuerzo
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3">

        <!-- ========================= -->
        <!-- TAB 1: TU TABLA ORIGINAL -->
        <!-- ========================= -->
        <div class="tab-pane fade show active" id="tab1">

            <div class="row">
                <div class="col-md-12 table-responsive">
                    <h5 class="mb-3"><i class="ti ti-archive me-2"></i>Control de Asistencia</h5>

                    <table id="datatable" class="table text-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Empleado</th>
                                <th>Hora de Apertura</th>
                                <th>Fecha y Hora de Llegada</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Sede</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendances as $atd): ?>
                                <tr>
                                    <td class="text-center"><?php echo $atd['id']; ?></td>
                                    <td><?php echo remove_junk(ucwords($atd['name'])); ?></td>
                                    <td><?php echo $atd['opening_time']; ?></td>
                                    <td><?php echo $atd['check_in']; ?></td>
                                    <td class="text-center"><?php echo $atd['status']; ?></td>
                                    <td class="text-center"><?php echo $atd['account_name']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

        <!-- ========================= -->
        <!-- TAB 2: ALMUERZO -->
        <!-- ========================= -->
        <div class="tab-pane fade" id="tab2">

            <div class="row">
                <div class="col-md-12 table-responsive">
                    <h5 class="mb-3">Control de Almuerzo</h5>

                    <!-- MISMO ESTILO DE TABLA -->
                    <table class="table text-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px;">ID</th>
                                <th>Empleado</th>
                                <th>Salida a Almorzar</th>
                                <th>Regreso</th>
                                <th class="text-center">Minutos</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($attendances as $atd): ?>

                                <?php
                                $departure = $atd['lunch_time_departure'];
                                $entrance = $atd['lunch_time_entrance'];

                                $time_diff = '-';
                                $status_lunch = '-';

                                if (!empty($departure) && !empty($entrance)) {

                                    $start = strtotime($departure);
                                    $end = strtotime($entrance);

                                    if ($end > $start) {

                                        $diff_seconds = $end - $start;

                                        // 👇 FORMATO HH:MM:SS
                                        $time_diff = gmdate("H:i:s", $diff_seconds);

                                        // Para el estado seguimos usando minutos
                                        $minutes = $diff_seconds / 60;
                                        $status_lunch = ($minutes > 60) ? 'Tarde' : 'A tiempo';
                                    }
                                }
                                ?>

                                <tr>
                                    <td class="text-center"><?php echo $atd['id']; ?></td>
                                    <td><?php echo remove_junk(ucwords($atd['name'])); ?></td>
                                    <td><?php echo !empty($departure) ? $departure : 'Pendiente'; ?></td>
                                    <td><?php echo !empty($entrance) ? $entrance : 'Pendiente'; ?></td>
                                    <td class="text-center"><?php echo $time_diff; ?></td>
                                    <td class="text-center"><?php echo $status_lunch; ?></td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>

</div>

<?php include_once('layouts/footer.php'); ?>