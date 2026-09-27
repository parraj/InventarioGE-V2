<?php
require_once('includes/load.php');
page_require_level(2);

function sanitize_ids($csv)
{
    $ids = explode(',', $csv);
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, fn($id) => $id > 0);
    return array_values(array_unique($ids));
}

/* =========================
   ACTUALIZAR PRODUCTOS
========================= */
if (isset($_POST['update_products'])) {

    $productsCsv = $_POST['products'] ?? '';
    $globalPrice = trim($_POST['global_price'] ?? '');
    $globalCategory = trim($_POST['global_category'] ?? '');
    $items = $_POST['items'] ?? [];

    if ($productsCsv === '') {
        $session->msg('d', 'No se recibieron productos.');
        redirect('product.php', false);
    }

    $productIds = sanitize_ids($productsCsv);

    if (empty($productIds)) {
        $session->msg('d', 'IDs inválidos.');
        redirect('product.php', false);
    }

    if ($globalPrice !== '' && !is_numeric($globalPrice)) {
        $session->msg('d', 'El precio global debe ser numérico.');
        redirect('product.php', false);
    }

    if ($globalCategory !== '' && !is_numeric($globalCategory)) {
        $session->msg('d', 'Categoría inválida.');
        redirect('product.php', false);
    }

    $account = $db->escape($_SESSION['account']);

    /* =========================
       ACTUALIZACIÓN DE DATOS
    ========================= */
    foreach ($productIds as $id) {

        $fields = [];
        $id = (int) $id;

        $item = $items[$id] ?? [];

        /* NOMBRE */
        if (isset($item['name'])) {
            $name = trim($item['name']);
            if ($name !== '') {
                $name = remove_junk($db->escape($name));
                $fields[] = "name = '{$name}'";
            }
        }

        /* PRECIO (prioridad: individual > global) */
        if (!empty($item['price']) && is_numeric($item['price'])) {
            $fields[] = "sale_price = " . (float) $item['price'];
        } elseif ($globalPrice !== '') {
            $fields[] = "sale_price = " . (float) $globalPrice;
        }

        /* CATEGORÍA (prioridad: individual > global) */
        if (!empty($item['category'])) {
            $fields[] = "categorie_id = " . (int) $item['category'];
        } elseif ($globalCategory !== '') {
            $fields[] = "categorie_id = " . (int) $globalCategory;
        }

        if (!empty($fields)) {
            $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = {$id}";
            $db->query($query);
        }
    }

    /* =========================
       SUBIDA GLOBAL DE IMÁGENES
    ========================= */

    /* =========================
       SUBIDA GLOBAL DE IMÁGENES (CORREGIDO)
    ========================= */
    if (!empty($_FILES['images']['name'][0])) {

        foreach ($_FILES['images']['name'] as $key => $value) {

            if ($_FILES['images']['error'][$key] == 0) {

                $originalTmp = $_FILES['images']['tmp_name'][$key];

                foreach ($productIds as $id) {

                    $id = (int) $id;

                    /* Crear copia del archivo temporal */
                    $tempCopy = sys_get_temp_dir() . '/' . uniqid('img_');
                    copy($originalTmp, $tempCopy);

                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tempCopy,
                        'error' => 0,
                        'size' => filesize($tempCopy)
                    ];

                    $image_path = upload_image($file, 'uploads/products/', $id);

                    unlink($tempCopy);

                    if ($image_path) {
                        $image_path = $db->escape($image_path);

                        $sql_img = "INSERT INTO product_images (product_id, image, account)
                            VALUES ('{$id}', '{$image_path}', '{$account}')";

                        $db->query($sql_img);
                    }
                }
            }
        }
    }

    $session->msg('s', 'Productos actualizados correctamente.');
    redirect('product.php', false);
}


/* =========================
   ELIMINAR PRODUCTOS
========================= */ elseif (isset($_GET['delete'])) {

    $productIds = sanitize_ids($_GET['delete'] ?? '');

    if (empty($productIds)) {
        $session->msg('d', 'IDs inválidos.');
        redirect('product.php', false);
    }

    $in = implode(',', $productIds);

    $query = "UPDATE products SET active = 0 WHERE id IN ({$in})";
    $db->query($query);

    $session->msg('s', 'Productos eliminados.');
    redirect('product.php', false);
} else {
    redirect('product.php', false);
}