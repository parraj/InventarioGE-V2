<?php
$page_title = 'Agregar Producto';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(2);

// Procesar formulario desde modal
if (isset($_POST['add_product'])) {

    $req_fields = ['product-title', 'product-categorie', 'saleing-price'];
    validate_fields($req_fields);

    if (empty($errors)) {

        $name     = remove_junk($db->escape($_POST['product-title']));
        $category = remove_junk($db->escape($_POST['product-categorie']));
        $price    = remove_junk($db->escape($_POST['saleing-price']));
        $date     = make_date();
        $account  = $db->escape($_SESSION['account']);

        $sql = "INSERT INTO products (
                    name,
                    sale_price,
                    categorie_id,
                    date,
                    account
                ) VALUES (
                    '{$name}',
                    '{$price}',
                    '{$category}',
                    '{$date}',
                    '{$account}'
                )";

        if ($db->query($sql)) {

            // OBTENER ID DEL PRODUCTO RECIÉN CREADO
            $product_id = $db->insert_id();

            /* =====================================================
               SUBIDA MÚLTIPLE DE IMÁGENES
            ===================================================== */

            if (!empty($_FILES['images']['name'][0])) {

                foreach ($_FILES['images']['name'] as $key => $value) {

                    if ($_FILES['images']['error'][$key] == 0) {

                        // Reconstruimos el array para usar tu función
                        $file = [
                            'name'     => $_FILES['images']['name'][$key],
                            'type'     => $_FILES['images']['type'][$key],
                            'tmp_name' => $_FILES['images']['tmp_name'][$key],
                            'error'    => $_FILES['images']['error'][$key],
                            'size'     => $_FILES['images']['size'][$key]
                        ];

                        //  Reutilizamos tu función
                        $image = upload_image($file, 'uploads/products/');

                        if ($image) {

                            $image = $db->escape($image);

                            $sql_img = "INSERT INTO product_images (
                                            product_id,
                                            image,
                                            account
                                        ) VALUES (
                                            '{$product_id}',
                                            '{$image}',
                                            '{$account}'
                                        )";

                            $db->query($sql_img);
                        }
                    }
                }
            }

            $session->msg('s', 'Producto creado satisfactoriamente.');

        } else {
            $session->msg('d', 'Ocurrió un error, no se pudo crear el producto.');
        }

    } else {
        $session->msg('d', $errors);
    }
}

// Siempre regresar al listado
redirect('product.php', false);