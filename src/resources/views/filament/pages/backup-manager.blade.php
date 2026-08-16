<x-filament-panels::page>
    <div class="flex justify-end mb-4">
        <x-filament::button wire:click="createBackup">
            إنشاء نسخة احتياطية الآن (DB)
        </x-filament::button>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">الاسم</th>
                    <th class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">الحجم</th>
                    <th class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">التاريخ</th>
                    <th class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white text-right">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($backups as $backup)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400" dir="ltr">{{ $backup['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $backup['size'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400" dir="ltr">{{ $backup['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right flex justify-end gap-2">
                            <x-filament::button size="sm" color="success" wire:click="downloadBackup('{{ $backup['path'] }}')">
                                تحميل
                            </x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="deleteBackup('{{ $backup['path'] }}')" wire:confirm="هل أنت متأكد من حذف هذه النسخة؟">
                                حذف
                            </x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            لا توجد نسخ احتياطية حالياً.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
