<?php

/* =====================================================
 * FUNCIONES GENERALES DE BASE DE DATOS
 * ===================================================== */

/**
 * Obtiene todos los registros activos de una tabla.
 *
 * @param string $table Nombre de la tabla.
 * @return array|null Registros activos o null si la tabla no existe.
 */
function find_all($table)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $table = $db->escape($table);
    return find_by_sql("SELECT * FROM {$table} WHERE active = '1'");
}



/**
 * Ejecuta una consulta SQL y devuelve los resultados como array.
 *
 * @param string $sql Consulta SQL.
 * @return array
 */
function find_by_sql($sql)
{
    global $db;
    $result = $db->query($sql);
    return $db->while_loop($result);
}

/**
 * Obtiene todos los registros activos de una tabla
 * filtrados por la cuenta actual en sesión.
 *
 * @param string $table Nombre de la tabla.
 * @return array|null
 */
function find_all_with_account($table)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $table   = $db->escape($table);
    $account = $db->escape($_SESSION['account']);

    return find_by_sql(
        "SELECT * FROM {$table} WHERE active = '1' AND account = {$account}"
    );
}

function find_product_img($product_id)
{
    $product_id = (int) $product_id;

    $sql = "SELECT image 
            FROM product_images 
            WHERE product_id = {$product_id}
            order by order_image asc";

    return find_by_sql($sql);
}

function getThumbnail($imagePath) {

    // Get filename without extension
    $fileName = pathinfo($imagePath, PATHINFO_FILENAME);

    // Build thumbnail path (force .jpg)
    $thumbnailPath = 'uploads/products/thumbnails/' . $fileName . '.jpg';

    // Fallback: if thumbnail doesn't exist, return original
    if (!file_exists($thumbnailPath)) {
        return $imagePath;
    }

    return $thumbnailPath;
}

/**
 * Busca un registro activo por ID.
 *
 * @param string $table Nombre de la tabla.
 * @param int $id ID del registro.
 * @return array|null
 */
function find_by_id($table, $id)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $id    = (int)$id;
    $table = $db->escape($table);

    $sql = $db->query(
        "SELECT * FROM {$table} 
         WHERE id='{$db->escape($id)}' AND active = 1 
         LIMIT 1"
    );

    return $db->fetch_assoc($sql) ?: null;
}

/**
 * Busca un registro por DNI y estado.
 *
 * @param string $table
 * @param int $dni
 * @param int $active
 * @return array|null
 */
function find_by_dni($table, $dni, $active)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $dni    = (int)$dni;
    $active = (int)$active;

    $table = $db->escape($table);

    $sql = "
        SELECT * FROM {$table}
        WHERE dni = '{$dni}'
        AND active = '{$active}'
        LIMIT 1
    ";

    $query = $db->query($sql);
    return $db->fetch_assoc($query) ?: null;
}

/**
 * Elimina físicamente un registro por ID.
 *
 * @param string $table
 * @param int $id
 * @return bool
 */
function delete_by_id($table, $id)
{
    global $db;

    if (!tableExists($table)) {
        return false;
    }

    $sql = "
        DELETE FROM {$db->escape($table)}
        WHERE id = {$db->escape($id)}
        LIMIT 1
    ";

    $db->query($sql);
    return ($db->affected_rows() === 1);
}

/**
 * Eliminación lógica de un registro (active = 0).
 *
 * @param string $table
 * @param int $id
 * @return bool
 */
function delete_by_id_status($table, $id)
{
    global $db;

    if (!tableExists($table)) {
        return false;
    }

    $sql = "
        UPDATE {$db->escape($table)}
        SET active = 0
        WHERE id = {$db->escape($id)}
        LIMIT 1
    ";

    $db->query($sql);
    return ($db->affected_rows() === 1);
}

/**
 * Verifica si una tabla existe en la base de datos.
 *
 * @param string $table
 * @return bool
 */
function tableExists($table)
{
    global $db;

    $sql = "
        SHOW TABLES FROM " . DB_NAME . "
        LIKE '" . $db->escape($table) . "'
    ";

    $result = $db->query($sql);
    return ($result && $db->num_rows($result) > 0);
}

/* =====================================================
 * USUARIOS Y AUTENTICACIÓN
 * ===================================================== */

/**
 * Autentica un usuario para una cuenta específica.
 *
 * @param string $username
 * @param string $password
 * @param string $account
 * @return array|bool
 */
function authenticate($username = '', $password = '', $account = '')
{
    global $db;

    $username = $db->escape($username);
    $password = $db->escape($password);
    $account  = $db->escape($account);

    $sql = "
        SELECT u.id, u.username, u.password, u.user_level,
               ua.account_id, a.name as account_name, a.image as account_image
        FROM users u
        LEFT JOIN user_accounts ua ON ua.user_id = u.id
        LEFT JOIN accounts a ON ua.account_id = a.id
        WHERE u.username = '{$username}'
        AND ua.account_id = '{$account}'
    ";

    $result = $db->query($sql);

    if ($db->num_rows($result)) {
        $user = $db->fetch_assoc($result);
        return (sha1($password) === $user['password']) ? $user : false;
    }

    return false;
}

/**
 * Devuelve el usuario actualmente logueado.
 *
 * @return array|null
 */
function current_user()
{
    static $current_user;
    global $db;

    if (!$current_user && isset($_SESSION['user_id'])) {
        $current_user = find_by_id('users', (int)$_SESSION['user_id']);
    }

    return $current_user;
}

/**
 * Obtiene todos los usuarios activos con su grupo.
 *
 * @return array
 */
function find_all_user()
{
    $sql = "
        SELECT u.id, u.name, u.username, u.user_level,
               u.status, u.last_login,
               g.group_name
        FROM users u
        LEFT JOIN user_groups g
            ON g.group_level = u.user_level
        WHERE u.active = 1 and u.id <> 1
        ORDER BY u.name ASC
    ";

    return find_by_sql($sql);
}

/**
 * Actualiza la fecha del último login.
 *
 * @param int $user_id
 * @return bool
 */
function updateLastLogIn($user_id)
{
    global $db;

    $date = make_date();

    $sql = "
        UPDATE users
        SET last_login = '{$date}'
        WHERE id = '{$user_id}'
        LIMIT 1
    ";

    $db->query($sql);
    return ($db->affected_rows() === 1);
}

/**
 * Verifica si un nombre de grupo ya existe.
 *
 * @param string $val
 * @return bool
 */
function find_by_groupName($val)
{
    global $db;

    $sql = "
        SELECT group_name
        FROM user_groups
        WHERE group_name = '{$db->escape($val)}'
        AND active = 1
        LIMIT 1
    ";

    $result = $db->query($sql);
    return ($db->num_rows($result) === 0);
}

/**
 * Obtiene un grupo por nivel.
 *
 * @param int $level
 * @return array|null
 */
function find_by_groupLevel($level)
{
    global $db;

    $sql = "
        SELECT *
        FROM user_groups
        WHERE group_level = '{$db->escape($level)}'
        AND active = 1
        LIMIT 1
    ";

    return $db->fetch_assoc($db->query($sql));
}

/**
 * Verifica permisos de acceso por nivel de usuario.
 *
 * @param int $require_level
 */
function page_require_level($require_level)
{
    global $session;

    $current_user = current_user();

    if (!$session->isUserLoggedIn(true)) {
        $session->msg('d', 'Por favor, inicia sesión.');
        redirect('index.php');
    }

    $login_level = find_by_groupLevel($current_user['user_level']);

    if (!$login_level || $login_level['group_status'] === '0') {
        $session->msg('d', 'Este nivel de usuario está inactivo.');
        redirect('home.php');
    }

    if ($current_user['user_level'] <= (int)$require_level) {
        return true;
    }

    $session->msg('d', 'No tienes permiso para ver esta página.');
    redirect('home.php');
}

/* =====================================================
 * RELACIONES USUARIO / CUENTAS
 * ===================================================== */

/**
 * Obtiene todas las cuentas asociadas a un usuario.
 *
 * @param int $id
 * @return array
 */
function find_user_accounts($id)
{
    global $db;

    $sql = "
        SELECT ua.id AS id_ua, ua.account_id, ua.date,
               a.id AS id_account, a.address, a.phone,
               a.image, a.name
        FROM user_accounts ua
        LEFT JOIN accounts a ON ua.account_id = a.id
        WHERE ua.user_id = {$db->escape($id)}
    ";

    return find_by_sql($sql);
}

/**
 * Verifica si un usuario ya tiene asociada una cuenta.
 *
 * @param int $id
 * @param int $account
 * @return array
 */
function find_user_accounts_for_add($id, $account)
{
    global $db;

    $sql = "
        SELECT *
        FROM user_accounts ua
        LEFT JOIN accounts a ON ua.account_id = a.id
        WHERE ua.user_id = {$db->escape($id)}
        AND account_id = {$db->escape($account)}
    ";

    return find_by_sql($sql);
}

/**
 * Busca un registro activo por ID y por local (account).
 *
 * Verifica que el registro:
 * - Exista en la tabla indicada.
 * - Pertenezca al local especificado.
 * - Esté activo (active = 1).
 *
 * @param string $table Nombre de la tabla.
 * @param int    $id    ID del registro.
 * @param int    $acc   ID del local (account).
 *
 * @return array|null Retorna el registro como arreglo asociativo si existe,
 *                    o null si no se encuentra.
 */
function find_by_id_account($table, $id, $acc)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $id  = (int)$id;
    $acc = (int)$acc;

    $sql  = "SELECT * FROM {$db->escape($table)} ";
    $sql .= "WHERE id = {$id} ";
    $sql .= "AND account = {$acc} ";
    $sql .= "AND active = 1 ";
    $sql .= "LIMIT 1";

    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        return $db->fetch_assoc($result);
    }

    return null;
}

/**
 * Busca un registro activo por nombre y por local (account).
 *
 * Verifica que el registro:
 * - Exista en la tabla indicada.
 * - Tenga el nombre especificado.
 * - Pertenezca al local indicado.
 * - Esté activo (active = 1).
 *
 * @param string $table Nombre de la tabla.
 * @param string $name  Nombre del registro (ej. producto).
 * @param int    $acc   ID del local (account).
 *
 * @return array|null Retorna el registro como arreglo asociativo si existe,
 *                    o null si no se encuentra.
 */
function find_by_name_account($table, $name, $acc)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    // Sanitización segura
    $table = $db->escape($table);
    $name  = $db->escape($name);
    $acc   = (int)$acc;

    $sql  = "SELECT * FROM {$table} ";
    $sql .= "WHERE name = '{$name}' ";
    $sql .= "AND account = {$acc} ";
    $sql .= "AND active = 1 ";
    $sql .= "LIMIT 1";

    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        return $db->fetch_assoc($result);
    }

    return null;
}

/**
 * Obtiene todos los empleados activos junto con el nombre de la sede (account).
 *
 * Esta función consulta la tabla de empleados y realiza un LEFT JOIN con la
 * tabla de cuentas (`accounts`) para obtener el nombre de la sede asociada
 * a cada empleado.
 *
 * Solo retorna empleados que tengan el campo `active = 1`.
 *
 * Ejemplo de uso:
 * $employees = find_all_employees('employees');
 *
 * @param string $table Nombre de la tabla de empleados.
 *
 * @return array|null
 * Retorna un arreglo con todos los empleados activos y su sede asociada.
 * Retorna null si la tabla no existe.
 */
function find_all_employees($table)
{
    global $db;

    if (!tableExists($table)) {
        return [];
    }

    $table = $db->escape($table);

    $sql = "SELECT 
                e.*, 
                a.name AS account_name
            FROM {$table} AS e
            LEFT JOIN accounts AS a 
                ON a.id = e.account
            WHERE e.active = 1";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene todas las asistencias registradas en un mes y año específicos.
 *
 * Esta función consulta la tabla `employees_attendance` y obtiene los registros
 * de asistencia de los empleados para el mes y año indicados. Además, realiza
 * un JOIN con las tablas `employees` y `accounts` para recuperar el nombre del
 * empleado y el nombre de la cuenta asociada al registro de asistencia.
 *
 * Los resultados se ordenan de forma descendente según la fecha de registro
 * (`check_in`), mostrando primero los registros más recientes.
 *
 * Tablas involucradas:
 * - employees_attendance (ea): registros de asistencia.
 * - employees (e): información del empleado.
 * - accounts (a): cuentas o áreas asociadas a la asistencia.
 *
 * Campos retornados:
 * - id (int): identificador del registro de asistencia.
 * - check_in (datetime): fecha y hora de ingreso del empleado.
 * - status (string): estado de la asistencia.
 * - account (int): identificador de la cuenta asociada.
 * - name (string): nombre del empleado.
 * - account_name (string): nombre de la cuenta asociada.
 * - opening_time (datetime): hora de apertura registrada.
 *
 * @param int|string $month Mes a consultar (1–12).
 * @param int|string $year  Año a consultar (ej. 2024).
 *
 * @return array Lista de asistencias del mes solicitado en formato de arreglo asociativo.
 */
function get_month_attendances($month, $year) {
    global $db;

    // Convertir a enteros para evitar inyección SQL y asegurar formato correcto
    $month = (int) $month;
    $year  = (int) $year;

    $sql = "
        SELECT 
            ea.id,
            ea.check_in,
            ea.status,
            ea.account,
            ea.opening_time,
            ea.lunch_time_departure,
            ea.lunch_time_entrance,
            e.name,
            a.name AS account_name
        FROM employees_attendance ea
        INNER JOIN employees e ON e.id = ea.employee_id
        INNER JOIN accounts a ON ea.account = a.id
        WHERE MONTH(ea.check_in) = {$month}
          AND YEAR(ea.check_in) = {$year}
        ORDER BY ea.check_in DESC
    ";

    return find_by_sql($sql);
}

/**
 * Obtiene el resumen de asistencias de un empleado para calcular el bono de presentismo.
 *
 * Esta función analiza los registros de asistencia (`employees_attendance`) de un empleado
 * en un mes y año determinados dentro de una cuenta específica. Con base en la hora de
 * apertura definida en la tabla `accounts`, se determina si el empleado:
 *
 * 1. Llegó dentro del tiempo de tolerancia (máximo 10 minutos después de la hora de apertura).
 * 2. Llegó tarde (más de 10 minutos después de la hora de apertura).
 *
 * Reglas de cálculo del bono:
 * - El empleado **pierde el bono** si tiene al menos una llegada tarde.
 * - El empleado **pierde el bono** si usa la tolerancia más de 3 veces.
 * - Si no hay llegadas tarde y usa la tolerancia máximo 3 veces, **conserva el bono**.
 *
 * Si la cuenta no tiene definida una `opening_time`, se utilizará por defecto **08:00:00**.
 *
 * Tablas involucradas:
 * - employees_attendance: registros de asistencia de empleados.
 * - accounts: define la hora de apertura para evaluar puntualidad.
 *
 * @param int|string $employee_id ID del empleado.
 * @param int|string $month Mes a evaluar (1–12).
 * @param int|string $year Año a evaluar (ej. 2024).
 * @param int|string $account_id ID de la cuenta donde se registra la asistencia.
 *
 * @return array{
 *     used_tolerance:int,
 *     has_bonus:bool
 * }
 * Retorna:
 * - used_tolerance: número de veces que el empleado llegó dentro del rango de tolerancia.
 * - has_bonus: indica si el empleado mantiene el bono de presentismo.
 */
function get_attendance_summary($employee_id, $month, $year, $account_id) {
    global $db;

    // Sanitizar valores numéricos para evitar inyección SQL
    $employee_id = (int) $employee_id;
    $month       = (int) $month;
    $year        = (int) $year;
    $account_id  = (int) $account_id;

    // === Obtener todos los check-ins del empleado en el mes indicado ===
    $sql = "
        SELECT check_in
        FROM employees_attendance
        WHERE employee_id = {$employee_id}
          AND MONTH(check_in) = {$month}
          AND YEAR(check_in) = {$year}
          AND account = {$account_id}
    ";

    $attendances = find_by_sql($sql);

    // Contadores de evaluación
    $total_lates    = 0;
    $used_tolerance = 0;

    // === Obtener la hora de apertura de la cuenta ===
    $account = find_by_id('accounts', $account_id);

    // Si no existe o no tiene opening_time definida, usar valor por defecto
    $opening_time = (!empty($account['opening_time']))
        ? $account['opening_time']
        : '08:00:00';

    // Convertir hora base a timestamp para comparaciones
    $entry_time = strtotime($opening_time);

    // === Evaluar cada registro de asistencia ===
    foreach ($attendances as $attendance) {

        // Hora exacta de llegada del empleado
        $check_time = strtotime(date('H:i:s', strtotime($attendance['check_in'])));

        // Límite máximo de tolerancia (5 minutos)
        $tolerance_limit = strtotime('+5 minutes', $entry_time);

        if ($check_time > $entry_time && $check_time <= $tolerance_limit) {
            // Llegó dentro de la tolerancia permitida
            $used_tolerance++;
        } elseif ($check_time > $tolerance_limit) {
            // Llegó tarde (fuera de tolerancia)
            $total_lates++;
        }
    }

    // === Determinar si mantiene el bono de presentismo ===
    $has_bonus = ($total_lates === 0 && $used_tolerance <= 3);

    return [
        'used_tolerance' => $used_tolerance,
        'has_bonus'      => $has_bonus
    ];
}

/* =====================================================
 * INVENTARIO Y PRODUCTOS
 * ===================================================== */

/**
 * Obtiene productos activos por cuenta.
 *
 * @param string $table
 * @param int $acc
 * @return array|null
 */
function find_all_for_account($table, $acc)
{
    global $db;

    if (!tableExists($table)) {
        return null;
    }

    $table = $db->escape($table);
    $acc   = $db->escape($acc);

    return find_by_sql(
        "SELECT * FROM {$table} WHERE account = {$acc} AND active = 1"
    );
}

/**
 * Productos con su categoría.
 *
 * @return array
 */
function join_product_table()
{
    global $db;
    $account = $db->escape($_SESSION['account']);

    $sql = "
        SELECT p.id, p.name, p.qty, p.item_ml, p.variation_ml,
               p.sale_price, p.date,
               c.name AS categorie
        FROM products p
        LEFT JOIN categories c ON c.id = p.categorie_id
        WHERE p.active = 1
        AND p.account = {$account}
        ORDER BY p.id ASC
    ";

    return find_by_sql($sql);
}



function join_product_table_by_category($category_id)
{
    global $db;
    $category_id = (int) $category_id;
    $account = $db->escape($_SESSION['account']);

    $sql = "SELECT p.id, p.name, p.qty, p.sale_price, p.categorie_id, p.date, c.name AS categorie
            FROM products p
            LEFT JOIN categories c ON c.id = p.categorie_id
            WHERE p.categorie_id = '{$category_id}'
              AND p.account = '{$account}'
              AND p.active = 1
            ORDER BY p.id DESC";

    return find_by_sql($sql);
}


/**
 * Obtiene todas las categorías de la cuenta actual junto con:
 * - la cantidad de productos activos por categoría
 * - la suma total del stock (qty) de los productos activos
 *
 * La consulta usa LEFT JOIN para que también se muestren categorías
 * que no tengan productos asociados, devolviendo 0 en los totales.
 *
 * Requiere que la tabla products tenga los campos:
 * - categorie_id
 * - account
 * - active
 * - qty
 *
 * Requiere que la sesión tenga definido:
 * - $_SESSION['account']
 *
 * @global object $db Instancia global de la base de datos.
 *
 * @return array Lista de categorías con los campos:
 *               - id
 *               - name
 *               - active
 *               - account
 *               - total_products
 *               - total_stock
 */
function find_all_categories_with_products_summary()
{
    global $db;

    $account = $db->escape($_SESSION['account']);

    $sql = "SELECT 
                c.id,
                c.name,
                c.active,
                c.account,
                COUNT(p.id) AS total_products,
                COALESCE(SUM(p.qty), 0) AS total_stock
            FROM categories c
            LEFT JOIN products p 
                ON p.categorie_id = c.id
                AND p.account = c.account
                AND p.active = 1
            WHERE c.account = '{$account}'
            GROUP BY c.id, c.name, c.active, c.account
            ORDER BY c.name ASC";

    return find_by_sql($sql);
}

/**
 * Inventario por rango de fechas.
 *
 * @param string $start
 * @param string $end
 * @return array
 */
function join_inventory_table($start, $end)
{
    global $db;

    $account = $db->escape($_SESSION['account']);
    $start   = $db->escape($start);
    $end     = $db->escape($end);

    $sql = "
        SELECT i.id, p.name, i.qty, i.date,
               i.user, i.buy_price, i.total, l.name as location_name, i.movement_type
        FROM inventory i
        LEFT JOIN products p ON p.id = i.product_id
        LEFT JOIN locations l on l.id = i.location_id
        WHERE i.date BETWEEN '{$start}' AND '{$end}'
        AND i.account = {$account}
        ORDER BY i.date DESC
    ";

    return find_by_sql($sql);
}

/**
 * Obtiene un listado de productos por sus IDs.
 *
 * Recibe una cadena de IDs separadas por comas, la sanea y consulta
 * los productos pertenecientes a la cuenta activa del usuario.
 *
 * Ejemplo de entrada:
 * "1,2,3"
 *
 * Campos retornados por cada producto:
 * - id
 * - name
 * - quantity
 * - buy_price
 * - sale_price
 * - media_id
 * - date
 * - categorie
 * - image
 *
 * @param string $prod Cadena de IDs de productos separadas por comas.
 *
 * @return array Lista de productos encontrados.
 */
function find_product_in($prod)
{
    global $db;

    $productIds = remove_junk($db->escape($prod));
    $accountId  = (int) $_SESSION['account'];

    $sql = "
        SELECT
            p.id,
            p.name,
            p.qty,
            p.sale_price,
            p.date,
            c.name AS categorie,
            p.categorie_id,
            pi.image
        FROM products p
        LEFT JOIN categories c ON c.id = p.categorie_id
        LEFT JOIN product_images pi ON pi.product_id = p.id
        WHERE p.id IN ({$productIds})
          AND p.account = {$accountId}
        ORDER BY p.id ASC
    ";

    return find_by_sql($sql);
}

/**
 * Obtiene todas las transferencias de inventario realizadas (salidas)
 * por la cuenta actual dentro de un rango de fechas.
 *
 * - Une con la tabla de productos para obtener el nombre.
 * - Une con la tabla de cuentas para obtener el nombre del local receptor.
 *
 * @param string $inicio Fecha inicio (Y-m-d H:i:s)
 * @param string $fin    Fecha fin (Y-m-d H:i:s)
 * @return array
 */
function join_inventory_transfer_out_table($inicio, $fin)
{
    global $db;

    $account = $db->escape($_SESSION['account']);
    $inicio  = $db->escape($inicio);
    $fin     = $db->escape($fin);

    $sql  = "SELECT 
                it.id,
                p.name,
                it.qty,
                it.date,
                it.user,
                it.transfer_type,
                ao.name AS origin_account,
                ad.name AS receiving_account,
                lo.name AS from_location,
                ld.name AS to_location
            FROM inventory_transfer AS it
            LEFT JOIN products AS p 
                ON p.id = it.product_id_origin
            LEFT JOIN accounts AS ao 
                ON ao.id = it.account
            LEFT JOIN accounts AS ad 
                ON ad.id = it.receiving_account_id
            LEFT JOIN locations AS lo 
                ON lo.id = it.from_location_id
            LEFT JOIN locations AS ld 
                ON ld.id = it.to_location_id
            WHERE it.date BETWEEN '{$inicio}' AND '{$fin}'
              AND it.account = {$account}
            ORDER BY it.date DESC";

    return find_by_sql($sql);
}

/**
 * Obtiene todas las transferencias de inventario recibidas (entradas)
 * por la cuenta actual dentro de un rango de fechas.
 *
 * - Une con la tabla de productos para obtener el nombre.
 * - Une con la tabla de cuentas para obtener el nombre del local emisor.
 *
 * @param string $inicio Fecha inicio (Y-m-d H:i:s)
 * @param string $fin    Fecha fin (Y-m-d H:i:s)
 * @return array
 */
function join_inventory_transfer_in_table($inicio, $fin)
{
    global $db;

    $account = $db->escape($_SESSION['account']);
    $inicio  = $db->escape($inicio);
    $fin     = $db->escape($fin);

    $sql  = "SELECT 
                it.id,
                p.name,
                it.qty,
                it.date,
                it.user,
                it.transfer_type,
                ao.name AS origin_account,
                ad.name AS receiving_account,
                lo.name AS from_location,
                ld.name AS to_location
            FROM inventory_transfer AS it
            LEFT JOIN products AS p 
                ON p.id = it.product_id_destination
            LEFT JOIN accounts AS ao 
                ON ao.id = it.account
            LEFT JOIN accounts AS ad 
                ON ad.id = it.receiving_account_id
            LEFT JOIN locations AS lo 
                ON lo.id = it.from_location_id
            LEFT JOIN locations AS ld 
                ON ld.id = it.to_location_id
            WHERE it.date BETWEEN '{$inicio}' AND '{$fin}'
              AND it.receiving_account_id = {$account}
            ORDER BY it.date DESC";

    return find_by_sql($sql);
}

/**
 * Obtiene el listado de productos por locación.
 *
 * Realiza un JOIN entre las tablas:
 * - product_location
 * - products
 * - locations
 *
 * Retorna cada registro con:
 * - id del registro
 * - nombre del producto
 * - nombre de la locación
 * - cantidad disponible en esa locación
 *
 * @global object $db Conexión global a la base de datos
 *
 * @return array Lista de productos por locación
 */
function join_product_location_table()
{
    global $db;
    $account = $db->escape($_SESSION['account']);

    $sql = "SELECT pl.id,
                   p.name AS product,
                   l.name AS location,
                   pl.qty
            FROM product_locations pl
            LEFT JOIN products p ON p.id = pl.product_id
            LEFT JOIN locations l ON l.id = pl.location_id
              WHERE pl.account = '{$account}'
            ORDER BY pl.id ASC";

    return find_by_sql($sql);
}

/**
 * Obtiene las locaciones donde existe un producto y su stock
 *
 * @param int $product_id
 * @return array
 */
function find_product_locations($product_id)
{
    global $db;

    $product_id = (int)$product_id;
    $account = $db->escape($_SESSION['account']);

    $sql = "SELECT 
                l.name AS location,
                pl.qty
            FROM product_locations pl
            LEFT JOIN locations l ON l.id = pl.location_id
            WHERE pl.product_id = '{$product_id}'
            AND pl.account = '{$account}'
            ORDER BY l.name ASC";

    return find_by_sql($sql);
}

/* =====================================================
 * MOVIMIENTOS FINANCIEROS
 * ===================================================== */

/**
 * Obtiene todos los movimientos financieros
 * con su cuenta financiera asociada.
 *
 * @return array
 */
function find_all_account_movements_with_financial()
{
    global $db;

    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT m.id, m.movement_type, m.amount,
               m.note, m.related_table, m.related_id,
               m.date,
               f.name AS account_name,
               f.type AS financial_type, m.reference
        FROM account_movements m
        INNER JOIN financial_accounts f
            ON f.id = m.financial_account_id
        WHERE m.account = {$account}
        AND m.active = 1
        ORDER BY m.date DESC
    ";

    return find_by_sql($sql);
}

/* =====================================================
 * MÉTRICAS Y GRÁFICOS – VENTAS
 * ===================================================== */

/* =====================================================
 * DASHBOARD - VENTAS
 * ===================================================== */

/**
 * Obtiene el total de ventas agrupadas por categoría.
 *
 * Se utilizan las tablas:
 * - sales_detail
 * - products
 * - categories
 * - sales
 *
 * Solo considera ventas con estado "Emitida".
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return array Lista de categorías con total vendido
 */
function get_sales_by_category($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT c.name AS category,
               SUM(sd.qty * sd.price) AS total
        FROM sales_detail sd
        JOIN products p ON sd.product_id = p.id
        JOIN categories c ON p.categorie_id = c.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
        GROUP BY c.name
        ORDER BY total DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/**
 * Obtiene los productos más vendidos del periodo.
 *
 * Calcula la suma de cantidades vendidas por producto.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return array Lista con nombre de producto y cantidad vendida
 */
function get_top_selling_products($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT p.name AS product,
               SUM(sd.qty) AS quantity
        FROM sales_detail sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
        GROUP BY p.id
        ORDER BY quantity DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/**
 * Obtiene las ventas agrupadas por tipo de venta.
 *
 * Tipos posibles:
 * - Retail
 * - Delivery
 * - ML
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return array Lista de tipos de venta con su total
 */
function get_delivery_vs_retail_sales($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT sale_type AS type,
               COALESCE(SUM(total),0) AS total
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
        GROUP BY sale_type
        ORDER BY total DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/**
 * Calcula el total de unidades vendidas en el periodo.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return int Total de productos vendidos
 */
function products_sold($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(sd.qty),0) AS total
        FROM sales_detail sd
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
    ";

    return (int)$db->query($sql)->fetch_assoc()['total'];
}


/* =====================================================
 * RENTABILIDAD
 * ===================================================== */

/**
 * Calcula la utilidad total generada por las ventas.
 *
 * Fórmula:
 * (precio venta - costo) * cantidad
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return float Utilidad total
 */
function product_profit($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM((sd.price - sd.cost_price) * sd.qty),0) AS total_profit
        FROM sales_detail sd
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
    ";

    return round($db->query($sql)->fetch_assoc()['total_profit'],2);
}


/**
 * Obtiene los productos más rentables.
 *
 * Calcula:
 * - ventas totales
 * - costo total
 * - utilidad
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return array Lista de productos ordenados por utilidad
 */
function get_product_profitability($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT 
            p.name AS product,
            SUM(sd.qty * sd.price) AS total_sales,
            SUM(sd.qty * sd.cost_price) AS total_cost,
            SUM((sd.price - sd.cost_price) * sd.qty) AS profit
        FROM sales_detail sd
        JOIN products p ON p.id = sd.product_id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
        GROUP BY p.id
        HAVING profit > 0
        ORDER BY profit DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/* =====================================================
 * INVENTARIO
 * ===================================================== */

/**
 * Calcula el valor de inventario por producto.
 *
 * Multiplica cantidad por precio de compra.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return array Productos con mayor inversión
 */
function get_valued_inventory($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT 
            p.name AS product,
            SUM(i.qty * i.buy_price) AS total_value
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        WHERE i.date BETWEEN '{$start}' AND '{$end}'
          AND i.account = '{$account}'
          AND i.active = 1
        GROUP BY p.id
        ORDER BY total_value DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/**
 * Obtiene productos con stock bajo y stock alto.
 *
 * Permite comparar inventario disponible.
 *
 * @param string $start No utilizado
 * @param string $end   No utilizado
 *
 * @return array Lista de productos con su nivel de stock
 */
function get_stock_comparison_products($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT name AS product,
               CASE WHEN qty <= 10 THEN qty ELSE 0 END AS low_stock,
               CASE WHEN qty > 10 THEN qty ELSE 0 END AS high_stock
        FROM products
        WHERE account = '{$account}'
          AND active = 1
        ORDER BY qty ASC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/* =====================================================
 * CLIENTES
 * ===================================================== */

/**
 * Obtiene los clientes con mayor volumen de compras.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 *
 * @return array Lista de clientes con total comprado
 */
function get_top_clients($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT c.name AS client,
               SUM(s.total) AS total_purchases
        FROM sales s
        JOIN clients c ON s.client_id = c.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
        GROUP BY c.id
        ORDER BY total_purchases DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene todos los clientes activos de la cuenta actual junto con:
 * - la cantidad total de ventas válidas por cliente
 * - la suma total del importe de esas ventas
 *
 * Se consideran ventas válidas aquellas que:
 * - pertenecen a la misma cuenta del cliente
 * - están activas (sales.active = 1)
 * - no están canceladas (sales.status <> 'Cancelada')
 *
 * La consulta usa LEFT JOIN para que también se muestren clientes
 * sin ventas, devolviendo 0 en los totales.
 *
 * Requiere que la tabla clients tenga los campos:
 * - id
 * - dni
 * - name
 * - phone
 * - mail
 * - address
 * - date
 * - active
 * - account
 *
 * Requiere que la tabla sales tenga los campos:
 * - client_id
 * - total
 * - status
 * - active
 * - account
 *
 * Requiere que la sesión tenga definido:
 * - $_SESSION['account']
 *
 * @global object $db Instancia global de la base de datos.
 *
 * @return array Lista de clientes con los campos:
 *               - id
 *               - dni
 *               - name
 *               - phone
 *               - mail
 *               - address
 *               - date
 *               - active
 *               - account
 *               - total_sales
 *               - total_sales_amount
 */
function find_all_clients_with_sales_summary()
{
    global $db;

    $account = $db->escape($_SESSION['account']);

    $sql = "SELECT
                c.id,
                c.dni,
                c.name,
                c.phone,
                c.mail,
                c.address,
                c.date,
                c.active,
                c.account,
                COUNT(s.id) AS total_sales,
                COALESCE(SUM(s.total), 0) AS total_sales_amount
            FROM clients c
            LEFT JOIN sales s
                ON s.client_id = c.id
                AND s.account = c.account
                AND s.active = 1
                AND s.status = 'Emitida'
            WHERE c.account = '{$account}'
              AND c.active = 1
            GROUP BY
                c.id,
                c.dni,
                c.name,
                c.phone,
                c.mail,
                c.address,
                c.date,
                c.active,
                c.account
            ORDER BY c.name ASC";

    return find_by_sql($sql);
}


/* =====================================================
 * FINANZAS
 * ===================================================== */

/**
 * Obtiene las cuentas financieras activas del sistema.
 *
 * Devuelve:
 * - nombre de cuenta
 * - saldo actual
 *
 * @param string $start No utilizado
 * @param string $end   No utilizado
 *
 * @return array Lista de cuentas financieras
 */
function get_financial_accounts($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT name AS account,
               balance
        FROM financial_accounts
        WHERE account = '{$account}'
          AND active = 1
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene la cantidad total de artículos ingresados al inventario
 * dentro del rango de fechas para la cuenta actual.
 *
 * Suma la columna `qty` de la tabla `inventory`, considerando
 * únicamente registros activos pertenecientes a la cuenta en sesión.
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return int Total de unidades ingresadas al inventario
 */
function inventory_items_purchased($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(qty),0) AS total
        FROM inventory
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND account = '{$account}'
          AND active = 1
    ";

    return (int)$db->query($sql)->fetch_assoc()['total'];
}

/**
 * Calcula la inversión total en inventario dentro del rango de fechas
 * para la cuenta actual.
 *
 * La inversión se calcula multiplicando:
 * - cantidad (`qty`)
 * - precio de compra (`buy_price`)
 *
 * de cada registro de la tabla `inventory`.
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return float Total invertido en inventario
 */
function inventory_total_investment($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(qty * buy_price),0) AS total
        FROM inventory
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND account = '{$account}'
          AND active = 1
    ";

    return round($db->query($sql)->fetch_assoc()['total'], 2);
}

/**
 * Calcula el ticket promedio de las ventas emitidas
 * dentro del rango de fechas para la cuenta actual.
 *
 * El ticket promedio se obtiene con AVG(total) sobre la tabla `sales`,
 * filtrando únicamente ventas con estado `Emitida`.
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return float Valor promedio por venta emitida
 */
function average_ticket($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(AVG(total),0) AS avg_ticket
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
    ";

    return round($db->query($sql)->fetch_assoc()['avg_ticket'], 2);
}

/**
 * Cuenta la cantidad de clientes distintos con ventas emitidas
 * dentro del rango de fechas para la cuenta actual.
 *
 * Usa COUNT(DISTINCT client_id) sobre la tabla `sales`.
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return int Cantidad de clientes únicos atendidos
 */
function distinct_clients_count($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COUNT(DISTINCT client_id) AS total
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
    ";

    return (int)$db->query($sql)->fetch_assoc()['total'];
}

/**
 * Obtiene la cantidad total de unidades actualmente disponibles
 * en stock para la cuenta actual.
 *
 * Suma la columna `qty` de la tabla `products`,
 * considerando únicamente productos activos.
 *
 * @return int Total de unidades en stock
 */
function total_stock_units()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(qty),0) AS total
        FROM products
        WHERE account = '{$account}'
          AND active = 1
    ";

    return (int)$db->query($sql)->fetch_assoc()['total'];
}

/**
 * Calcula el saldo total acumulado de las cuentas financieras
 * activas de la cuenta actual.
 *
 * Suma la columna `balance` de la tabla `financial_accounts`.
 *
 * @return float Saldo financiero total
 */
function total_financial_balance()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(balance),0) AS total
        FROM financial_accounts
        WHERE account = '{$account}'
          AND active = 1
    ";

    return round($db->query($sql)->fetch_assoc()['total'], 2);
}

/**
 * Obtiene la tendencia de ventas por día dentro del rango indicado
 * para la cuenta actual.
 *
 * Agrupa las ventas emitidas por fecha y suma el total vendido
 * en cada día.
 *
 * Resultado esperado:
 * - day   => fecha agrupada
 * - total => total vendido ese día
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return array Lista de días con total vendido por fecha
 */
function get_sales_trend($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT DATE(date) AS day,
               SUM(total) AS total
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
        GROUP BY DATE(date)
        ORDER BY day ASC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene un resumen de ventas agrupadas por estado
 * dentro del rango de fechas para la cuenta actual.
 *
 * Estados posibles según la base de datos:
 * - En Validación
 * - Emitida
 * - Cancelada
 *
 * Resultado esperado:
 * - status => estado de la venta
 * - total  => cantidad de ventas en ese estado
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return array Lista de estados con cantidad de ventas
 */
function get_sales_status_summary($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT status,
               COUNT(*) AS total
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND account = '{$account}'
        GROUP BY status
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene el stock total agrupado por ubicación
 * para la cuenta actual.
 *
 * Usa la tabla `product_locations` unida con `locations`
 * para sumar la cantidad disponible (`qty`) en cada locación.
 *
 * Resultado esperado:
 * - location  => nombre de la ubicación
 * - total_qty => total de unidades en esa ubicación
 *
 * @return array Lista de ubicaciones con total de stock
 */
function get_stock_by_location()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT l.name AS location,
               SUM(pl.qty) AS total_qty
        FROM product_locations pl
        JOIN locations l ON l.id = pl.location_id
        WHERE pl.account = '{$account}'
        GROUP BY l.id
        ORDER BY total_qty DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Calcula el total facturado en ventas emitidas
 * dentro del rango de fechas para la cuenta actual.
 *
 * Suma la columna `total` de la tabla `sales`
 * considerando únicamente ventas con estado `Emitida`.
 *
 * @param string $start Fecha inicial del rango (Y-m-d H:i:s)
 * @param string $end   Fecha final del rango (Y-m-d H:i:s)
 *
 * @return float Total facturado
 */
function total_sales_amount($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT COALESCE(SUM(total),0) AS total
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
    ";

    return round($db->query($sql)->fetch_assoc()['total'], 2);
}

/**
 * Obtiene ventas, utilidad y margen porcentual por canal de venta.
 *
 * Margen % = (utilidad / ventas) * 100
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_margin_by_sale_type($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            s.sale_type AS type,
            COALESCE(SUM(sd.qty * sd.price), 0) AS total_sales,
            COALESCE(SUM((sd.price - sd.cost_price) * sd.qty), 0) AS total_profit,
            CASE
                WHEN COALESCE(SUM(sd.qty * sd.price), 0) > 0
                THEN ROUND(
                    (
                        COALESCE(SUM((sd.price - sd.cost_price) * sd.qty), 0)
                        / COALESCE(SUM(sd.qty * sd.price), 0)
                    ) * 100,
                    2
                )
                ELSE 0
            END AS margin_percent
        FROM sales s
        INNER JOIN sales_detail sd ON sd.sale_id = s.id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
          AND s.active = 1
          AND sd.active = 1
        GROUP BY s.sale_type
        ORDER BY FIELD(s.sale_type, 'Retail', 'Delivery', 'ML')
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene la inversión en inventario agrupada por categoría.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_inventory_investment_by_category($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            c.name AS category,
            COALESCE(SUM(i.qty * i.buy_price), 0) AS total_investment
        FROM inventory i
        INNER JOIN products p ON p.id = i.product_id
        INNER JOIN categories c ON c.id = p.categorie_id
        WHERE i.date BETWEEN '{$start}' AND '{$end}'
          AND i.account = '{$account}'
          AND i.active = 1
        GROUP BY c.id, c.name
        ORDER BY total_investment DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene comparación entre stock actual y cantidad vendida por producto.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_stock_vs_sales_products($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            p.name AS product,
            COALESCE(SUM(sd.qty), 0) AS sold_qty,
            p.qty AS current_stock
        FROM products p
        LEFT JOIN sales_detail sd
            ON sd.product_id = p.id
        LEFT JOIN sales s
            ON s.id = sd.sale_id
           AND s.date BETWEEN '{$start}' AND '{$end}'
           AND s.status = 'Emitida'
           AND s.account = '{$account}'
        WHERE p.account = '{$account}'
          AND p.active = 1
        GROUP BY p.id, p.name, p.qty
        HAVING sold_qty > 0 OR current_stock > 0
        ORDER BY sold_qty DESC, current_stock DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene el saldo financiero agrupado por tipo de cuenta.
 *
 * @return array
 */
function get_financial_balance_by_type()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            type,
            COALESCE(SUM(balance), 0) AS total_balance
        FROM financial_accounts
        WHERE account = '{$account}'
          AND active = 1
        GROUP BY type
        ORDER BY total_balance DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene la facturación de ventas ML vs no ML.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_ml_vs_non_ml_sales($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            CASE
                WHEN sale_type = 'ML' THEN 'ML'
                ELSE 'No ML'
            END AS channel,
            COALESCE(SUM(total), 0) AS total_sales
        FROM sales
        WHERE date BETWEEN '{$start}' AND '{$end}'
          AND status = 'Emitida'
          AND account = '{$account}'
          AND active = 1
        GROUP BY channel
        ORDER BY total_sales DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}



/**
 * Obtiene productos con stock actual pero sin ventas emitidas en el período.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_products_without_movement($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            p.name AS product,
            p.qty AS current_stock
        FROM products p
        LEFT JOIN sales_detail sd
            ON sd.product_id = p.id
        LEFT JOIN sales s
            ON s.id = sd.sale_id
           AND s.date BETWEEN '{$start}' AND '{$end}'
           AND s.status = 'Emitida'
           AND s.account = '{$account}'
        WHERE p.account = '{$account}'
          AND p.active = 1
          AND p.qty > 0
        GROUP BY p.id, p.name, p.qty
        HAVING COALESCE(SUM(sd.qty), 0) = 0
        ORDER BY p.qty DESC, p.name ASC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene utilidad por categoría en el rango indicado.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_profit_by_category($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            c.name AS category,
            COALESCE(SUM((sd.price - sd.cost_price) * sd.qty), 0) AS profit
        FROM sales_detail sd
        INNER JOIN sales s ON s.id = sd.sale_id
        INNER JOIN products p ON p.id = sd.product_id
        INNER JOIN categories c ON c.id = p.categorie_id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
          AND s.active = 1
          AND sd.active = 1
        GROUP BY c.id, c.name
        ORDER BY profit DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene índice de rotación por producto.
 * Fórmula base: vendidos / stock actual.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_inventory_rotation_products($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            p.name AS product,
            COALESCE(SUM(sd.qty), 0) AS sold_qty,
            p.qty AS current_stock,
            CASE
                WHEN p.qty > 0 THEN ROUND(COALESCE(SUM(sd.qty), 0) / p.qty, 2)
                ELSE 0
            END AS rotation_index
        FROM products p
        LEFT JOIN sales_detail sd ON sd.product_id = p.id AND sd.active = 1
        LEFT JOIN sales s ON s.id = sd.sale_id
            AND s.date BETWEEN '{$start}' AND '{$end}'
            AND s.status = 'Emitida'
            AND s.account = '{$account}'
            AND s.active = 1
        WHERE p.account = '{$account}'
          AND p.active = 1
        GROUP BY p.id, p.name, p.qty
        HAVING sold_qty > 0 OR current_stock > 0
        ORDER BY rotation_index DESC, sold_qty DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene concentración de ventas por cliente para análisis Pareto.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_clients_concentration($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            c.name AS client,
            COALESCE(SUM(s.total), 0) AS total_purchases
        FROM sales s
        INNER JOIN clients c ON c.id = s.client_id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
          AND s.active = 1
        GROUP BY c.id, c.name
        ORDER BY total_purchases DESC
        LIMIT 10
    ";

    $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

    $grandTotal = array_sum(array_map(fn($r) => (float)$r['total_purchases'], $rows));
    $runningTotal = 0;

    foreach ($rows as &$row) {
        $value = (float)$row['total_purchases'];
        $runningTotal += $value;

        $row['percent'] = $grandTotal > 0 ? round(($value / $grandTotal) * 100, 2) : 0;
        $row['accum_percent'] = $grandTotal > 0 ? round(($runningTotal / $grandTotal) * 100, 2) : 0;
    }
    unset($row);

    return $rows;
}


/**
 * Obtiene productos con mayor margen porcentual.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_top_margin_products($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            p.name AS product,
            COALESCE(SUM(sd.qty * sd.price), 0) AS total_sales,
            COALESCE(SUM((sd.price - sd.cost_price) * sd.qty), 0) AS total_profit,
            CASE
                WHEN COALESCE(SUM(sd.qty * sd.price), 0) > 0
                THEN ROUND(
                    (COALESCE(SUM((sd.price - sd.cost_price) * sd.qty), 0) / COALESCE(SUM(sd.qty * sd.price), 0)) * 100,
                    2
                )
                ELSE 0
            END AS margin_percent
        FROM sales_detail sd
        INNER JOIN sales s ON s.id = sd.sale_id
        INNER JOIN products p ON p.id = sd.product_id
        WHERE s.date BETWEEN '{$start}' AND '{$end}'
          AND s.status = 'Emitida'
          AND s.account = '{$account}'
          AND s.active = 1
          AND sd.active = 1
        GROUP BY p.id, p.name
        HAVING total_sales > 0
        ORDER BY margin_percent DESC, total_profit DESC
        LIMIT 10
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene distribución del stock actual agrupado por categoría.
 *
 * @return array
 */
function get_stock_distribution_by_category()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            c.name AS category,
            COALESCE(SUM(p.qty), 0) AS total_stock
        FROM products p
        INNER JOIN categories c ON c.id = p.categorie_id
        WHERE p.account = '{$account}'
          AND p.active = 1
        GROUP BY c.id, c.name
        ORDER BY total_stock DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene comparativo de unidades vendidas vs compradas por categoría.
 *
 * @param string $start Fecha inicial
 * @param string $end   Fecha final
 * @return array
 */
function get_sales_vs_purchases_summary($start, $end)
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            base.category,
            COALESCE(v.sold_qty, 0) AS sold_qty,
            COALESCE(i.purchased_qty, 0) AS purchased_qty
        FROM (
            SELECT c.id, c.name AS category
            FROM categories c
        ) base
        LEFT JOIN (
            SELECT
                p.categorie_id,
                SUM(sd.qty) AS sold_qty
            FROM sales_detail sd
            INNER JOIN sales s ON s.id = sd.sale_id
            INNER JOIN products p ON p.id = sd.product_id
            WHERE s.date BETWEEN '{$start}' AND '{$end}'
              AND s.status = 'Emitida'
              AND s.account = '{$account}'
              AND s.active = 1
              AND sd.active = 1
            GROUP BY p.categorie_id
        ) v ON v.categorie_id = base.id
        LEFT JOIN (
            SELECT
                p.categorie_id,
                SUM(i.qty) AS purchased_qty
            FROM inventory i
            INNER JOIN products p ON p.id = i.product_id
            WHERE i.date BETWEEN '{$start}' AND '{$end}'
              AND i.account = '{$account}'
              AND i.active = 1
            GROUP BY p.categorie_id
        ) i ON i.categorie_id = base.id
        WHERE COALESCE(v.sold_qty, 0) > 0 OR COALESCE(i.purchased_qty, 0) > 0
        ORDER BY sold_qty DESC, purchased_qty DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene buckets de antigüedad del inventario actual.
 * Usa la fecha más reciente de ingreso registrada por producto.
 *
 * @return array
 */
function get_inventory_age_buckets()
{
    global $db;
    $account = (int)$_SESSION['account'];

    $sql = "
        SELECT
            bucket,
            SUM(current_stock) AS total_stock
        FROM (
            SELECT
                p.id,
                p.qty AS current_stock,
                CASE
                    WHEN DATEDIFF(CURDATE(), DATE(MAX(i.date))) <= 30 THEN '0-30 días'
                    WHEN DATEDIFF(CURDATE(), DATE(MAX(i.date))) BETWEEN 31 AND 60 THEN '31-60 días'
                    WHEN DATEDIFF(CURDATE(), DATE(MAX(i.date))) BETWEEN 61 AND 90 THEN '61-90 días'
                    ELSE '90+ días'
                END AS bucket
            FROM products p
            LEFT JOIN inventory i
                ON i.product_id = p.id
               AND i.account = '{$account}'
               AND i.active = 1
            WHERE p.account = '{$account}'
              AND p.active = 1
              AND p.qty > 0
            GROUP BY p.id, p.qty
        ) t
        GROUP BY bucket
        ORDER BY FIELD(bucket, '0-30 días', '31-60 días', '61-90 días', '90+ días')
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

//FIN DE METRICAS Y GRÁFICOS – VENTAS



/**
 * Obtiene las ventas totales agrupadas por categoría dentro de un rango de fechas
 * filtrando por la cuenta activa.
 *
 * Consulta las tablas sales_detail, products, categories y sales para calcular
 * el total vendido por categoría considerando únicamente ventas con estado "Emitida"
 * pertenecientes a la cuenta actual.
 *
 * @param string $start Fecha inicial del rango (YYYY-MM-DD).
 * @param string $end   Fecha final del rango (YYYY-MM-DD).
 *
 * @return array Lista asociativa con:
 *               - category (string) Nombre de la categoría
 *               - total (float) Total vendido en esa categoría
 */



function find_meli_event_details($eventId)
{
    global $db;

    $eventId   = (int)$eventId;

    $sql  = "SELECT * ";
    $sql .= "FROM meli_event_detail ";
    $sql .= "WHERE event_id = {$eventId} ";
    $sql .= "ORDER BY date ASC";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene las ventas registradas en el sistema
 * filtradas por cuenta y rango de fechas.
 *
 * Incluye JOIN con la tabla clients para
 * obtener información del cliente asociada
 * a cada venta.
 *
 * @param string $start     Fecha inicio (Y-m-d H:i:s)
 * @param string $end       Fecha fin (Y-m-d H:i:s)
 * @param int    $accountId ID de la cuenta
 *
 * @return array Lista de ventas con datos de cliente
 */
function find_sales($start, $end) {
    global $db;
    $account = (int)$_SESSION['account'];
    $sql = "
        SELECT 
            s.*,
            c.name   AS client_name
        FROM sales s
        LEFT JOIN clients c ON c.id = s.client_id
        WHERE s.account = {$account}
          AND s.date BETWEEN '{$start}' AND '{$end}'
        ORDER BY s.date DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene el detalle de una venta (sales_detail) por ID de venta.
 *
 * Devuelve los items vendidos junto con la información básica del producto,
 * usando JOIN contra la tabla products.
 *
 * @param int $sale_id  ID de la venta (sales.id)
 *
 * @return array        Lista de registros de sales_detail asociados a la venta
 */
function find_sales_detail(int $sale_id): array
{
    global $db;

    $sale_id = (int)$sale_id;

    $sql = "
        SELECT
            sd.id,
            sd.qty,
            sd.price,
            sd.total,
            sd.status,
            sd.date,
            sd.item_ml,
            sd.variation_ml,
            p.name AS product_name
        FROM sales_detail sd
        LEFT JOIN products p ON p.id = sd.product_id
        WHERE sd.sale_id = {$sale_id}
        ORDER BY sd.id ASC
    ";

    return $db->query(sql: $sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene todas las ventas registradas en el sistema
 * filtradas por cuenta y por cliente.
 *
 * Incluye JOIN con la tabla clients para
 * obtener información del cliente asociada
 * a cada venta.
 *
 * @param int $client_id ID del cliente
 *
 * @return array Lista de ventas con datos de cliente
 */
function find_sales_by_client($client_id) {
    global $db;
    $account = (int) $_SESSION['account'];
    $client_id = (int) $client_id;

    $sql = "
        SELECT 
            s.*,
            c.name AS client_name
        FROM sales s
        LEFT JOIN clients c ON c.id = s.client_id
        WHERE s.account = {$account}
          AND s.client_id = {$client_id}
        ORDER BY s.date DESC
    ";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}


/* =====================================================
 * VENTAS - CONSULTAS PARA EMITIR VENTA EN VALIDACIÓN
 * ===================================================== */

/**
 * Obtiene una venta por ID para el módulo de emisión,
 * junto con datos básicos del cliente.
 *
 * @param int $sale_id
 * @param int $account
 * @return array|null
 */
function find_sale_for_emit($sale_id, $account)
{
    global $db;

    $sale_id = (int)$sale_id;
    $account = (int)$account;

    $sql = "
        SELECT 
            s.*,
            c.name AS client_name,
            c.dni AS client_dni,
            c.mail AS client_mail,
            c.phone AS client_phone,
            c.address AS client_address,
            c.note AS client_note

        FROM sales s
        LEFT JOIN clients c ON c.id = s.client_id
        WHERE s.id = '{$sale_id}'
          AND s.account = '{$account}'
          AND s.active = 1
        LIMIT 1
    ";

    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        return $db->fetch_assoc($result);
    }

    return null;
}

/**
 * Obtiene los productos actuales de una venta
 * para precargarlos en emit_sale.php
 *
 * Usa LEFT JOIN para no perder registros si cambió
 * alguna relación secundaria.
 *
 * @param int $sale_id
 * @param int $account
 * @return array
 */
function find_sale_products_for_emit($sale_id, $account)
{
    global $db;

    $sale_id = (int)$sale_id;
    $account = (int)$account;

    $sql = "
        SELECT 
            sd.id,
            sd.sale_id,
            sd.product_id,
            sd.location_product_id,
            sd.dispatch_type,
            sd.qty,
            sd.price,
            sd.cost_price,
            sd.note,
            sd.status,
            sd.discount_percent,
            sd.discounted_price,
            p.name AS product_name,
            pl.location_id,
            pl.qty AS current_stock,
            l.name AS location_name,
            l.location_type
        FROM sales_detail sd
        LEFT JOIN products p ON p.id = sd.product_id
        LEFT JOIN product_locations pl ON pl.id = sd.location_product_id
        LEFT JOIN locations l ON l.id = pl.location_id
        WHERE sd.sale_id = '{$sale_id}'
          AND sd.account = '{$account}'
        ORDER BY sd.id ASC
    ";

    return find_by_sql($sql);
}

/**
 * Obtiene los pagos actuales de una venta
 * para precargarlos en emit_sale.php
 *
 * Usa LEFT JOIN y no filtra por active para evitar
 * perder pagos si el campo no está gestionado igual.
 *
 * @param int $sale_id
 * @param int $account
 * @return array
 */
function find_sale_payments_for_emit($sale_id, $account)
{
    global $db;

    $sale_id = (int)$sale_id;
    $account = (int)$account;

    $sql = "
        SELECT 
            am.id,
            am.financial_account_id AS account_id,
            fa.name AS account_name,
            am.amount,
            am.reference,
            am.date
        FROM account_movements am
        LEFT JOIN financial_accounts fa ON fa.id = am.financial_account_id
        WHERE am.related_table = 'Ventas'
          AND am.related_id = '{$sale_id}'
          AND am.account = '{$account}'
        ORDER BY am.id ASC
    ";

    return find_by_sql($sql);
}

/**
 * Obtiene una relación producto-ubicación válida
 * para validar y emitir una venta.
 *
 * Esta sí debe ser estricta porque se usa para validar stock
 * y tipo de despacho al momento de emitir.
 *
 * @param int $location_product_id
 * @param int $product_id
 * @param int $account_sender
 * @return array|null
 */
function find_sale_product_location($location_product_id, $product_id, $account_sender)
{
    global $db;

    $location_product_id = (int)$location_product_id;
    $product_id          = (int)$product_id;
    $account_sender      = (int)$account_sender;

    $sql = "
        SELECT 
            pl.id,
            pl.product_id,
            pl.location_id,
            pl.qty,
            pl.account,
            l.name AS location_name,
            l.location_type
        FROM product_locations pl
        INNER JOIN locations l ON l.id = pl.location_id
        WHERE pl.id = '{$location_product_id}'
          AND pl.product_id = '{$product_id}'
          AND pl.account = '{$account_sender}'
          AND pl.active = 1
          AND l.account = '{$account_sender}'
          AND l.active = 1
        LIMIT 1
    ";

    $result = $db->query($sql);

    if ($result && $db->num_rows($result) > 0) {
        return $db->fetch_assoc($result);
    }

    return null;
}


?>