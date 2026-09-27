/**
 * @fileoverview
 * Gestión del proceso de ventas:
 * - Selección de productos y ubicaciones.
 * - Registro de pagos.
 * - Construcción de resumen de venta.
 * - Confirmación y envío de la venta.
 * - Consulta y despliegue del detalle de ventas en la misma tabla.
 *
 * Nota:
 * Este archivo fue documentado con JSDoc sin alterar la funcionalidad existente.
 */

/* =========================
STATE
========================= */

/**
 * Lista de productos agregados a la venta actual.
 *
 * @type {Array<Object>}
 */
let saleProducts = [];

/**
 * Lista de pagos agregados a la venta actual.
 *
 * @type {Array<Object>}
 */
let salePayments = [];

/* =========================
HELPERS
========================= */

/**
 * Obtiene el tipo de venta seleccionado en el formulario.
 *
 * @returns {string} Tipo de venta actual. Si no existe el campo, retorna 'Retail'.
 */
function getSaleType() {
    const el = document.getElementById('sale_type');
    return el ? el.value : 'Diaria';
}

/**
 * Indica si la venta actual es de tipo Delivery.
 *
 * @returns {boolean} `true` si el tipo de venta es Delivery; en otro caso `false`.
 */
function isDeliverySale() {
    return getSaleType() === 'Mayorista';
}

/**
 * Obtiene el tipo de despacho bloqueado de la venta actual.
 * Se toma del primer producto agregado, para impedir mezcla
 * entre despacho interno y externo.
 *
 * @returns {string|null} Tipo de despacho bloqueado o `null` si aún no hay productos.
 */
function getLockedDispatchType() {
    if (saleProducts.length === 0) return null;
    return saleProducts[0].dispatch_type || null;
}

/**
 * Formatea un valor numérico a 2 decimales.
 *
 * @param {number|string} value Valor a formatear.
 * @returns {string} Valor formateado con 2 decimales.
 */
function formatMoney(value) {
    const number = parseFloat(value || 0);
    return number.toFixed(2);
}

/**
 * Escapa texto para evitar inyección HTML al renderizar en el DOM.
 *
 * @param {string|null|undefined} text Texto a escapar.
 * @returns {string} Texto escapado de forma segura.
 */
function escapeHtml(text) { 
    const div = document.createElement('div');
    div.textContent = text == null ? '' : text;
    return div.innerHTML;
}

/**
 * Calcula cuántas unidades de un location product ya están reservadas
 * dentro de la venta actual.
 *
 * @param {number|string} locationProductId ID de la relación producto-ubicación.
 * @returns {number} Cantidad reservada actualmente.
 */
function getReservedQtyByLocationProduct(locationProductId) {
    const id = parseInt(locationProductId, 10);

    return saleProducts.reduce((total, item) => {
        if (parseInt(item.location_product_id, 10) === id) {
            return total + parseInt(item.quantity || 0, 10);
        }

        return total;
    }, 0);
}

/**
 * Obtiene el stock restante de una ubicación/producto,
 * descontando lo ya reservado en la venta actual.
 *
 * @param {number|string} locationProductId ID de la relación producto-ubicación.
 * @param {number|string} originalStock Stock original informado por backend.
 * @returns {number} Stock disponible restante.
 */
function getRemainingStockForLocationProduct(locationProductId, originalStock) {
    const reserved = getReservedQtyByLocationProduct(locationProductId);
    const remaining = parseInt(originalStock || 0) - reserved;
    return remaining > 0 ? remaining : 0;
}

/**
 * Prepara y reinicia el modal de agregado de productos.
 * Limpia selects, inputs y muestra el estado actual del tipo de despacho.
 *
 * @returns {void}
 */
function prepareProductModal() {
    const productSelectEl = document.getElementById('productSelect');
    const locationProductSelectEl = document.getElementById('locationProductSelect');
    const qtyEl = document.getElementById('productQty');
    const priceEl = document.getElementById('productPrice');
    const noteEl = document.getElementById('productNote');
    const stockInfoEl = document.getElementById('productStockInfo');
    const dispatchInfoEl = document.getElementById('locationDispatchInfo');

    if (productSelectEl) {
        productSelectEl.selectedIndex = 0;
    }

    if (locationProductSelectEl) {
        locationProductSelectEl.innerHTML = '<option value="">Seleccione una ubicación</option>';
    }

    if (qtyEl) qtyEl.value = '';
    if (priceEl) priceEl.value = '';
    if (noteEl) noteEl.value = '';
    if (stockInfoEl) stockInfoEl.value = '-';

    const lockedDispatchType = getLockedDispatchType();

    if (dispatchInfoEl) {
        if (lockedDispatchType) {
            dispatchInfoEl.innerHTML = `Esta venta quedó definida como despacho <strong>${lockedDispatchType}</strong>.`;
        } else {
            dispatchInfoEl.innerHTML = 'Aún no hay un tipo de despacho definido para esta venta.';
        }
    }
}

/* =========================
PRODUCT LOCATIONS
========================= */

/**
 * Carga las ubicaciones disponibles para el producto seleccionado,
 * filtrando por cuenta emisora y respetando el tipo de despacho ya bloqueado.
 *
 * Además:
 * - actualiza el precio sugerido del producto,
 * - calcula stock disponible real descontando reservas actuales,
 * - informa al usuario sobre la compatibilidad del despacho.
 *
 * @async
 * @returns {Promise<void>}
 */
async function loadProductLocations() {
    const productSelectEl = document.getElementById('productSelect');
    const locationProductSelectEl = document.getElementById('locationProductSelect');
    const priceEl = document.getElementById('productPrice');
    const stockInfoEl = document.getElementById('productStockInfo');
    const dispatchInfoEl = document.getElementById('locationDispatchInfo');
    const accountSenderEl = document.querySelector('[name="account_sender"]');

    if (!productSelectEl || !locationProductSelectEl || !accountSenderEl) {
        console.error('Faltan elementos del DOM para cargar ubicaciones');
        return;
    }

    const productId = parseInt(productSelectEl.value || 0, 10);
    const accountSender = parseInt(accountSenderEl.value || 0, 10);
    const selectedProductOption = productSelectEl.selectedOptions[0];

    if (priceEl && selectedProductOption) {
        priceEl.value = parseFloat(
            selectedProductOption.dataset.price || 0
        ).toFixed(2);
    }

    if (stockInfoEl) {
        stockInfoEl.value = '-';
    }

    if (!productId || !accountSender) {
        locationProductSelectEl.innerHTML =
            '<option value="">Seleccione una ubicación</option>';

        if (dispatchInfoEl) {
            dispatchInfoEl.innerHTML =
                'Seleccione un producto para ver ubicaciones disponibles.';
        }

        return;
    }

    locationProductSelectEl.innerHTML =
        '<option value="">Cargando ubicaciones...</option>';

    try {
        const url =
            `get_product_locations_by_product.php?product_id=${productId}&account=${accountSender}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        console.log('DATA UBICACIONES:', data);

        locationProductSelectEl.innerHTML = '';

        const lockedDispatchType =
            getLockedDispatchType();

        let html =
            '<option value="">Seleccione una ubicación</option>';

        let countAvailable = 0;

        if (Array.isArray(data) && data.length > 0) {

            data.forEach(item => {

                const locationType =
                    item.location_type === 'Externa'
                        ? 'Externa'
                        : 'Interna';

                const dispatchType =
                    locationType === 'Externa'
                        ? 'Externo'
                        : 'Interno';

                /*
                 * No permitir mezclar tipos de despacho.
                 */
                if (
                    lockedDispatchType &&
                    lockedDispatchType !== dispatchType
                ) {
                    return;
                }

                /*
                 * item.qty = STOCK REAL DE LA UBICACIÓN.
                 *
                 * getRemainingStockForLocationProduct()
                 * descuenta lo que ya está reservado en
                 * saleProducts.
                 */
                const originalStock =
                    parseInt(item.qty || 0, 10);

                const remainingStock =
                    getRemainingStockForLocationProduct(
                        item.id,
                        originalStock
                    );

                /*
                 * Si realmente no queda stock,
                 * no mostramos la ubicación.
                 */
                if (remainingStock <= 0) {
                    return;
                }

                countAvailable++;

                const option =
                    document.createElement('option');

                option.value = item.id;

                option.dataset.locationId =
                    item.location_id;

                option.dataset.locationName =
                    item.location_name || '';

                option.dataset.locationType =
                    locationType;

                option.dataset.dispatchType =
                    dispatchType;

                /*
                 * IMPORTANTE:
                 *
                 * dataset.stock representa el STOCK
                 * RESTANTE, porque ya descontamos
                 * las reservas actuales.
                 */
                option.dataset.stock =
                    remainingStock;

                option.textContent =
                    `${item.location_name} | Stock: ${remainingStock} | ${locationType}`;

                locationProductSelectEl.appendChild(option);
            });
        }

        if (countAvailable === 0) {

            locationProductSelectEl.innerHTML =
                '<option value="">Sin ubicaciones disponibles</option>';

        } else {

            const firstOption =
                document.createElement('option');

            firstOption.value = '';
            firstOption.textContent =
                'Seleccione una ubicación';

            locationProductSelectEl.insertBefore(
                firstOption,
                locationProductSelectEl.firstChild
            );

            locationProductSelectEl.value = '';
        }

        if (dispatchInfoEl) {

            if (lockedDispatchType) {

                dispatchInfoEl.innerHTML =
                    countAvailable > 0
                        ? `Esta venta está definida como <strong>${lockedDispatchType}</strong>. Solo se muestran ubicaciones compatibles con stock disponible.`
                        : `Esta venta está definida como <strong>${lockedDispatchType}</strong> y este producto no tiene ubicaciones compatibles disponibles.`;

            } else {

                dispatchInfoEl.innerHTML =
                    countAvailable > 0
                        ? 'Seleccione una ubicación. El responsable del despacho se define automáticamente.'
                        : 'Este producto no tiene ubicaciones disponibles con stock.';
            }
        }

    } catch (error) {

        console.error(
            'ERROR loadProductLocations:',
            error
        );

        console.error(
            'STACK:',
            error?.stack
        );

        locationProductSelectEl.innerHTML =
            '<option value="">Error al cargar ubicaciones</option>';

        if (dispatchInfoEl) {
            dispatchInfoEl.innerHTML =
                'Ocurrió un error al consultar las ubicaciones.';
        }
    }
}

/* =========================
PRODUCTS
========================= */

/**
 * Agrega un producto a la venta usando la información
 * capturada en el modal.
 *
 * Cada producto agregado conserva su propia fila
 * y comienza sin descuento seleccionado.
 *
 * @returns {void}
 */
function saleAddProductFromModal() {

    const productSelect =
        document.getElementById('productSelect');

    const locationSelect =
        document.getElementById('locationProductSelect');

    const qtyInput =
        document.getElementById('productQty');

    const priceInput =
        document.getElementById('productPrice');

    const noteInput =
        document.getElementById('productNote');

    const productId =
        parseInt(productSelect.value, 10);

    const locationProductId =
        parseInt(locationSelect.value, 10);

    const qty =
        parseInt(qtyInput.value, 10);

    const price =
        parseFloat(priceInput.value);

    const note =
        noteInput.value.trim();

    if (!productId) {
        Swal.fire({
            icon: 'warning',
            title: 'Producto requerido',
            text: 'Seleccione un producto.'
        });

        return;
    }

    if (!locationProductId) {
        Swal.fire({
            icon: 'warning',
            title: 'Ubicación requerida',
            text: 'Seleccione una ubicación.'
        });

        return;
    }

    if (!qty || qty <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cantidad inválida',
            text: 'Ingrese una cantidad válida.'
        });

        return;
    }

    if (isNaN(price) || price < 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Precio inválido',
            text: 'Ingrese un precio válido.'
        });

        return;
    }

    const selectedOption =
        productSelect.options[
            productSelect.selectedIndex
        ];

    const locationOption =
        locationSelect.options[
            locationSelect.selectedIndex
        ];

    const productName =
        selectedOption.dataset.name ||
        selectedOption.textContent.trim();

    const locationName =
        locationOption.dataset.locationName ||
        locationOption.textContent.trim();

    const locationId =
        parseInt(
            locationOption.dataset.locationId || 0,
            10
        );

    const locationType =
        locationOption.dataset.locationType || '';

    const dispatchType =
        locationOption.dataset.dispatchType || '';

    const stockAvailable =
        parseInt(
            locationOption.dataset.stock ||
            locationOption.dataset.stockAvailable ||
            0,
            10
        );

    if (qty > stockAvailable) {
        Swal.fire({
            icon: 'warning',
            title: 'Stock insuficiente',
            text:
                'Solo quedan ' +
                stockAvailable +
                ' unidades disponibles en esta ubicación.'
        });

        return;
    }

    const lockedDispatchType =
        getLockedDispatchType();

    if (
        lockedDispatchType &&
        dispatchType &&
        dispatchType !== lockedDispatchType
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'Tipo de despacho diferente',
            text:
                'No puede mezclar productos con despacho ' +
                lockedDispatchType +
                ' y ' +
                dispatchType +
                ' en la misma venta.'
        });

        return;
    }

    saleProducts.push({

        product_id:
            productId,

        name:
            productName,

        location_product_id:
            locationProductId,

        location_id:
            locationId,

        location_name:
            locationName,

        location_type:
            locationType,

        dispatch_type:
            dispatchType,

        stock_available:
            stockAvailable,

        quantity:
            qty,

        unitPrice:
            price,

        note:
            note,

        discount_selected:
            false,

        discount_percent:
            0,

        discount_amount:
            0,

        subtotal:
            Math.round(
                (qty * price) * 100
            ) / 100,

        final_subtotal:
            Math.round(
                (qty * price) * 100
            ) / 100
    });

    syncProductDiscountData();

    qtyInput.value = 1;
    noteInput.value = '';

    renderProducts();

    const modal =
        bootstrap.Modal.getInstance(
            document.getElementById('addProductModal')
        );

    if (modal) {
        modal.hide();
    }
}

/**
 * Renderiza la tabla de productos seleccionados.
 *
 * Muestra el checkbox de descuento, información del producto,
 * cantidad, precio, nota y valores calculados.
 *
 * @returns {void}
 */
function renderProducts() {

    const tbody =
        document.querySelector(
            '#productosSeleccionados tbody'
        );

    const tfoot =
        document.querySelector(
            '#productosSeleccionados tfoot'
        );

    if (!tbody || !tfoot) {
        return;
    }

    syncProductDiscountData();

    tbody.innerHTML = '';

    let totalOriginal = 0;
    let totalDiscount = 0;
    let totalFinal = 0;

    const discountPercent =
        getDiscountPercent();

    saleProducts.forEach((p, i) => {

        const subtotal =
            getProductSubtotal(p);

        const discount =
            getProductDiscountAmount(p);

        const finalSubtotal =
            getProductFinalSubtotal(p);

        totalOriginal += subtotal;
        totalDiscount += discount;
        totalFinal += finalSubtotal;

        /*
         * El checkbox solamente se deshabilita
         * cuando el porcentaje es 0.
         *
         * IMPORTANTE:
         * No modificamos discount_selected.
         */
        const checkboxDisabled =
            discountPercent <= 0
                ? 'disabled'
                : '';

        const checkboxChecked =
            p.discount_selected
                ? 'checked'
                : '';

        let subtotalHtml = `
            $${formatMoney(finalSubtotal)}
        `;

        if (discount > 0) {

            subtotalHtml = `
                <div class="text-muted text-decoration-line-through small">
                    $${formatMoney(subtotal)}
                </div>

                <div class="fw-semibold">
                    $${formatMoney(finalSubtotal)}
                </div>

                <small class="text-success">
                    -$${formatMoney(discount)}
                    (${formatMoney(discountPercent)}%)
                </small>
            `;
        }

        tbody.innerHTML += `
            <tr>

                <!-- DESCUENTO -->

                <td class="text-center">

                    <input
                        type="checkbox"
                        class="form-check-input product-discount-checkbox"
                        title="Aplicar descuento a este producto"
                        ${checkboxChecked}
                        ${checkboxDisabled}
                        onchange="updateProductDiscountSelection(${i}, this.checked)"
                    >

                </td>


                <!-- PRODUCTO -->

                <td>
                    ${escapeHtml(p.name)}
                </td>


                <!-- UBICACIÓN -->

                <td>
                    ${escapeHtml(p.location_name)}
                </td>


                <!-- DESPACHO -->

                <td>

                    <span class="badge ${
                        p.dispatch_type === 'Externo'
                            ? 'bg-warning text-dark'
                            : 'bg-primary'
                    }">

                        ${escapeHtml(p.dispatch_type)}

                    </span>

                </td>


                <!-- CANTIDAD -->

                <td>

                    <input
                        class="form-control form-control-sm"
                        type="number"
                        min="1"
                        value="${parseInt(p.quantity)}"
                        onchange="updateQty(${i}, this.value)"
                    >

                </td>


                <!-- PRECIO -->

                <td>

                    <input
                        class="form-control form-control-sm"
                        type="number"
                        min="0"
                        step="0.01"
                        value="${parseFloat(p.unitPrice)}"
                        onchange="updatePrice(${i}, this.value)"
                    >

                </td>


                <!-- NOTA -->

                <td>

                    <input
                        type="text"
                        class="form-control form-control-sm"
                        value="${escapeHtml(p.note || '')}"
                        onchange="updateNote(${i}, this.value)"
                    >

                </td>


                <!-- SUBTOTAL -->

                <td>
                    ${subtotalHtml}
                </td>


                <!-- ELIMINAR -->

                <td class="text-center">

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar producto"
                        onclick="removeProduct(${i})"
                    >
                        <i class="bi bi-trash-fill"></i>
                    </button>

                </td>

            </tr>
        `;
    });


    /*
     * Sin productos
     */

    if (saleProducts.length === 0) {

        tfoot.innerHTML = '';

    } else {

        totalOriginal =
            Math.round(totalOriginal * 100) / 100;

        totalDiscount =
            Math.round(totalDiscount * 100) / 100;

        totalFinal =
            Math.round(totalFinal * 100) / 100;

        tfoot.innerHTML = `
            <tr>

                <th colspan="7" class="text-end">
                    Subtotal
                </th>

                <th>
                    $${formatMoney(totalOriginal)}
                </th>

                <th></th>

            </tr>

            <tr>

                <th colspan="7" class="text-end text-success">
                    Descuento
                </th>

                <th class="text-success">
                    -$${formatMoney(totalDiscount)}
                </th>

                <th></th>

            </tr>

            <tr>

                <th colspan="7" class="text-end">
                    Total
                </th>

                <th>
                    $${formatMoney(totalFinal)}
                </th>

                <th></th>

            </tr>
        `;
    }

    updateUI();
}

/**
 * Actualiza la cantidad de un producto y recalcula
 * los valores relacionados con su descuento.
 *
 * @param {number} i Índice del producto.
 * @param {string|number} value Nueva cantidad.
 * @returns {void}
 */
function updateQty(i, value) {

    if (!saleProducts[i]) {
        return;
    }

    const qty =
        parseInt(value || 0, 10);

    if (!qty || qty <= 0) {

        saleProducts[i].quantity = 1;

        syncProductDiscountData();
        renderProducts();

        return;
    }

    const currentProduct =
        saleProducts[i];

    const locationProductId =
        parseInt(
            currentProduct.location_product_id,
            10
        );

    const stockAvailable =
        parseInt(
            currentProduct.stock_available || 0,
            10
        );

    const reservedByOtherRows =
        saleProducts.reduce(
            (total, item, index) => {

                if (index === i) {
                    return total;
                }

                if (
                    parseInt(
                        item.location_product_id,
                        10
                    ) === locationProductId
                ) {

                    return total +
                        parseInt(
                            item.quantity || 0,
                            10
                        );
                }

                return total;

            },
            0
        );

    const availableForThisRow =
        stockAvailable -
        reservedByOtherRows;

    if (qty > availableForThisRow) {

        Swal.fire({
            icon: 'warning',
            title: 'Stock insuficiente',
            text:
                'Solo puede asignar ' +
                Math.max(
                    availableForThisRow,
                    0
                ) +
                ' unidades en esta fila.'
        });

        renderProducts();

        return;
    }

    currentProduct.quantity =
        qty;

    syncProductDiscountData();

    renderProducts();
}

/**
 * Actualiza el precio unitario de un producto.
 *
 * Si el producto tiene descuento seleccionado,
 * el valor del descuento se recalcula automáticamente.
 *
 * @param {number} i Índice del producto.
 * @param {string|number} value Nuevo precio.
 * @returns {void}
 */
function updatePrice(i, value) {

    if (!saleProducts[i]) {
        return;
    }

    const price =
        parseFloat(value || 0);

    saleProducts[i].unitPrice =
        price >= 0
            ? price
            : 0;

    syncProductDiscountData();

    renderProducts();
}


/**
 * Actualiza la nota de un producto seleccionado.
 *
 * @param {number} i Índice del producto en `saleProducts`.
 * @param {string} value Nueva nota.
 * @returns {void}
 */
function updateNote(i, value) {
    if (!saleProducts[i]) return;
    saleProducts[i].note = value;
}

/**
 * Elimina un producto de la venta.
 *
 * @param {number} i Índice del producto.
 * @returns {void}
 */
function removeProduct(i) {

    saleProducts.splice(i, 1);

    syncProductDiscountData();

    renderProducts();
}

/* =========================
PAYMENTS
========================= */

/**
 * Agrega un pago a la lista de pagos de la venta actual.
 *
 * @returns {void}
 */
function addPayment() {
    const paymentAccountEl = document.getElementById('paymentAccount');
    const paymentAmountEl = document.getElementById('paymentAmount');
    const paymentReferenceEl = document.getElementById('paymentReference');

    if (!paymentAccountEl || !paymentAmountEl || !paymentReferenceEl) return;

    const amount = parseFloat(paymentAmountEl.value || 0);

    if (!paymentAccountEl.value) {
        alert('Seleccione una cuenta financiera.');
        return;
    }

    if (!amount || amount <= 0) {
        alert('Ingrese un monto válido.');
        return;
    }

    salePayments.push({
        account_id: paymentAccountEl.value,
        account_name: paymentAccountEl.options[paymentAccountEl.selectedIndex].text,
        amount: amount,
        reference: paymentReferenceEl.value.trim()
    });

    paymentAmountEl.value = '';
    paymentReferenceEl.value = '';
    renderPayments();
}

/**
 * Renderiza la tabla de pagos registrados.
 *
 * @returns {void}
 */
function renderPayments() {
    const paymentsTable = document.getElementById('paymentsTable');
    if (!paymentsTable) return;

    paymentsTable.innerHTML = '';

    salePayments.forEach((p, i) => {
        paymentsTable.innerHTML += `
            <tr>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePayment(${i})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
                <td>${escapeHtml(p.account_name)}</td>
                <td>${formatMoney(p.amount)}</td>
                <td>${escapeHtml(p.reference)}</td>
            </tr>
        `;
    });

    updateUI();
}

/**
 * Elimina un pago de la venta actual.
 *
 * @param {number} i Índice del pago a eliminar.
 * @returns {void}
 */
function removePayment(i) {
    salePayments.splice(i, 1);
    renderPayments();
}

/* =========================
TOTALS & SUMMARY
========================= */

/**
 * Obtiene el total final de los productos después
 * de aplicar los descuentos seleccionados.
 *
 * @returns {number} Total final de productos.
 */
function getTotalProducts() {

    const total =
        saleProducts.reduce((sum, product) => {

            return sum +
                getProductFinalSubtotal(product);

        }, 0);

    return Math.round(
        total * 100
    ) / 100;
}

/**
 * Obtiene el valor total pagado en la venta actual.
 *
 * @returns {number} Total pagado.
 */
function getTotalPaid() {
    return salePayments.reduce((sum, p) => {
        return sum + parseFloat(p.amount);
    }, 0);
}

/**
 * Calcula el saldo pendiente de la venta considerando
 * el total después de descuentos.
 *
 * @returns {number} Saldo pendiente.
 */
function getRemaining() {

    const remaining =
        getTotalProducts() -
        getTotalPaid();

    return Math.round(
        remaining * 100
    ) / 100;
}


/**
 * Determina si la venta puede ser confirmada.
 *
 * Reglas:
 * - Debe existir al menos un producto.
 * - Si es Delivery, puede confirmarse aunque exista saldo pendiente.
 * - Si no es Delivery, debe quedar totalmente pagada.
 *
 * @returns {boolean} `true` si puede confirmarse; en otro caso `false`.
 */
function canConfirmSale() {
    if (saleProducts.length === 0) {
        return false;
    }

    if (isDeliverySale()) {
        return true;
    }

    return getRemaining() === 0;
}

/**
 * Actualiza todos los componentes visuales relacionados con el estado de la venta.
 *
 * @returns {void}
 */
function updateUI() {
    buildSummary();
    toggleConfirmButton();
    updatePaymentsHelpText();
}

/**
 * Habilita o deshabilita el botón de confirmación según el estado de la venta.
 *
 * @returns {void}
 */
function toggleConfirmButton() {
    const btn = document.getElementById('confirmSaleBtn');
    if (!btn) return;
    btn.disabled = !canConfirmSale();
}

/**
 * Actualiza el texto de ayuda relacionado con la forma de pago,
 * según el tipo de venta seleccionado.
 *
 * @returns {void}
 */
function updatePaymentsHelpText() {
    const helpText = document.getElementById('paymentsHelpText');
    if (!helpText) return;

    if (isDeliverySale()) {
        helpText.innerHTML = 'Venta Mayorista: puedes confirmar la venta aunque el pago quede pendiente.';
    } else {
        helpText.innerHTML = 'Venta Diaria y Mercado Libre requieren pago completo para confirmar.';
    }
}

/**
 * Construye el resumen visual de la venta.
 *
 * Incluye subtotal original, descuento total, total final,
 * pagos registrados y saldo pendiente.
 *
 * @returns {void}
 */
function buildSummary() {

    const saleSummary =
        document.getElementById('saleSummary');

    const clientNameEl =
        document.getElementById('client_name');

    const clientAddressEl =
        document.getElementById('client_address');

    if (!saleSummary) {
        return;
    }

    if (saleProducts.length === 0) {

        saleSummary.innerHTML =
            '<p class="text-muted">Sin productos</p>';

        return;
    }

    syncProductDiscountData();

    let html = `
        <h6 class="mb-2">Cliente</h6>

        <p class="mb-3">

            Nombre:
            ${escapeHtml(
                clientNameEl
                    ? clientNameEl.value
                    : '-'
            )}<br>

            Dirección:
            ${escapeHtml(
                clientAddressEl
                    ? clientAddressEl.value
                    : '-'
            )}<br>

            Tipo de Venta:
            ${escapeHtml(getSaleType())}

        </p>

        <h6 class="mb-2">
            Despacho
        </h6>

        <p class="mb-3">

            ${
                saleProducts.length > 0
                    ? `<strong>${escapeHtml(
                        saleProducts[0].dispatch_type
                    )}</strong>`
                    : '-'
            }

        </p>

        <h6 class="mb-2">
            Productos
        </h6>

        <ul class="mb-3">
    `;

    saleProducts.forEach(p => {

        const subtotal =
            getProductSubtotal(p);

        const discount =
            getProductDiscountAmount(p);

        const finalSubtotal =
            getProductFinalSubtotal(p);

        html += `
            <li class="mb-2">

                ${escapeHtml(p.name)}
                x ${parseInt(p.quantity)}

                <br>

                <small class="text-muted">
                    ${escapeHtml(p.location_name)}
                    |
                    ${escapeHtml(p.dispatch_type)}
                </small>

                <span class="float-end">

                    ${
                        discount > 0

                            ? `
                                <span class="text-muted text-decoration-line-through small">
                                    $${formatMoney(subtotal)}
                                </span>

                                <br>

                                <strong>
                                    $${formatMoney(finalSubtotal)}
                                </strong>
                              `

                            : `
                                $${formatMoney(finalSubtotal)}
                              `
                    }

                </span>

                ${
                    discount > 0

                        ? `
                            <div class="small text-success">
                                Descuento:
                                -$${formatMoney(discount)}
                                (${formatMoney(getDiscountPercent())}%)
                            </div>
                          `

                        : ''
                }

            </li>
        `;
    });

    html += `
        </ul>

        <h6 class="mb-2">
            Pagos
        </h6>

        <ul class="mb-3">
    `;

    if (salePayments.length === 0) {

        html += `
            <li class="text-muted">
                Sin pagos registrados
            </li>
        `;

    } else {

        salePayments.forEach(p => {

            html += `
                <li>

                    ${escapeHtml(p.account_name)}

                    <span class="float-end">
                        $${formatMoney(p.amount)}
                    </span>

                </li>
            `;
        });
    }

    const subtotal =
        saleProducts.reduce(
            (sum, product) => {

                return sum +
                    getProductSubtotal(product);

            },
            0
        );

    const totalDiscount =
        getTotalDiscount();

    const total =
        getTotalProducts();

    const totalPaid =
        getTotalPaid();

    const remaining =
        getRemaining();

    const remainingClass =
        remaining === 0
            ? 'text-success'
            : 'text-danger';

    html += `
        </ul>

        <hr>

        <div class="d-flex justify-content-between">

            <span>
                Subtotal
            </span>

            <span>
                $${formatMoney(subtotal)}
            </span>

        </div>

        <div class="d-flex justify-content-between text-success">

            <span>
                Descuento
            </span>

            <span>
                -$${formatMoney(totalDiscount)}
            </span>

        </div>

        <div class="d-flex justify-content-between fw-bold">

            <span>
                Total
            </span>

            <span>
                $${formatMoney(total)}
            </span>

        </div>

        <div class="d-flex justify-content-between">

            <span>
                Pagado
            </span>

            <span>
                $${formatMoney(totalPaid)}
            </span>

        </div>

        <div class="d-flex justify-content-between ${remainingClass}">

            <span>
                ${
                    isDeliverySale()
                        ? 'Pendiente / Crédito'
                        : 'Pendiente'
                }
            </span>

            <span>
                $${formatMoney(remaining)}
            </span>

        </div>
    `;

    if (isDeliverySale() && remaining > 0) {

        html += `
            <div class="alert alert-warning mt-3 mb-0 py-2">
                Venta Mayorista: se permite confirmar con saldo pendiente.
            </div>
        `;
    }

    saleSummary.innerHTML =
        html;
}

/**
 * Prepara y envía el formulario de venta al backend.
 *
 * Antes de enviar sincroniza la información de descuentos
 * de cada producto y serializa productos y pagos.
 *
 * @param {SubmitEvent} [e] Evento submit del formulario.
 * @returns {boolean} Indica si el formulario fue preparado.
 */
function submitSale(e) {

    if (e) {
        e.preventDefault();
    }

    const submitter =
        e
            ? e.submitter
            : null;

    const action =
        submitter
            ? submitter.value
            : 'emit';

    if (
        action === 'emit' &&
        saleProducts.length === 0
    ) {

        alert(
            'Debe agregar al menos un producto para emitir la venta.'
        );

        return false;
    }

    if (
        action === 'emit' &&
        !isDeliverySale() &&
        getRemaining() !== 0
    ) {

        alert(
            'La venta debe quedar totalmente pagada.'
        );

        return false;
    }

    const productsInput =
        document.getElementById('productsInput');

    const paymentsInput =
        document.getElementById('paymentsInput');

    const saleForm =
        document.getElementById('saleForm');

    if (
        !productsInput ||
        !paymentsInput ||
        !saleForm
    ) {

        alert(
            'No se pudo preparar el formulario de venta.'
        );

        return false;
    }

    syncProductDiscountData();

    productsInput.value =
        JSON.stringify(saleProducts);

    paymentsInput.value =
        JSON.stringify(salePayments);

    saleForm.submit();

    return true;
}

/* =========================
UX
========================= */

/**
 * Inicializa comportamientos UX del módulo de ventas:
 * - Enter para agregar pagos.
 * - Cambio de ubicación para actualizar stock y despacho visible.
 * - Render inicial del resumen y estados.
 */
document.addEventListener('DOMContentLoaded', function () {
  const paymentAmountEl = document.getElementById('paymentAmount');
  const locationProductSelectEl = document.getElementById('locationProductSelect');
  const productStockInfoEl = document.getElementById('productStockInfo');
  const locationDispatchInfoEl = document.getElementById('locationDispatchInfo');
  const saleForm = document.getElementById('saleForm');

  if (paymentAmountEl) {
    paymentAmountEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addPayment();
      }
    });
  }

  if (locationProductSelectEl) {
    locationProductSelectEl.addEventListener('change', function () {
      const selectedOption = this.selectedOptions[0];

      if (!selectedOption || !selectedOption.value) {
        if (productStockInfoEl) productStockInfoEl.value = '-';
        return;
      }

      const stock = selectedOption.dataset.stock || '-';
      const dispatchType = selectedOption.dataset.dispatchType || '-';
      const locationType = selectedOption.dataset.locationType || '-';
      const lockedDispatchType = getLockedDispatchType();

      if (productStockInfoEl) {
        productStockInfoEl.value = stock;
      }

      if (locationDispatchInfoEl) {
        let extra = '';
        if (lockedDispatchType) {
          extra = ` | Venta definida: <strong>${lockedDispatchType}</strong>`;
        }

        locationDispatchInfoEl.innerHTML =
          `Ubicación ${locationType} → despacho automático <strong>${dispatchType}</strong>${extra}`;
      }
    });
  }

 if (Array.isArray(window.preloadedSaleProducts)) {

    saleProducts = window.preloadedSaleProducts;

    /*
     * ---------------------------------------------------------
     * DESCUENTO PRECARGADO PARA EMIT_SALE
     * ---------------------------------------------------------
     *
     * Busca el porcentaje de descuento existente en la venta.
     * Esto permite que emit_sale.php cargue, por ejemplo, 10%
     * antes de ejecutar renderProducts().
     *
     * IMPORTANTE:
     * No se modifica la estructura de saleProducts.
     * Cada producto sigue siendo una fila independiente.
     */

    let preloadedDiscountPercent = 0;

    for (let i = 0; i < saleProducts.length; i++) {

        const product = saleProducts[i];

        const productDiscountPercent =
            parseFloat(product.discount_percent || 0);

        if (
            productDiscountPercent > 0 &&
            productDiscountPercent <= 100
        ) {
            preloadedDiscountPercent =
                productDiscountPercent;

            break;
        }
    }

    const discountInput =
        document.getElementById('saleDiscountPercent');

    if (
        discountInput &&
        preloadedDiscountPercent > 0
    ) {
        discountInput.value =
            preloadedDiscountPercent;
    }

    /*
     * Normalizar los productos precargados.
     *
     * Si un producto tenía descuento en la venta,
     * queda seleccionado.
     *
     * Si no tenía descuento, queda sin seleccionar.
     */
    saleProducts.forEach(product => {

        const productDiscountPercent =
            parseFloat(product.discount_percent || 0);

        product.discount_selected =
            productDiscountPercent > 0;

        const subtotal =
            getProductSubtotal(product);

        product.subtotal =
            Math.round(subtotal * 100) / 100;

        if (
            product.discount_selected &&
            preloadedDiscountPercent > 0
        ) {

            const discountAmount =
                Math.round(
                    subtotal *
                    (preloadedDiscountPercent / 100) *
                    100
                ) / 100;

            product.discount_percent =
                preloadedDiscountPercent;

            product.discount_amount =
                discountAmount;

            product.final_subtotal =
                Math.round(
                    (subtotal - discountAmount) * 100
                ) / 100;

        } else {

            product.discount_percent = 0;
            product.discount_amount = 0;
            product.final_subtotal = product.subtotal;
        }
    });
}

  if (Array.isArray(window.preloadedSalePayments)) {
    salePayments = window.preloadedSalePayments;
  }

if (saleForm) {
  saleForm.addEventListener('submit', function (e) {
    const productsInput = document.getElementById('productsInput');
    const paymentsInput = document.getElementById('paymentsInput');

    if (!productsInput || !paymentsInput) {
      e.preventDefault();
      alert('No se pudo preparar el formulario.');
      return;
    }

    const submitter = e.submitter;
    const action = submitter ? submitter.value : '';

    if (action === 'emit' && saleProducts.length === 0) {
      e.preventDefault();
      alert('Debe agregar al menos un producto para emitir la venta.');
      return;
    }

    if (action === 'emit' && !isDeliverySale() && getRemaining() !== 0) {
      e.preventDefault();
      alert('La venta debe quedar totalmente pagada.');
      return;
    }

    syncProductDiscountData();

    productsInput.value =
        JSON.stringify(saleProducts);

    paymentsInput.value =
        JSON.stringify(salePayments);
        
  });
}

  renderProducts();
  renderPayments();
  updateUI();

  const btn = document.getElementById('confirmSaleBtn');
  if (btn) {
    btn.textContent = 'Emitir Venta';
  }
});
//AQUI FINALIZA EL PROCESO DE VENTA

//AQUI INICIA EL PROCDESO DE CONSULTAR VENTAS
/* =========================================================
   VENTAS - DETALLE EN LA MISMA TABLA
   PEGAR EN functions.js
========================================================= */

/**
 * Escapa texto para renderizar de forma segura en el detalle de ventas consultadas.
 *
 * @param {string|null|undefined} text Texto a escapar.
 * @returns {string} Texto escapado.
 */
function salesEscapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text == null ? '' : text;
  return div.innerHTML;
}

/**
 * Formatea valores monetarios usando configuración regional es-CO
 * con 2 decimales.
 *
 * @param {number|string} value Valor a formatear.
 * @returns {string} Valor formateado.
 */
function salesMoney(value) {
  const number = parseFloat(value || 0);
  return number.toLocaleString('es-CO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

/**
 * Cierra todas las filas de detalle abiertas, excepto una opcional.
 *
 * @param {number|string|null} [exceptSaleId=null] ID de venta que debe permanecer abierta.
 * @returns {void}
 */
function closeAllSaleDetails(exceptSaleId = null) {
  document.querySelectorAll('tr[id^="sale-detail-row-"]').forEach(row => {
    const currentId = row.id.replace('sale-detail-row-', '');
    if (exceptSaleId === null || String(currentId) !== String(exceptSaleId)) {
      row.remove();
    }
  });
}

/**
 * Construye el HTML del detalle expandido de una venta consultada.
 *
 * Incluye:
 * - datos generales de la venta,
 * - tabla de productos,
 * - tabla de pagos,
 * - resumen financiero.
 *
 * @param {Object} data Respuesta JSON del backend.
 * @param {Object} [data.sale] Cabecera de la venta.
 * @param {Array<Object>} [data.products] Productos de la venta.
 * @param {Array<Object>} [data.payments] Pagos de la venta.
 * @returns {string} HTML listo para ser insertado en el DOM.
 */
function renderSaleDetailHtml(data) {
  const sale = data.sale || {};
  const products = Array.isArray(data.products) ? data.products : [];
  const payments = Array.isArray(data.payments) ? data.payments : [];

  let productsRows = '';
  let paymentsRows = '';
  let totalProfit = 0;
  let totalPaid = 0;
  let totalDiscount = 0;

  products.forEach(item => {
    const qty = parseFloat(item.qty || 0);
    const price = parseFloat(item.price || 0);
    const costPrice = parseFloat(item.cost_price || 0);

    const discountPercent = parseFloat(item.discount_percent || 0);
    const discountedPrice = parseFloat(
      item.discounted_price !== undefined && item.discounted_price !== null
        ? item.discounted_price
        : price
    );

    // Subtotal original antes del descuento
    const originalSubtotal = qty * price;

    // Subtotal final después del descuento
    const subtotal = qty * discountedPrice;

    // Descuento total aplicado a esta línea
    const discountAmount = originalSubtotal - subtotal;

    // Utilidad calculada sobre el precio realmente cobrado
    const profit = (discountedPrice - costPrice) * qty;

    totalDiscount += discountAmount;
    totalProfit += profit;

    let priceHtml = `$${salesMoney(price)}`;
    let discountHtml = '-';

    if (discountPercent > 0) {
      priceHtml = `
        <div>
          <span class="text-muted text-decoration-line-through">
            $${salesMoney(price)}
          </span>
          <br>
          <span class="fw-semibold">
            $${salesMoney(discountedPrice)}
          </span>
        </div>
      `;

      discountHtml = `
        <div>
          <span class="badge bg-success">
            ${salesMoney(discountPercent)}%
          </span>
          <br>
          <small class="text-muted">
            -$${salesMoney(discountAmount)}
          </small>
        </div>
      `;
    }

    productsRows += `
      <tr>
        <td>${salesEscapeHtml(item.product_name)}</td>

        <td>${salesEscapeHtml(item.location_name || '-')}</td>

        <td>
          <span class="badge ${item.dispatch_type === 'Externo' ? 'bg-warning text-dark' : 'bg-primary'}">
            ${salesEscapeHtml(item.dispatch_type || '-')}
          </span>
        </td>

        <td>${salesMoney(qty)}</td>

        <td>
          ${priceHtml}
        </td>

        <td>
          $${salesMoney(costPrice)}
        </td>

        <td>
          ${discountHtml}
        </td>

        <td>
          $${salesMoney(subtotal)}
        </td>

        <td>
          $${salesMoney(profit)}
        </td>

        <td>
          ${salesEscapeHtml(item.note || '-')}
        </td>
      </tr>
    `;
  });

  if (!productsRows) {
    productsRows = `
      <tr>
        <td colspan="10" class="text-center text-muted">
          Sin productos registrados
        </td>
      </tr>
    `;
  }

  payments.forEach(item => {
    const amount = parseFloat(item.amount || 0);
    totalPaid += amount;

    paymentsRows += `
      <tr>
        <td>${salesEscapeHtml(item.account_name || '-')}</td>
        <td>${salesEscapeHtml(item.reference || '-')}</td>
        <td>$${salesMoney(amount)}</td>
      </tr>
    `;
  });

  if (!paymentsRows) {
    paymentsRows = `
      <tr>
        <td colspan="3" class="text-center text-muted">
          Sin pagos registrados
        </td>
      </tr>
    `;
  }

  const saleTotal = parseFloat(sale.total || 0);
  const remaining = saleTotal - totalPaid;

  return `
    <div class="card shadow-sm border-0">
      <div class="card-body">

        <div class="mb-4">
          <div class="row g-3">

            <div class="col-md-3">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Cliente</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.client_name || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Tipo</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.sale_type || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Estado</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.status || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-2">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Teléfono</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.phone || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Usuario</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.user || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-8">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Dirección</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.address || '-')}
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card h-100 border">
                <div class="card-body">
                  <span class="text-muted small d-block mb-1">Nota</span>
                  <div class="fw-semibold">
                    ${salesEscapeHtml(sale.note || '-')}
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="mb-4">
          <h6 class="mb-3">Productos</h6>

          <div class="table-responsive">
            <table class="table text-nowrap align-middle mb-0">

              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Ubicación</th>
                  <th>Despacho</th>
                  <th>Cant.</th>
                  <th>Precio</th>
                  <th>Costo</th>
                  <th>Descuento</th>
                  <th>Subtotal</th>
                  <th>Utilidad</th>
                  <th>Nota</th>
                </tr>
              </thead>

              <tbody>
                ${productsRows}
              </tbody>

            </table>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="mb-3">Pagos</h6>

          <div class="table-responsive">
            <table class="table text-nowrap align-middle mb-0">

              <thead>
                <tr>
                  <th>Cuenta</th>
                  <th>Referencia</th>
                  <th>Monto</th>
                </tr>
              </thead>

              <tbody>
                ${paymentsRows}
              </tbody>

            </table>
          </div>
        </div>

        <div class="row g-3">

          <div class="col-md-3">
            <div class="card border h-100">
              <div class="card-body">
                <span class="text-muted small d-block mb-1">
                  Total descuento
                </span>

                <div class="fw-semibold fs-5">
                  $${salesMoney(totalDiscount)}
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card border h-100">
              <div class="card-body">
                <span class="text-muted small d-block mb-1">
                  Total venta
                </span>

                <div class="fw-semibold fs-5">
                  $${salesMoney(saleTotal)}
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="card border h-100">
              <div class="card-body">
                <span class="text-muted small d-block mb-1">
                  Pagado
                </span>

                <div class="fw-semibold fs-5">
                  $${salesMoney(totalPaid)}
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="card border h-100">
              <div class="card-body">
                <span class="text-muted small d-block mb-1">
                  Pendiente
                </span>

                <div class="fw-semibold fs-5 ${remaining === 0 ? 'text-success' : 'text-danger'}">
                  $${salesMoney(remaining)}
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-2">
            <div class="card border h-100">
              <div class="card-body">
                <span class="text-muted small d-block mb-1">
                  Utilidad estimada
                </span>

                <div class="fw-semibold fs-5">
                  $${salesMoney(totalProfit)}
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  `;
}



/**
 * Abre o cierra el detalle de una venta dentro de la misma tabla.
 *
 * Comportamiento:
 * - si el detalle ya está abierto, lo cierra;
 * - si no está abierto, cierra otros detalles y carga el actual vía AJAX.
 *
 * @async
 * @param {number|string} saleId ID de la venta a consultar.
 * @returns {Promise<void>}
 */
async function toggleSaleDetail(saleId) {
  const currentRow = document.getElementById(`sale-row-${saleId}`);
  const existingDetailRow = document.getElementById(`sale-detail-row-${saleId}`);

  if (!currentRow) {
    return;
  }

  if (existingDetailRow) {
    existingDetailRow.remove();
    return;
  }

  closeAllSaleDetails(saleId);

  const detailRow = document.createElement('tr');
  detailRow.id = `sale-detail-row-${saleId}`;
  detailRow.innerHTML = `
    <td colspan="8" class="pt-2 pb-3 px-0">
      <div class="px-2">
        <div class="card shadow-sm border">
          <div class="card-body text-center text-muted">
            Cargando detalle...
          </div>
        </div>
      </div>
    </td>
  `;

  currentRow.insertAdjacentElement('afterend', detailRow);

  try {
    const response = await fetch(`get_sale_detail.php?id=${saleId}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    if (!data.success) {
      detailRow.innerHTML = `
        <td colspan="8" class="pt-2 pb-3 px-0">
          <div class="px-2">
            <div class="alert alert-danger mb-0">
              ${salesEscapeHtml(data.message || 'No se pudo cargar el detalle.')}
            </div>
          </div>
        </td>
      `;
      return;
    }

    detailRow.innerHTML = `
      <td colspan="8" class="pt-2 pb-3 px-0">
        <div class="px-2">
          ${renderSaleDetailHtml(data)}
        </div>
      </td>
    `;
  } catch (error) {
    console.error('Error al cargar detalle de venta:', error);
    detailRow.innerHTML = `
      <td colspan="8" class="pt-2 pb-3 px-0">
        <div class="px-2">
          <div class="alert alert-danger mb-0">
            Ocurrió un error al cargar el detalle de la venta.
          </div>
        </div>
      </td>
    `;
  }
}




// Array global que almacena los items antes de enviar al backend
let items = [];

document.addEventListener('DOMContentLoaded', function () {

    // Botón agregar item a la lista
    const addBtn = document.getElementById('addItem');
    if (addBtn) addBtn.addEventListener('click', addItem);

    // Evento submit del formulario: actualiza el input hidden antes de enviar
    const form = document.querySelector('#addinventory form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (items.length === 0) {
                alert('No hay productos para guardar');
                e.preventDefault();
                return;
            }
            document.getElementById('itemsInput').value = JSON.stringify(items);
        });
    }
});

function addItem() {

    const product = document.getElementById('product');
    const location = document.getElementById('location');
    const buy = parseFloat(document.getElementById('buy').value);
    const qty = parseFloat(document.getElementById('qty').value);

    if (!product.value || !location.value || isNaN(buy) || isNaN(qty)) {
        alert('Completa todos los campos correctamente');
        return;
    }

    const item = {
        product_id: product.value,
        product_name: product.options[product.selectedIndex].text,
        location_id: location.value,
        location_name: location.options[location.selectedIndex].text,
        buy: buy,
        qty: qty,
        total: buy * qty
    };

    items.push(item);
    renderTable();
    clearFields();
}

function renderTable() {

    const tbody = document.querySelector('#tempTable tbody');
    tbody.innerHTML = '';

    items.forEach((item, index) => {
        tbody.innerHTML += `
            <tr>
            <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </td>
                <td>${item.product_name}</td>
                <td>${item.location_name}</td>
                <td>${item.buy.toFixed(2)}</td>
                <td>${item.qty}</td>
                <td>${item.total.toFixed(2)}</td>
            </tr>
        `;
    });
}

function removeItem(index) {
    items.splice(index, 1);
    renderTable();
}

function clearFields() {
    // Limpiar valores
    document.getElementById('buy').value = '';
    document.getElementById('qty').value = '';
    
    // Refrescar selects de Bootstrap para que se muestre "(Seleccione un producto / ubicación)"
    $('.selectpicker').selectpicker('render');
}

document.addEventListener("DOMContentLoaded", function () {

    const el = document.getElementById("sortable-images");

    if (el) {
        new Sortable(el, {
            animation: 150,
            ghostClass: "bg-light"
        });

        const form = el.closest("form");

        form.addEventListener("submit", function () {

            let order = [];

            document.querySelectorAll(".sortable-item").forEach((item, index) => {
                order.push({
                    id: item.dataset.id,
                    order: index + 1
                });
            });

            document.getElementById("image_order").value = JSON.stringify(order);
        });
    }

});

/*
|--------------------------------------------------------------------------
| TRANSFERENCIA MULTIPLE (FRONTEND)
|--------------------------------------------------------------------------
| Maneja la lista temporal de traslados antes de enviarlos al backend
|--------------------------------------------------------------------------
*/

let transferItems = [];

document.addEventListener("DOMContentLoaded", function () {

    // Botón agregar item
    const addBtn = document.getElementById("addTransferItem");
    if (addBtn) addBtn.addEventListener("click", addTransferItem);

    // Submit del formulario
    const form = document.querySelector("#addTransfer form");

    if (form) {
        form.addEventListener("submit", function (e) {

            if (transferItems.length === 0) {
                alert("Debe agregar al menos un traslado");
                e.preventDefault();
                return;
            }

            document.getElementById("transferItemsInput").value =
                JSON.stringify(transferItems);
        });
    }
});

/*
|--------------------------------------------------------------------------
| AGREGAR ITEM A LISTA
|--------------------------------------------------------------------------
*/
function addTransferItem() {

    const product = document.getElementById("product_select");
    const fromLocation = document.getElementById("from_location");
    const toLocation = document.getElementById("location_destino");
    const account = document.getElementById("account_select");
    const qty = parseInt(document.querySelector("[name='qty']").value);

    if (!product.value || !fromLocation.value || !toLocation.value || !account.value || !qty || qty <= 0) {
        alert("Completa todos los campos");
        return;
    }

    const item = {
        product_id: product.value,
        product_name: product.options[product.selectedIndex].text,

        from_location_id: fromLocation.value,
        from_location_name: fromLocation.options[fromLocation.selectedIndex].text,

        to_location_id: toLocation.value,
        to_location_name: toLocation.options[toLocation.selectedIndex].text,

        account_id: account.value,
        account_name: account.options[account.selectedIndex].text,

        qty: qty
    };

    transferItems.push(item);

    renderTransferTable();
    clearTransferFields();
}

/*
|--------------------------------------------------------------------------
| RENDER TABLA TEMPORAL
|--------------------------------------------------------------------------
*/
function renderTransferTable() {

    const tbody = document.querySelector("#tempTransferTable tbody");
    tbody.innerHTML = "";

    transferItems.forEach((item, index) => {
        tbody.innerHTML += `
            <tr>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeTransferItem(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                <td>${item.product_name}</td>
                <td>${item.from_location_name}</td>
                <td>${item.to_location_name}</td>
                <td>${item.qty}</td>
            </tr>
        `;
    });
}

/*
|--------------------------------------------------------------------------
| ELIMINAR ITEM
|--------------------------------------------------------------------------
*/
function removeTransferItem(index) {
    transferItems.splice(index, 1);
    renderTransferTable();
}

/*
|--------------------------------------------------------------------------
| LIMPIAR CAMPOS
|--------------------------------------------------------------------------
*/
function clearTransferFields() {
    document.querySelector("[name='qty']").value = "";
}



/*-----------------------------------------------------------------*/

/**
 * Obtiene el porcentaje de descuento configurado para la venta.
 *
 * @returns {number} Porcentaje de descuento entre 0 y 100.
 */
function getDiscountPercent() {
    const discountInput = document.getElementById('saleDiscountPercent');

    if (!discountInput) {
        return 0;
    }

    let percent = parseFloat(discountInput.value || 0);

    if (isNaN(percent)) {
        percent = 0;
    }

    return Math.max(0, Math.min(100, percent));
}

/**
 * Calcula el subtotal original de una fila de producto.
 *
 * @param {Object} product Producto de la venta.
 * @returns {number} Subtotal antes del descuento.
 */
function getProductSubtotal(product) {
    if (!product) {
        return 0;
    }

    const quantity = parseFloat(product.quantity || 0);
    const unitPrice = parseFloat(product.unitPrice || 0);

    return quantity * unitPrice;
}

/**
 * Calcula el descuento correspondiente a un producto.
 *
 * El descuento solamente se aplica cuando el producto
 * está seleccionado mediante su checkbox.
 *
 * @param {Object} product Producto de la venta.
 * @returns {number} Valor del descuento.
 */
function getProductDiscountAmount(product) {

    if (!product || !product.discount_selected) {
        return 0;
    }

    const subtotal = getProductSubtotal(product);

    if (subtotal <= 0) {
        return 0;
    }

    const percent = getDiscountPercent();

    /*
     * Si el porcentaje es 0, no hay descuento,
     * pero NO se modifica discount_selected.
     */
    if (percent <= 0) {
        return 0;
    }

    return Math.round(
        subtotal * (percent / 100) * 100
    ) / 100;
}

/**
 * Calcula el subtotal final de un producto después
 * de aplicar el descuento correspondiente.
 *
 * @param {Object} product Producto de la venta.
 * @returns {number} Subtotal final.
 */
function getProductFinalSubtotal(product) {
    const subtotal = getProductSubtotal(product);
    const discount = getProductDiscountAmount(product);

    return Math.round(
        (subtotal - discount) * 100
    ) / 100;
}

/**
 * Calcula el valor total de los descuentos aplicados
 * a los productos seleccionados.
 *
 * @returns {number} Total de descuentos.
 */
function getTotalDiscount() {
    const totalDiscount = saleProducts.reduce((sum, product) => {
        return sum + getProductDiscountAmount(product);
    }, 0);

    return Math.round(
        totalDiscount * 100
    ) / 100;
}

/**
 * Sincroniza la información de descuento de todos
 * los productos de la venta.
 *
 * @returns {void}
 */
function syncProductDiscountData() {

    const percent = getDiscountPercent();

    saleProducts.forEach(product => {

        /*
         * Aseguramos que exista el estado del checkbox.
         * Nunca lo modificamos aquí.
         */
        if (typeof product.discount_selected !== 'boolean') {
            product.discount_selected = false;
        }

        const subtotal =
            getProductSubtotal(product);

        let discountAmount = 0;

        /*
         * El descuento solamente se calcula cuando:
         *
         * 1. El producto está seleccionado.
         * 2. El porcentaje es mayor que 0.
         *
         * Si el porcentaje es 0, el checkbox permanece
         * seleccionado pero simplemente no se aplica descuento.
         */
        if (
            product.discount_selected &&
            percent > 0
        ) {

            discountAmount = Math.round(
                subtotal *
                (percent / 100) *
                100
            ) / 100;
        }

        const finalSubtotal = Math.round(
            (subtotal - discountAmount) * 100
        ) / 100;

        /*
         * Guardamos el porcentaje actual.
         *
         * Si está seleccionado y el porcentaje es 0:
         * discount_percent = 0
         *
         * Pero discount_selected permanece TRUE.
         */
        product.discount_percent =
            product.discount_selected
                ? percent
                : 0;

        product.discount_amount =
            discountAmount;

        product.subtotal =
            Math.round(subtotal * 100) / 100;

        product.final_subtotal =
            finalSubtotal;
    });
}

/**
 * Habilita o deshabilita los checkboxes de descuento
 * dependiendo del porcentaje ingresado.
 *
 * @returns {void}
 */
function toggleProductDiscountCheckboxes() {
    const percent = getDiscountPercent();

    const checkboxes = document.querySelectorAll(
        '#productosSeleccionados .product-discount-checkbox'
    );

    checkboxes.forEach(checkbox => {

        if (percent > 0) {
            checkbox.disabled = false;
        } else {
            checkbox.checked = false;
            checkbox.disabled = true;
        }
    });

    if (percent <= 0) {
        saleProducts.forEach(product => {
            product.discount_selected = false;
        });
    }
}

/**
 * Actualiza el porcentaje de descuento de la venta.
 *
 * El porcentaje ingresado no se aplica automáticamente
 * a todos los productos. Solo afecta los productos
 * cuyo checkbox esté seleccionado.
 *
 * @returns {void}
 */
function updateDiscountPercent() {

    const discountInput =
        document.getElementById('saleDiscountPercent');

    if (!discountInput) {
        return;
    }

    let percent =
        parseFloat(
            discountInput.value || 0
        );

    if (isNaN(percent)) {
        percent = 0;
    }

    /*
     * Limitar el porcentaje entre 0 y 100.
     */
    if (percent < 0) {
        percent = 0;
    }

    if (percent > 100) {
        percent = 100;
    }

    /*
     * IMPORTANTE:
     *
     * NO desmarcamos ningún checkbox aquí.
     *
     * Si el usuario borra temporalmente el porcentaje
     * para escribir otro, los productos seleccionados
     * permanecen seleccionados.
     */
    syncProductDiscountData();

    renderProducts();
}

/**
 * Actualiza la selección de descuento de una fila.
 *
 * @param {number} index Índice del producto en saleProducts.
 * @param {boolean} checked Estado del checkbox.
 * @returns {void}
 */
function updateProductDiscountSelection(index, checked) {

    if (!saleProducts[index]) {
        return;
    }

    /*
     * El checkbox solamente controla si el producto
     * participa o no en el descuento.
     *
     * No importa si el porcentaje actualmente es 0.
     */
    saleProducts[index].discount_selected =
        Boolean(checked);

    /*
     * Recalcular descuento y totales.
     */
    syncProductDiscountData();

    renderProducts();
}