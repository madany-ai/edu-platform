<div class="p-4 flex flex-col items-center justify-center space-y-4 text-center">
    <div id="printable-card" class="w-full max-w-sm border-2 border-primary-500 rounded-2xl p-6 bg-gradient-to-br from-primary-50 to-white dark:from-gray-900 dark:to-gray-800 shadow-xl relative overflow-hidden text-right dir-rtl">
        <div class="absolute top-0 right-0 left-0 h-3 bg-primary-600"></div>

        <div class="flex items-center justify-between mb-4 border-b pb-3 border-gray-200 dark:border-gray-700">
            <div>
                <h4 class="font-bold text-lg text-primary-700 dark:text-primary-400">كارنيه الطالب الرسمـي</h4>
                <p class="text-xs text-gray-500">{{ config('app.name', 'المنصة التعليمية') }}</p>
            </div>
            <span class="text-xs font-mono bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-300 px-2.5 py-1 rounded-full font-bold">
                {{ $student->student_code ?? 'بدون كود' }}
            </span>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <div class="bg-white p-2 rounded-xl shadow border border-gray-100 flex-shrink-0">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($student->student_code ?? $student->id) }}"
                    alt="QR Code"
                    class="w-24 h-24 object-contain"
                />
            </div>
            <div class="space-y-1 text-sm">
                <p class="font-bold text-base text-gray-900 dark:text-white">
                    {{ $student->first_name }} {{ $student->second_name }} {{ $student->third_name }} {{ $student->last_name }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">الصف:</span> {{ $student->gradeLevel?->name ?? 'غير محدد' }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">المجموعة:</span> {{ $student->group?->name ?? 'بدون مجموعة' }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">الهاتف:</span> <span dir="ltr">{{ $student->phone }}</span>
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">ولي الأمر:</span> <span dir="ltr">{{ $student->father_phone }}</span>
                </p>
            </div>
        </div>

        <div class="text-center text-[10px] text-gray-400 border-t pt-2 border-gray-200 dark:border-gray-700">
            امسح الـ QR Code لتسجيل الحضور الفوري بالحصة
        </div>
    </div>

    <button
        type="button"
        onclick="window.print()"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg text-sm transition-colors shadow"
    >
        🖨️ طباعة الكارنيه
    </button>
</div>
