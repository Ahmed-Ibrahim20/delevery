<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateNotifications extends Command
{
    protected $signature = 'notifications:clean-duplicates {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean up duplicate notifications from the database';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No notifications will be deleted');
        } else {
            $this->info('🧹 Starting cleanup of duplicate notifications...');
        }
        
        $this->newLine();

        // Find and clean user-based duplicates
        $this->cleanUserDuplicates($dryRun);
        
        // Find and clean role-based duplicates
        $this->cleanRoleDuplicates($dryRun);

        $this->newLine();
        $this->info('✅ Cleanup completed!');
    }

    private function cleanUserDuplicates($dryRun)
    {
        $this->info('1. Cleaning user-based duplicate notifications...');
        
        // Find duplicates based on user_id, title, notifiable_id, notifiable_type
        $duplicates = DB::select("
            SELECT 
                user_id, 
                title, 
                notifiable_id, 
                notifiable_type,
                GROUP_CONCAT(id ORDER BY created_at ASC) as notification_ids,
                COUNT(*) as count
            FROM notifications 
            WHERE user_id IS NOT NULL
            GROUP BY user_id, title, notifiable_id, notifiable_type 
            HAVING COUNT(*) > 1
        ");

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->notification_ids);
            $keepId = array_shift($ids); // Keep the first (oldest) notification
            $deleteIds = $ids; // Delete the rest

            $this->line("   Found {$duplicate->count} duplicates for User ID: {$duplicate->user_id}, Title: '{$duplicate->title}'");
            $this->line("   Keeping notification ID: {$keepId}");
            $this->line("   " . ($dryRun ? 'Would delete' : 'Deleting') . " notification IDs: " . implode(', ', $deleteIds));

            if (!$dryRun && !empty($deleteIds)) {
                $deleted = Notification::whereIn('id', $deleteIds)->delete();
                $totalDeleted += $deleted;
                $this->line("   ✅ Deleted {$deleted} duplicate notifications");
            }
            
            $this->newLine();
        }

        if (empty($duplicates)) {
            $this->line('   ✅ No user-based duplicate notifications found');
        } else {
            $message = $dryRun 
                ? "   📊 Would delete " . array_sum(array_column($duplicates, 'count')) - count($duplicates) . " duplicate notifications"
                : "   ✅ Deleted {$totalDeleted} duplicate notifications";
            $this->line($message);
        }
    }

    private function cleanRoleDuplicates($dryRun)
    {
        $this->info('2. Cleaning role-based duplicate notifications...');
        
        // Find duplicates based on target_role, title, notifiable_id, notifiable_type
        $duplicates = DB::select("
            SELECT 
                target_role, 
                title, 
                notifiable_id, 
                notifiable_type,
                GROUP_CONCAT(id ORDER BY created_at ASC) as notification_ids,
                COUNT(*) as count
            FROM notifications 
            WHERE target_role IS NOT NULL AND user_id IS NULL
            GROUP BY target_role, title, notifiable_id, notifiable_type 
            HAVING COUNT(*) > 1
        ");

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->notification_ids);
            $keepId = array_shift($ids); // Keep the first (oldest) notification
            $deleteIds = $ids; // Delete the rest

            $roleName = $this->getRoleName($duplicate->target_role);
            $this->line("   Found {$duplicate->count} duplicates for Role: {$roleName}, Title: '{$duplicate->title}'");
            $this->line("   Keeping notification ID: {$keepId}");
            $this->line("   " . ($dryRun ? 'Would delete' : 'Deleting') . " notification IDs: " . implode(', ', $deleteIds));

            if (!$dryRun && !empty($deleteIds)) {
                $deleted = Notification::whereIn('id', $deleteIds)->delete();
                $totalDeleted += $deleted;
                $this->line("   ✅ Deleted {$deleted} duplicate notifications");
            }
            
            $this->newLine();
        }

        if (empty($duplicates)) {
            $this->line('   ✅ No role-based duplicate notifications found');
        } else {
            $message = $dryRun 
                ? "   📊 Would delete " . array_sum(array_column($duplicates, 'count')) - count($duplicates) . " duplicate notifications"
                : "   ✅ Deleted {$totalDeleted} duplicate notifications";
            $this->line($message);
        }
    }

    private function getRoleName($role)
    {
        return match($role) {
            0 => 'Admin',
            1 => 'Driver', 
            2 => 'Shop',
            default => 'Unknown'
        };
    }
}
