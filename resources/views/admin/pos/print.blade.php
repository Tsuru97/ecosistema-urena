<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Ticket - {{ $sale->ticket_folio }}</title>
    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Librerías para Renderizado de Códigos de Barras y QR -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <style>
        body { background-color: #f3f3f9; padding: 20px; font-family: 'Inter', sans-serif; }
        .print-container { max-width: 1000px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        
        #viewer-container {
            width: 100%;
            overflow-x: auto;
            background: #e9ecef;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        /* Estilos del Documento de Impresión */
        .print-document {
            position: relative;
            background-color: white;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .field-item {
            position: absolute;
            box-sizing: border-box;
            display: flex;
            font-family: 'Helvetica', 'Arial', sans-serif;
            overflow: hidden;
        }

        .field-table {
            width: 100%;
            border-collapse: collapse;
            font-size: inherit;
            color: inherit;
        }

        .field-table th {
            padding: 4px 6px;
            font-weight: 700;
            background-color: #f1f5f9;
            border-bottom: 2px solid #475569;
            text-align: left;
            word-break: break-word;
        }

        .field-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #e2e8f0;
            word-break: break-word;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .no-print { display: none !important; }
            .print-container { box-shadow: none; max-width: 100%; padding: 0; }
            #viewer-container { padding: 0; background: transparent; display: block; overflow: visible; }
            .print-document { box-shadow: none; }
            @page { margin: 0; }
        }
    </style>
</head>
<body>

<div class="print-container">
    <div class="header no-print">
        <div>
            <h4 class="mb-0">Documento: <span class="text-primary">{{ $sale->ticket_folio ?? 'Folio' }}</span></h4>
            <p class="text-muted mb-0 small">Plantilla usada: {{ $template->name }}</p>
        </div>
        <div>
            <button class="btn btn-light me-2" onclick="window.close()">Cerrar</button>
            <button class="btn btn-primary" id="btn-print" onclick="window.print()"><i class="ri-printer-line"></i> Imprimir</button>
        </div>
    </div>

    <!-- Contenedor del PDF Nativo (Preview e Impresión) -->
    <div id="viewer-container">
        <div id="print-document" class="print-document"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    try {
        const template = JSON.parse(`{!! addslashes($template->template_json) !!}`);
        const data = JSON.parse(`{!! addslashes(json_encode($data)) !!}`);

        const doc = document.getElementById('print-document');
        
        // Dimensiones del documento base
        const mmW = template.width || 215.9; // Carta por defecto
        const mmH = template.height || 279.4;
        
        doc.style.width = mmW + 'mm';
        doc.style.height = mmH + 'mm';

        if (template.basePdf && template.basePdf.startsWith('data:image')) {
            doc.style.backgroundImage = `url('${template.basePdf}')`;
        }

        // Obtener schema unificado
        let fields = [];
        const pageSchema = template.schemas[0] || {};
        if (Array.isArray(pageSchema)) {
            fields = pageSchema;
        } else {
            fields = Object.entries(pageSchema).map(([k, v]) => ({...v, name: v.name || k}));
        }

        let fieldsHtml = '';

        fields.forEach(field => {
            if (!field) return;
            const key = field.name;
            const baseKey = key.replace(/_(?!(3|6|9|12)$)\d+$/, '');
            
            // Obtener el valor (si no existe, usar content)
            let val = data[baseKey] !== undefined ? data[baseKey] : (data[key] !== undefined ? data[key] : field.content || '');

            const left = field.position.x;
            const top = field.position.y;
            const width = field.width;
            const height = field.height;

            const fontSize = field.fontSize || 10;
            const fontColor = field.fontColor || '#000000';
            const alignment = field.alignment || 'left';
            const valignment = field.valignment || 'top';

            let alignCss = alignment === 'center' ? 'center' : (alignment === 'right' ? 'flex-end' : 'flex-start');
            let valignCss = valignment === 'top' ? 'flex-start' : (valignment === 'bottom' ? 'flex-end' : 'center');

            let contentHtml = '';

            if (field.type === 'table') {
                let tableHeader = '';
                let tableBody = '';
                if (Array.isArray(val) && val.length > 0) {
                    const colWidths = (field.columnsWidths || '').split(',').map(n => parseInt(n.trim()));
                    
                    val.forEach((row, rIdx) => {
                        let rowHtml = '';
                        const isHeader = rIdx === 0;
                        if (Array.isArray(row)) {
                            row.forEach((cellText, cIdx) => {
                                const w = colWidths[cIdx] ? `width: ${colWidths[cIdx]}%;` : '';
                                if (isHeader) {
                                    rowHtml += `<th style="${w}">${cellText}</th>`;
                                } else {
                                    rowHtml += `<td style="${w}">${cellText}</td>`;
                                }
                            });
                        }
                        if (isHeader) {
                            tableHeader = `<thead><tr>${rowHtml}</tr></thead>`;
                        } else {
                            tableBody += `<tr>${rowHtml}</tr>`;
                        }
                    });
                }
                
                contentHtml = `
                    <div style="font-size:${fontSize}pt;color:${fontColor};width:100%;height:100%;overflow:hidden;">
                        <table class="field-table">
                            ${tableHeader}
                            <tbody>${tableBody}</tbody>
                        </table>
                    </div>
                `;

            } else if (field.type === 'image') {
                contentHtml = val ? `<img src="${val}" style="width: 100%; height: 100%; object-fit: contain;">` : '';
            } else if (field.type === 'barcodes.code128') {
                contentHtml = `<svg class="barcode-svg" data-value="${val}" style="width: 100%; height: 100%;"></svg>`;
            } else if (field.type === 'qrcode') {
                contentHtml = `<canvas class="qrcode-canvas" data-value="${val}" style="width: 100%; height: 100%;"></canvas>`;
            } else {
                contentHtml = `<div style="font-size:${fontSize}pt;color:${fontColor};text-align:${alignment};width:100%;height:100%;white-space:pre-wrap;display:flex;align-items:${valignCss};justify-content:${alignCss};">${val}</div>`;
            }

            fieldsHtml += `
                <div class="field-item" style="left: ${left}mm; top: ${top}mm; width: ${width}mm; height: ${height}mm;">
                    ${contentHtml}
                </div>
            `;
        });

        doc.innerHTML = fieldsHtml;

        // Renderizar códigos de barras reales mediante JsBarcode
        doc.querySelectorAll('.barcode-svg').forEach(el => {
            const v = el.getAttribute('data-value');
            if (v) {
                try {
                    JsBarcode(el, String(v), {
                        format: "CODE128",
                        displayValue: false,
                        margin: 0,
                        height: el.clientHeight || 35,
                        width: 2
                    });
                } catch (err) {
                    console.error('Error al generar código de barras JsBarcode:', err);
                }
            }
        });

        // Renderizar códigos QR reales mediante qrcode.js
        doc.querySelectorAll('.qrcode-canvas').forEach(el => {
            const v = el.getAttribute('data-value');
            if (v) {
                try {
                    const sz = Math.min(el.clientWidth || 70, el.clientHeight || 70);
                    QRCode.toCanvas(el, String(v), {
                        margin: 0,
                        width: sz
                    });
                } catch (err) {
                    console.error('Error al generar código QR QRCode.js:', err);
                }
            }
        });

    } catch (e) {
        console.error("Error renderizando documento nativo:", e);
        document.getElementById('viewer-container').innerHTML = '<div class="alert alert-danger w-100 mt-4"><strong>Error al procesar la plantilla:</strong> ' + e.message + '</div>';
    }
});
</script>

</body>
</html>
