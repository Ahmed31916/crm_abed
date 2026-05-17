<?php

namespace App\Console\Commands;

use App\Models\ProductEventOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupProductEvents extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'product-events:cleanup
                            {--days=7 : حذف الأحداث الأقدم من هذا العدد بالأيام}
                            {--status=sent : حالة الأحداث المراد حذفها (sent, failed)}';

    /**
     * وصف الأمر
     */
    protected $description = 'تنظيف أحداث المنتجات القديمة من جدول الـ outbox';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $status = $this->option('status');

        $cutoffDate = now()->subDays($days);

        $deleted = ProductEventOutbox::where('status', $status)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        if ($deleted > 0) {
            $this->info("تم حذف {$deleted} حدث بوضع '{$status}' أقدم من {$days} أيام");
            Log::info("CleanupProductEvents: Deleted {$deleted} old events", [
                'status' => $status,
                'older_than_days' => $days,
            ]);
        } else {
            $this->info('لا توجد أحداث قديمة للحذف');
        }

        return Command::SUCCESS;
    }
}
