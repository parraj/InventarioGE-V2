<?php
ob_start();
require_once('includes/load.php');
if($session->isUserLoggedIn(true)) { redirect('home.php', false);}
$all_accounts = find_all('accounts');
?>
<?php include_once('layouts/header.php'); ?>

<div class="d-flex justify-content-center align-items-center min-vh-100" style="background: #f1f3f6;">
    <div class="card shadow-lg w-100" style="max-width: 400px; border-radius: 12px;">
        <div class="card-body p-4">

            <div class="text-center mb-3">
                <img src="uploads/empresa.png" class="rounded-circle border border-2"
                     style="width: 90px; height: 90px; border-color: #c7b071;" alt="Logo">
            </div>

            <h4 class="text-center mb-1 fw-bold text-primary">Bienvenido</h4>
            <p class="text-center text-muted mb-4">Inicia sesión para continuar</p>

            <?php echo display_msg($msg); ?>

            <form method="post" action="auth.php" autocomplete="off">
                <div class="mb-3 input-group">
                    <input type="text" class="form-control" name="username" placeholder="Usuario" required>
                </div>
                <div class="mb-4 input-group">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                </div>
                <div class="mb-4 input-group">
                        <select name="account" class="form-control">
                        <?php  foreach ($all_accounts as $account): ?>
                            <option value="<?php echo (int)$account['id'] ?>">
                                <?php echo $account['id']." - ".$account['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary fw-bold shadow-sm">Entrar</button>
                </div>
            </form>

            <div class="text-center">
                <small class="text-muted">© <?php echo date("Y"); ?> JP-System. Todos los derechos reservados.</small>
            </div>

        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>