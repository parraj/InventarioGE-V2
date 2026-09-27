<?php
require_once('includes/load.php');

// Validar que venga un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="text-center p-5">Producto no válido</div>';
    exit;
}

$product_id = (int) $_GET['id'];

// Traer las imágenes del producto
$images = find_product_img($product_id);

if (empty($images)) {
    echo '<div class="text-center p-5">No hay imágenes para este producto.</div>';
    exit;
}

?>

<div class="modal-header">
    <h5 class="modal-title">Imágenes del Producto</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row">
        <?php foreach ($images as $img): ?>
            <div class="col-md-4 mb-3">
                <img src="<?php echo $img['image']; ?>" class="img-fluid rounded"
                     style="width:100%;height:200px;object-fit:contain;background:#f8f9fa;">
            </div>
        <?php endforeach; ?>
    </div>
</div>