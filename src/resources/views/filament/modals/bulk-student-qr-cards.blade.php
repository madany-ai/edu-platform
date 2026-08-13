<div class="p-4 flex flex-col items-center justify-center space-y-4 text-center">
    <div class="w-full text-right mb-4">
        <button
            type="button"
            onclick="window.print()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm transition-colors shadow"
        >
            🖨️ طباعة كل الكارنيهات المحددة
        </button>
    </div>

    <style>
        @page {
            size: A4 portrait;
            margin: 20mm;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-bulk-cards, #printable-bulk-cards * {
                visibility: visible;
            }
            /* Reset Filament modal constraints to allow full page width in print */
            .fi-modal-window, [role="dialog"], .fi-modal {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                transform: none !important;
                left: 0 !important;
                top: 0 !important;
            }

            #printable-bulk-cards {
                position: static !important; 
                display: grid !important;
                grid-template-columns: repeat(2, 85mm) !important;
                justify-content: space-between !important;
                gap: 8mm 5mm !important;
                width: 100% !important;
                padding-left: 15mm !important;
                padding-right: 5mm !important;
                margin: 0 !important;
                background-color: white !important;
                box-sizing: border-box !important;
            }
            .bulk-card-item {
                width: 85mm !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border: 2px solid #000 !important;
                border-radius: 10px !important;
                padding: 15px !important;
                box-sizing: border-box !important;
                background: #fff !important;
            }
            .bulk-card-item * {
                color: #000 !important;
            }
        }
    </style>

    <div id="printable-bulk-cards" class="w-full flex flex-wrap justify-center gap-6">
        @foreach($students as $student)
            <div class="bulk-card-item w-full max-w-[260px] border-2 border-gray-800 rounded-2xl p-6 bg-white shadow-sm flex flex-col items-center justify-center text-center">
                
                <div class="mb-4 bg-white p-2 flex-shrink-0">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($student->student_code ?? $student->id) }}"
                        alt="QR Code"
                        class="w-32 h-32 object-contain"
                    />
                </div>
                
                <div class="space-y-3 w-full border-t-2 border-gray-200 pt-4 mt-2">
                    <div class="text-base font-mono font-bold tracking-widest text-gray-900">
                        {{ $student->student_code ?? 'بدون كود' }}
                    </div>
                    
                    <div class="font-bold text-lg text-gray-900 leading-snug">
                        {{ $student->first_name }} {{ $student->second_name }} {{ $student->third_name }}
                    </div>
                </div>

            </div>
        @endforeach
    </div>
</div>
