<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament-panels::form wire:submit="loadRankings">
            {{ $this->form }}
        </x-filament-panels::form>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                🏆 قائمة المتفوقين والأوائل
            </h3>

            @if(empty($rankings))
                <div class="text-center py-12 text-gray-500">
                    يرجى اختيار مجموعة دراسية أو صف دراسي لعرض ترتيب الأوائل.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 text-sm font-semibold text-gray-600 dark:text-gray-400">
                                <th class="py-3 px-4">المركز</th>
                                <th class="py-3 px-4">كود الطالب</th>
                                <th class="py-3 px-4">اسم الطالب</th>
                                <th class="py-3 px-4">مجموع الدرجات الحاصل عليها</th>
                                <th class="py-3 px-4">النسبة المئوية</th>
                                <th class="py-3 px-4">عدد الامتحانات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($rankings as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-lg">
                                        @if($index == 0)
                                            <span class="inline-flex items-center gap-1 text-amber-500 bg-amber-500/10 px-3 py-1 rounded-full text-sm">
                                                🥇 المركز الأول
                                            </span>
                                        @elseif($index == 1)
                                            <span class="inline-flex items-center gap-1 text-gray-400 bg-gray-400/10 px-3 py-1 rounded-full text-sm">
                                                🥈 المركز الثاني
                                            </span>
                                        @elseif($index == 2)
                                            <span class="inline-flex items-center gap-1 text-amber-700 bg-amber-700/10 px-3 py-1 rounded-full text-sm">
                                                🥉 المركز الثالث
                                            </span>
                                        @else
                                            <span class="text-gray-500 px-3">#{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-sm text-primary-600 dark:text-primary-400">
                                        {{ $item->student_code ?? 'بدون كود' }}
                                    </td>
                                    <td class="py-3 px-4 font-medium">
                                        {{ $item->first_name }} {{ $item->second_name }} {{ $item->third_name }} {{ $item->last_name }}
                                    </td>
                                    <td class="py-3 px-4 font-bold">
                                        {{ $item->total_score }} <span class="text-xs text-gray-400">/ {{ $item->max_score }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->percentage >= 85 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                            {{ $item->percentage }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500">
                                        {{ $item->exams_count }} اختبارات
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
