<?php
$page_title = 'Editar producto';
require_once('includes/load.php');
page_require_level(2);

$product = find_by_id('products', (int)$_GET['id']);
$all_categories = find_all('categories');

if (!$product) {
    $session->msg('d', 'ID del producto no encontrado.');
    redirect('product.php', false);
}

/* =====================================================
   FUNCIÓN OBTENER IMÁGENES (ORDENADAS)
===================================================== */
function find_product_images($product_id)
{
    global $db;
    $product_id = (int)$product_id;
    $sql = "SELECT * FROM product_images 
            WHERE product_id = '{$product_id}'
            ORDER BY order_image ASC";
    return find_by_sql($sql);
}

$images = find_product_images($product['id']);

/* =====================================================
   ELIMINAR IMAGEN
===================================================== */
if (isset($_GET['delete_image'])) {

    $image_id = (int)$_GET['delete_image'];
    $img = find_by_id('product_images', $image_id);

    if ($img) {

        $imagePath = $img['image'];

        $fileName = pathinfo($imagePath, PATHINFO_FILENAME);
        $thumbPath = 'uploads/products/thumbnails/' . $fileName . '.jpg';

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }

        $db->query("DELETE FROM product_images WHERE id = '{$image_id}'");
        $session->msg('s', 'Imagen y thumbnail eliminados correctamente.');
    }

    redirect('edit_product.php?id=' . (int)$product['id'], false);
}

/* =====================================================
   ACTUALIZAR PRODUCTO + ORDEN + NUEVAS IMÁGENES
===================================================== */
if (isset($_POST['product'])) {

    $req_fields = ['product-title', 'product-categorie', 'saleing-price'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $p_name = remove_junk($db->escape($_POST['product-title']));
        $p_cat  = (int)$_POST['product-categorie'];
        $p_sale = remove_junk($db->escape($_POST['saleing-price']));
        $account  = $db->escape($_SESSION['account']);

        $sql = "UPDATE products SET
                    name = '{$p_name}',
                    sale_price = '{$p_sale}',
                    categorie_id = '{$p_cat}'
                WHERE id = '{$product['id']}'";

        $db->query($sql);

        /* ==============================
           GUARDAR ORDEN DE IMÁGENES
        ============================== */
        if (!empty($_POST['image_order'])) {

            $orderData = json_decode($_POST['image_order'], true);

            if (is_array($orderData)) {

                foreach ($orderData as $item) {

                    $img_id = (int)$item['id'];
                    $order  = (int)$item['order'];

                    $sql_check = "SELECT id FROM product_images 
                                  WHERE id = '{$img_id}' 
                                  AND product_id = '{$product['id']}' 
                                  LIMIT 1";

                    $result = $db->query($sql_check);

                    if ($db->num_rows($result)) {

                        $sql_update = "UPDATE product_images SET 
                                       order_image = '{$order}'
                                       WHERE id = '{$img_id}'";

                        $db->query($sql_update);
                    }
                }
            }
        }

        /* ==============================
           SUBIR NUEVAS IMÁGENES
        ============================== */
        if (!empty($_FILES['images']['name'][0])) {

            foreach ($_FILES['images']['name'] as $key => $value) {

                if ($_FILES['images']['error'][$key] == 0) {

                    $file = [
                        'name'     => $_FILES['images']['name'][$key],
                        'type'     => $_FILES['images']['type'][$key],
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'error'    => $_FILES['images']['error'][$key],
                        'size'     => $_FILES['images']['size'][$key]
                    ];

                    $image_path = upload_image($file, 'uploads/products/');

                    if ($image_path) {

                        $image_path = $db->escape($image_path);

                        $sql_last = "SELECT MAX(order_image) as last_order 
                                     FROM product_images 
                                     WHERE product_id = '{$product['id']}'";

                        $res = $db->query($sql_last);
                        $row = $db->fetch_assoc($res);
                        $new_order = (int)$row['last_order'] + 1;

                        $sql_img = "INSERT INTO product_images (
                                        product_id,
                                        image,
                                        account,
                                        order_image
                                    ) VALUES (
                                        '{$product['id']}',
                                        '{$image_path}',
                                        '{$account}',
                                        '{$new_order}'
                                    )";

                        $db->query($sql_img);
                    }
                }
            }
        }

        $session->msg('s', 'Producto modificado satisfactoriamente.');
        redirect('product.php?id=' . (int)$product['id'], false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">

        <h4 class="mb-4">
            <i class="bi bi-box-seam me-2"></i>
            Editar Producto
        </h4>

        <form method="POST"
              action="edit_product.php?id=<?php echo (int)$product['id']; ?>"
              enctype="multipart/form-data"
              autocomplete="off">

            <!-- NUEVO -->
            <input type="hidden" name="image_order" id="image_order">

            <div class="mb-3">
                <label class="form-label">Nombre del Producto</label>
                <input type="text"
                       name="product-title"
                       class="form-control"
                       value="<?php echo remove_junk($product['name']); ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="product-categorie"
                        class="form-select"
                        required>

                    <option value="">Selecciona una categoría</option>

                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>"
                            <?php echo ($product['categorie_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo remove_junk($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Precio de venta</label>
                <input type="number"
                       name="saleing-price"
                       class="form-control"
                       value="<?php echo remove_junk($product['sale_price']); ?>"
                       required>
            </div>

            <!-- IMÁGENES -->
            <div class="mb-4">
                <label class="form-label">Imágenes actuales</label>

                <!-- NUEVO ID -->
                <div class="row" id="sortable-images">
                    <?php foreach ($images as $img): ?>
                        <div class="col-md-3 mb-3 sortable-item"
                             data-id="<?php echo (int)$img['id']; ?>">

                            <div class="position-relative">

                                <img src="<?php echo $img['image']; ?>"
                                     class="img-fluid rounded shadow-sm bg-light"
                                     style="height:150px; width:100%; object-fit:contain;">

                                <a class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute"
                                   style="width:28px; height:28px; top:6px; right:6px;"
                                   onclick="delet(<?php echo (int)$product['id']; ?>,<?php echo (int)$img['id']; ?>)">

                                   <i class="bi bi-trash-fill"></i>
                                </a>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Agregar nuevas imágenes</label>
                <input type="file"
                       name="images[]"
                       class="form-control"
                       multiple
                       accept="image/*">
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="product" class="btn btn-primary me-2">
                    Actualizar
                </button>
                <a href="product.php" class="btn btn-danger">
                    Cancelar
                </a>
            </div>

        </form>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<script>
function delet(id, img) {
    Swal.fire({
        icon: "warning",
        title: "¿Estás seguro de eliminar esta imagen?",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#0d6efd",
        cancelButtonColor: "#dc3545",
        width: '350px',
        customClass: {
            title: 'swal-title-sm',
            content: 'swal-content-sm',
            confirmButton: 'swal-btn-sm',
            cancelButton: 'swal-btn-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'edit_product.php?id=' + id+'&delete_image='+img;
        }
    });
}
</script>