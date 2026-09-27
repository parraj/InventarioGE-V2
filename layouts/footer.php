</div>
</div>
</div>
</div>
</div>

<!-- Librerías JS -->
<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sidebarmenu.js"></script>
<script src="assets/js/app.min.js"></script>
<script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="assets/libs/simplebar/dist/simplebar.js"></script>
<script src="assets/js/functions.js"></script>
<script src="https://cdn.tiny.cloud/1/lhio35xqn73nf55x486qi2lyi830vryfuetuim8g1fphnvxy/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.bootstrap5.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.5/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Otros -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Bootstrap Select -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
  $(document).ready(function () {
    $('.selectpicker').selectpicker();
});

  $(window).on('load', function () { $('#loader').fadeOut('slow'); });

new DataTable('#datatable', {
  order: [[0, 'desc']],
  pageLength: 10,
  lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
  layout: {
    topStart: ['pageLength', 'buttons'], // 👈 juntos
    topEnd: 'search',
    bottomStart: 'info',
    bottomEnd: 'paging'
  },
  buttons: [
    { extend: 'copy', text: '<i class="bi bi-clipboard"></i> Copiar', className: 'btn btn-primary btn-sm' },
    { extend: 'excel', text: '<i class="bi bi-file-earmark-spreadsheet"></i> Excel', className: 'btn btn-primary btn-sm' },
    { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-primary btn-sm' },
    { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-primary btn-sm' }
  ]
});

new DataTable('#datatable2', {
  order: [[0, 'desc']],
  pageLength: 10,
  lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
  layout: {
    topStart: ['pageLength', 'buttons'],
    topEnd: 'search',
    bottomStart: 'info',
    bottomEnd: 'paging'
  },
  buttons: [
    { extend: 'copy', text: '<i class="bi bi-clipboard"></i> Copiar', className: 'btn btn-primary btn-sm' },
    { extend: 'excel', text: '<i class="bi bi-file-earmark-spreadsheet"></i> Excel', className: 'btn btn-primary btn-sm' },
    { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-primary btn-sm' },
    { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-primary btn-sm' }
  ]
});
</script>

<!-- Offcanvas de personalización -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="themeSettings">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Personalizar plantilla</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Tema -->
    <div class="mb-3">
      <label class="form-label">Tema</label>
      <select id="themeSelect" class="form-select">
        <option value="light">Claro</option>
        <option value="dark">Oscuro</option>
      </select>
    </div>

    <!-- Color principal -->
    <div class="mb-3">
      <label class="form-label">Color principal</label>
      <select id="primaryColorSelect" class="form-select">
        <option value="#5D87FF">Azul predeterminado</option>
        <option value="#6610f2">Violeta</option>
        <option value="#13DEB9">Verde</option>
        <option value="#dc3545">Rojo</option>
        <option value="#FA896B">Naranja</option>
        <option value="#6c757d">Gris</option>
        <option value="#0dcaf0">Cian</option>
        <option value="#ffc107">Amarillo</option>
      </select>
    </div>

    <!-- Fuente -->
    <div class="mb-3">
      <label class="form-label">Fuente</label>
      <select id="fontSelect" class="form-select">
        <option value='"Plus Jakarta Sans", sans-serif'>Plus Jakarta Sans (moderna)</option>
        <option value='Roboto, sans-serif'>Roboto (moderna)</option>
        <option value='Montserrat, sans-serif'>Montserrat (moderna)</option>
        <option value='Lato, sans-serif'>Lato (moderna)</option>
        <option value='Merriweather, serif'>Merriweather (clásica, serif)</option>
        <option value='Oswald, sans-serif'>Oswald (impactante)</option>
        <option value='Dancing Script, cursive'>Dancing Script (cursiva)</option>
        <option value='Pacifico, cursive'>Pacifico (decorativa)</option>
        <option value='"Times New Roman", serif'>Times New Roman (clásica)</option>
      </select>
    </div>

    <!-- Tamaño de letra -->
    <div class="mb-3">
      <label class="form-label">Tamaño de letra</label>
      <input type="range" id="fontSizeRange" min="12" max="24" value="16" class="form-range">
    </div>
  </div>
</div>

<script>
  const root = document.documentElement;

  // Función para guardar y aplicar cambios
  function saveSetting(key, value) {
    localStorage.setItem(key, value);
    if (key === 'themeFont') root.style.setProperty('--bs-body-font-family', value);
    if (key === 'themeColor') root.style.setProperty('--bs-primary', value);
    if (key === 'themeMode') root.setAttribute('data-bs-theme', value);
    if (key === 'themeFontSize') root.style.setProperty('--bs-body-font-size', value + 'px');
  }

  // Inicializar controles
  const themeSelect = document.getElementById('themeSelect');
  const primaryColorSelect = document.getElementById('primaryColorSelect');
  const fontSelect = document.getElementById('fontSelect');
  const fontSizeRange = document.getElementById('fontSizeRange');

  // Escuchar cambios y guardar
  themeSelect.addEventListener('change', function () { saveSetting('themeMode', this.value); });
  primaryColorSelect.addEventListener('change', function () { saveSetting('themeColor', this.value); });
  fontSelect.addEventListener('change', function () { saveSetting('themeFont', this.value); });
  fontSizeRange.addEventListener('input', function () { saveSetting('themeFontSize', this.value); });

  // Aplicar configuraciones guardadas al cargar
  window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('themeMode')) {
      root.setAttribute('data-bs-theme', localStorage.getItem('themeMode'));
      themeSelect.value = localStorage.getItem('themeMode');
    }
    if (localStorage.getItem('themeColor')) {
      root.style.setProperty('--bs-primary', localStorage.getItem('themeColor'));
      primaryColorSelect.value = localStorage.getItem('themeColor');
    }
    if (localStorage.getItem('themeFont')) {
      root.style.setProperty('--bs-body-font-family', localStorage.getItem('themeFont'));
      fontSelect.value = localStorage.getItem('themeFont');
    }
    if (localStorage.getItem('themeFontSize')) {
      root.style.setProperty('--bs-body-font-size', localStorage.getItem('themeFontSize') + 'px');
      fontSizeRange.value = localStorage.getItem('themeFontSize');
    }
  });


</script>

</body>

</html>

<?php if (isset($db))
  $db->db_disconnect(); ?>