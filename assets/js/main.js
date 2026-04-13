/**
 * JavaScript Principal del Sistema POS
 */

$(document).ready(function() {
    // Toggle Sidebar en móvil
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').addClass('active');
        $('<div class="overlay"></div>').appendTo('body').addClass('active');
    });
    
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').removeClass('active');
        $('.overlay').remove();
    });
    
    $(document).on('click', '.overlay', function() {
        $('#sidebar').removeClass('active');
        $(this).remove();
    });
    
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
    
    // Inicializar DataTables
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
        responsive: true
    });
    
    // Confirmar eliminación
    $(document).on('click', '.btn-eliminar', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
    
    // Formatear moneda
    $('.moneda').on('input', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        if (value) {
            $(this).val(new Intl.NumberFormat('es-CO').format(value));
        }
    });
    
    // Calcular IVA automáticamente
    function calcularTotales() {
        let subtotal = 0;
        $('.detalle-subtotal').each(function() {
            subtotal += parseFloat($(this).val() || 0);
        });
        
        const ivaPorcentaje = parseFloat($('#impuesto_porcentaje').val() || 19);
        const impuesto = subtotal * (ivaPorcentaje / 100);
        const descuento = parseFloat($('#descuento').val() || 0);
        const total = subtotal + impuesto - descuento;
        
        $('#subtotal').val(subtotal.toFixed(0));
        $('#impuesto').val(impuesto.toFixed(0));
        $('#total').val(total.toFixed(0));
        
        $('.subtotal-display').text('$ ' + new Intl.NumberFormat('es-CO').format(subtotal));
        $('.impuesto-display').text('$ ' + new Intl.NumberFormat('es-CO').format(impuesto));
        $('.total-display').text('$ ' + new Intl.NumberFormat('es-CO').format(total));
    }
    
    $(document).on('change', '.detalle-cantidad, .detalle-precio, #impuesto_porcentaje, #descuento', function() {
        const row = $(this).closest('.detalle-row');
        const cantidad = parseFloat(row.find('.detalle-cantidad').val() || 0);
        const precio = parseFloat(row.find('.detalle-precio').val() || 0);
        const subtotal = cantidad * precio;
        
        row.find('.detalle-subtotal').val(subtotal.toFixed(0));
        row.find('.subtotal-cell').text('$ ' + new Intl.NumberFormat('es-CO').format(subtotal));
        
        calcularTotales();
    });
    
    // Agregar detalle
    let detalleIndex = 1;
    $('#agregar-detalle').on('click', function() {
        const template = $('#detalle-template').html();
        const newRow = template.replace(/\{index\}/g, detalleIndex);
        $('#detalles-container').append(newRow);
        detalleIndex++;
        
        // Inicializar select2 en nuevos selects
        $('.select2-producto').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: '../../api/productos/buscar.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                }
            }
        });
    });
    
    // Eliminar detalle
    $(document).on('click', '.eliminar-detalle', function() {
        $(this).closest('.detalle-row').remove();
        calcularTotales();
    });
    
    // Buscador de productos
    $('.buscar-producto').on('input', function() {
        const query = $(this).val();
        const resultsDiv = $(this).siblings('.search-results');
        
        if (query.length < 2) {
            resultsDiv.hide();
            return;
        }
        
        $.ajax({
            url: '../../api/productos/buscar.php',
            method: 'GET',
            data: { q: query },
            success: function(response) {
                resultsDiv.empty();
                if (response.length > 0) {
                    response.forEach(function(item) {
                        resultsDiv.append(`
                            <div class="search-item" data-id="${item.id}" data-codigo="${item.codigo}" 
                                 data-nombre="${item.nombre}" data-precio="${item.precio_venta}">
                                <strong>${item.codigo}</strong> - ${item.nombre}<br>
                                <small>$ ${new Intl.NumberFormat('es-CO').format(item.precio_venta)}</small>
                            </div>
                        `);
                    });
                    resultsDiv.show();
                } else {
                    resultsDiv.hide();
                }
            }
        });
    });
    
    // Seleccionar producto de búsqueda
    $(document).on('click', '.search-item', function() {
        const row = $(this).closest('.detalle-row');
        const id = $(this).data('id');
        const codigo = $(this).data('codigo');
        const nombre = $(this).data('nombre');
        const precio = $(this).data('precio');
        
        row.find('.producto-id').val(id);
        row.find('.producto-nombre').text(nombre);
        row.find('.detalle-precio').val(precio);
        row.find('.buscar-producto').val(codigo);
        row.find('.search-results').hide();
        
        calcularTotales();
    });
    
    // Imprimir
    $('.btn-imprimir').on('click', function() {
        window.print();
    });
    
    // Cerrar alertas automáticamente
    setTimeout(function() {
        $('.alert-auto-dismiss').alert('close');
    }, 5000);
    
    // Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Fechas por defecto
    if ($('.fecha-hoy').length && !$('.fecha-hoy').val()) {
        const today = new Date().toISOString().split('T')[0];
        $('.fecha-hoy').val(today);
    }
});

// Función para exportar tabla a Excel
function exportarExcel(tablaId, nombreArchivo) {
    const tabla = document.getElementById(tablaId);
    const html = tabla.outerHTML;
    const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    
    const link = document.createElement('a');
    link.download = nombreArchivo + '.xls';
    link.href = url;
    link.click();
}

// Función para exportar a PDF
function exportarPDF(elementoId, nombreArchivo) {
    window.print();
}

// Formatear número como moneda
function formatMoneda(valor) {
    return '$ ' + new Intl.NumberFormat('es-CO').format(valor);
}
